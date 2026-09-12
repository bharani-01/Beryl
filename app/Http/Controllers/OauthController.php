<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;

class OauthController extends Controller
{
    public function redirect(string $provider)
    {
        $socialite_provider = get_socialite_provider($provider);

        return $socialite_provider->redirect();
    }

    public function callback(string $provider)
    {
        try {
            $oauthUser = get_socialite_provider($provider)->user();
            $email = trim((string) $oauthUser->email);
            if ($email === '') {
                abort(403, 'OAuth provider did not return an email address');
            }
            $email = strtolower($email);
            $user = User::whereEmail($email)->first();
            if (! $user) {
                $settings = instanceSettings();
                if (! $settings->is_registration_enabled) {
                    abort(403, 'Registration is disabled');
                }

                $user = User::create([
                    'name' => $oauthUser->name,
                    'email' => $email,
                ]);
            }
            Auth::login($user);

            $team = $user->resolveStoredTeam();
            if (! $team && $user->teams()->count() === 0) {
                $team = $user->recreate_personal_team();
            }
            if ($team) {
                session(['currentTeam' => $user->currentTeam = $team]);
            }

            $redirect = session()->pull('url.intended');
            if (! empty($redirect) && is_string($redirect)) {
                $host = parse_url($redirect, PHP_URL_HOST);
                if (empty($host) || strtolower($host) === strtolower(request()->getHost())) {
                    $scheme = parse_url($redirect, PHP_URL_SCHEME);
                    if (! $scheme || in_array(strtolower($scheme), ['http', 'https'], true)) {
                        $path = parse_url($redirect, PHP_URL_PATH) ?? '';
                        if (! str_starts_with($path, '/api') && ! str_starts_with($path, '/livewire')) {
                            return redirect()->to($redirect);
                        }
                    }
                }
            }

            return redirect()->intended('/');
        } catch (\Exception $e) {
            $errorCode = $e instanceof HttpException ? 'auth.failed' : 'auth.failed.callback';

            return redirect()->route('login')->withErrors([__($errorCode)]);
        }
    }
}
