<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\OauthSetting;
use App\Models\TeamInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Contracts\TwoFactorLoginResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->instance(RegisterResponse::class, new class implements RegisterResponse
        {
            public function toResponse($request)
            {
                // First user (root) will be redirected to /settings instead of / on registration.
                if ($request->user()->currentTeam->id === 0) {
                    return redirect()->route('settings.index');
                }

                return redirect(RouteServiceProvider::HOME);
            }
        });

        $resolveSafeRedirect = function ($request): ?string {
            $redirect = $request->input('redirect') ?: session()->pull('url.intended');
            if (empty($redirect) || ! is_string($redirect)) {
                return null;
            }

            // Reject protocol-relative URLs: e.g. "//attacker.com"
            if (str_starts_with($redirect, '//')) {
                $clean = ltrim($redirect, '/');
                if (! str_starts_with($clean, $request->getHost().'/')) {
                    return null;
                }
            }

            $scheme = parse_url($redirect, PHP_URL_SCHEME);
            if ($scheme && ! in_array(strtolower($scheme), ['http', 'https'], true)) {
                return null;
            }

            $host = parse_url($redirect, PHP_URL_HOST);
            if ($host && strtolower($host) !== strtolower($request->getHost())) {
                return null;
            }

            $path = parse_url($redirect, PHP_URL_PATH) ?? '';
            if (str_starts_with($path, '/api') || str_starts_with($path, '/livewire')) {
                return null;
            }

            return $redirect;
        };

        $this->app->instance(LoginResponse::class, new class($resolveSafeRedirect) implements LoginResponse
        {
            public function __construct(protected \Closure $resolveSafeRedirect) {}

            public function toResponse($request)
            {
                if ($request->wantsJson()) {
                    return response()->json(['two_factor' => false]);
                }

                $target = ($this->resolveSafeRedirect)($request);
                if ($target) {
                    return redirect()->to($target);
                }

                if ($request->user() && ($request->user()->id === 0 || $request->user()->isInstanceAdmin())) {
                    return redirect()->route('admin.index');
                }

                return redirect()->to(Fortify::redirects('login'));
            }
        });

        $this->app->instance(TwoFactorLoginResponse::class, new class($resolveSafeRedirect) implements TwoFactorLoginResponse
        {
            public function __construct(protected \Closure $resolveSafeRedirect) {}

            public function toResponse($request)
            {
                if ($request->wantsJson()) {
                    return response()->noContent();
                }

                $target = ($this->resolveSafeRedirect)($request);
                if ($target) {
                    return redirect()->to($target);
                }

                if ($request->user() && ($request->user()->id === 0 || $request->user()->isInstanceAdmin())) {
                    return redirect()->route('admin.index');
                }

                return redirect()->to(Fortify::redirects('login'));
            }
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::registerView(function () {
            $isFirstUser = User::count() === 0;

            $settings = instanceSettings();
            if (! $settings->is_registration_enabled) {
                return redirect()->route('login');
            }

            return view('auth.register', [
                'isFirstUser' => $isFirstUser,
            ]);
        });

        Fortify::loginView(function () {
            $settings = instanceSettings();
            $enabled_oauth_providers = OauthSetting::where('enabled', true)->get();
            $users = User::count();
            if ($users == 0) {
                // If there are no users, redirect to registration
                return redirect()->route('register');
            }

            if (request()->has('redirect') && filled(request()->get('redirect'))) {
                $redirect = (string) request()->get('redirect');
                $host = parse_url($redirect, PHP_URL_HOST);
                if (empty($host) || strtolower($host) === strtolower(request()->getHost())) {
                    session(['url.intended' => $redirect]);
                }
            }

            return view('auth.login', [
                'is_registration_enabled' => $settings->is_registration_enabled,
                'enabled_oauth_providers' => $enabled_oauth_providers,
                'redirect' => request('redirect') ?? session('url.intended'),
            ]);
        });

        Fortify::authenticateUsing(function (Request $request) {
            $email = strtolower($request->email);
            $user = User::where('email', $email)->with('teams')->first();
            if (
                $user &&
                Hash::check($request->password, $user->password)
            ) {
                if ($user->is_suspended && ! ($user->id === 0 || $user->isInstanceAdmin())) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        Fortify::username() => 'Your account has been suspended by an administrator. Please contact support.',
                    ]);
                }

                $user->updated_at = now();
                $user->save();

                // Check if user has a pending invitation they haven't accepted yet
                $invitation = TeamInvitation::whereEmail($email)->first();
                if ($invitation && $invitation->isValid()) {
                    // User is logging in for the first time after being invited
                    // Attach them to the invited team if not already attached
                    if (! $user->teams()->where('team_id', $invitation->team->id)->exists()) {
                        $user->teams()->attach($invitation->team->id, ['role' => $invitation->role]);
                    }
                    $user->currentTeam = $invitation->team;
                    $invitation->delete();
                    session(['currentTeam' => $user->currentTeam]);
                } else {
                    // Restore the last active team; only fall back when unambiguous.
                    $team = $user->resolveStoredTeam();
                    if (! $team && $user->teams->isEmpty()) {
                        $team = $user->recreate_personal_team();
                    }
                    if ($team) {
                        session(['currentTeam' => $user->currentTeam = $team]);
                    }
                    // Otherwise (multiple teams, no stored choice) leave the session
                    // team unset so the user is sent to the team-selection screen.
                }

                return $user;
            }
        });
        Fortify::requestPasswordResetLinkView(function () {
            return view('auth.forgot-password');
        });
        Fortify::resetPasswordView(function ($request) {
            return view('auth.reset-password', ['request' => $request]);
        });
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);

        Fortify::confirmPasswordView(function () {
            return view('auth.confirm-password');
        });

        Fortify::twoFactorChallengeView(function () {
            return view('auth.two-factor-challenge');
        });
    }
}
