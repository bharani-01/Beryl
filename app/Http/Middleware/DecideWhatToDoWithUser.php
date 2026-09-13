<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Models\Server;
use App\Models\StandaloneStorage;
use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class DecideWhatToDoWithUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($user = auth()->user()) {
            if ($user->is_suspended && ! (isInstanceAdmin() || $user->id === 0)) {
                auth()->guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'email' => 'Your account has been suspended by an administrator. Please contact support.',
                ]);
            }

            if ($user->teams?->count() === 0) {
                $currentTeam = $user->recreate_personal_team();
                refreshSession($currentTeam);
            }

            // Verify resource authenticity and authorization for deep links
            $this->verifyResourceAuthenticityAndAlignTeam($request, $user);

            if ($user->currentTeam()) {
                refreshSession($user->currentTeam());
                // A team is already active; the selection screen no longer applies.
                if ($request->routeIs('team.select')) {
                    return redirect()->route('dashboard');
                }
            } elseif ($user->teams?->count() > 0) {
                // No active team in the session (fresh login or invalidated selection).
                // Restore the last active team, or the sole team of a single-team user.
                $resolvedTeam = $user->resolveStoredTeam();
                if ($resolvedTeam) {
                    refreshSession($resolvedTeam);
                } elseif ($request->routeIs('team.select') || $request->routeIs('*livewire.update')) {
                    // Ambiguous choice: let the user pick a team on the selection screen.
                    // Livewire's update endpoint must pass through too, otherwise the
                    // selection action's AJAX call is redirected to HTML and never runs.
                    return $next($request);
                } else {
                    return redirect()->route('team.select');
                }
            }
        }
        if (! auth()->user() || ! isCloud()) {
            if (! isCloud() && showBoarding() && ! in_array($request->path(), allowedPathsForBoardingAccounts())) {
                return redirect()->route('onboarding');
            }

            return $next($request);
        }
        // Instance admins can access settings and admin routes regardless of subscription
        if (isInstanceAdmin() && ($request->routeIs('settings.*') || $request->path() === 'admin')) {
            return $next($request);
        }
        if (! isEmailVerificationBypassed() && ! auth()->user()->hasVerifiedEmail()) {
            if ($request->path() === 'verify' || in_array($request->path(), allowedPathsForInvalidAccounts()) || $request->routeIs('verify.verify')) {
                return $next($request);
            }

            return redirect()->route('verify.email');
        }
        if (! isSubscriptionActive() && ! isSubscriptionOnGracePeriod()) {
            if (! in_array($request->path(), allowedPathsForUnsubscribedAccounts())) {
                if (Str::startsWith($request->path(), 'invitations')) {
                    return $next($request);
                }

                return redirect()->route('subscription.index');
            }
        }
        if (showBoarding() && ! in_array($request->path(), allowedPathsForBoardingAccounts())) {
            if (Str::startsWith($request->path(), 'invitations')) {
                return $next($request);
            }

            return redirect()->route('onboarding');
        }
        if ((auth()->user()->hasVerifiedEmail() || isEmailVerificationBypassed()) && $request->path() === 'verify') {
            return redirect(RouteServiceProvider::HOME);
        }

        return $next($request);
    }

    /**
     * Verify resource authenticity, authorization, and hierarchical integrity for deep links.
     *
     * If the user is an authorized member of the team owning the requested resource,
     * automatically align the session's active team so the page loads seamlessly.
     * If unauthorized, explicitly deny access (403) to prevent cross-tenant access.
     */
    protected function verifyResourceAuthenticityAndAlignTeam(Request $request, $user): void
    {
        if ($request->routeIs('*livewire.update') || $request->is('api/*')) {
            return;
        }

        if ($projectUuid = $request->route('project_uuid')) {
            $project = Project::where('uuid', $projectUuid)->first();
            if (! $project) {
                abort(404, 'Project not found.');
            }

            if (! $user->teams->contains('id', $project->team_id)) {
                abort(403, 'You do not have permission to access this project.');
            }

            // User is an authentic member: align active team session if not already active
            if ($user->currentTeam()?->id !== $project->team_id) {
                $targetTeam = $user->teams->firstWhere('id', $project->team_id);
                refreshSession($targetTeam);
                session(['currentTeam' => $targetTeam]);
                $user->current_team_id = $targetTeam->id;
                $user->save();
            }

            // Hierarchical validation: environment, application, database, service
            if ($environmentUuid = $request->route('environment_uuid')) {
                $environment = $project->environments()->where('uuid', $environmentUuid)->first();
                if (! $environment) {
                    abort(404, 'Environment not found in this project.');
                }

                if ($applicationUuid = $request->route('application_uuid')) {
                    $application = $environment->applications()->where('uuid', $applicationUuid)->first();
                    if (! $application) {
                        abort(404, 'Application not found in this environment.');
                    }
                }

                if ($databaseUuid = $request->route('database_uuid')) {
                    $database = $environment->databases()->where('uuid', $databaseUuid)->first();
                    if (! $database) {
                        abort(404, 'Database not found in this environment.');
                    }
                }

                if ($serviceUuid = $request->route('service_uuid')) {
                    $service = $environment->services()->where('uuid', $serviceUuid)->first();
                    if (! $service) {
                        abort(404, 'Service not found in this environment.');
                    }
                }
            }
        } elseif ($serverUuid = $request->route('server_uuid')) {
            $server = Server::where('uuid', $serverUuid)->first();
            if (! $server) {
                abort(404, 'Server not found.');
            }

            if (! $user->teams->contains('id', $server->team_id)) {
                abort(403, 'You do not have permission to access this server.');
            }

            if ($user->currentTeam()?->id !== $server->team_id) {
                $targetTeam = $user->teams->firstWhere('id', $server->team_id);
                refreshSession($targetTeam);
                session(['currentTeam' => $targetTeam]);
                $user->current_team_id = $targetTeam->id;
                $user->save();
            }
        } elseif ($storageUuid = $request->route('storage_uuid')) {
            $storage = StandaloneStorage::where('uuid', $storageUuid)->first();
            if (! $storage) {
                abort(404, 'Storage not found.');
            }

            if (! $user->teams->contains('id', $storage->team_id)) {
                abort(403, 'You do not have permission to access this storage.');
            }

            if ($user->currentTeam()?->id !== $storage->team_id) {
                $targetTeam = $user->teams->firstWhere('id', $storage->team_id);
                refreshSession($targetTeam);
                session(['currentTeam' => $targetTeam]);
                $user->current_team_id = $targetTeam->id;
                $user->save();
            }
        }
    }
}
