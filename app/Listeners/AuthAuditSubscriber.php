<?php

namespace App\Listeners;

use App\Services\Audit\ForensicAuditService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Events\Dispatcher;

class AuthAuditSubscriber
{
    /**
     * Handle user login.
     */
    public function handleLogin(Login $event): void
    {
        $user = $event->user;

        ForensicAuditService::record([
            'event_type' => 'auth.login.succeeded',
            'event_category' => 'AUTH',
            'severity' => 'NOTICE',
            'action_operation' => 'AUTH',
            'action_result' => 'SUCCESS',
            'actor_type' => 'HUMAN',
            'actor_id' => (string) $user->id,
            'actor_email' => $user->email,
            'actor_role' => method_exists($user, 'isInstanceAdmin') && $user->isInstanceAdmin() ? 'ROOT_ADMIN' : 'MEMBER',
            'organization_id' => $user->currentTeam()?->id,
            'target_type' => 'User',
            'target_id' => (string) $user->id,
            'target_name' => $user->name,
            'payload' => [
                'guard' => $event->guard,
                'remember' => $event->remember,
            ],
        ]);
    }

    /**
     * Handle failed authentication attempts.
     */
    public function handleFailed(Failed $event): void
    {
        $credentials = $event->credentials ?? [];
        $attemptedEmail = $credentials['email'] ?? ($credentials['name'] ?? 'unknown');

        ForensicAuditService::record([
            'event_type' => 'auth.login.failed',
            'event_category' => 'AUTH',
            'severity' => 'WARNING',
            'action_operation' => 'AUTH',
            'action_result' => 'FAILURE',
            'action_reason' => 'Invalid credentials supplied',
            'actor_type' => 'HUMAN',
            'actor_id' => null,
            'actor_email' => (string) $attemptedEmail,
            'target_type' => 'UserSession',
            'target_id' => null,
            'target_name' => (string) $attemptedEmail,
            'payload' => [
                'guard' => $event->guard,
                'attempted_email' => (string) $attemptedEmail,
            ],
        ]);
    }

    /**
     * Handle user logout.
     */
    public function handleLogout(Logout $event): void
    {
        $user = $event->user;
        if (! $user) {
            return;
        }

        ForensicAuditService::record([
            'event_type' => 'auth.logout',
            'event_category' => 'AUTH',
            'severity' => 'INFORMATIONAL',
            'action_operation' => 'AUTH',
            'action_result' => 'SUCCESS',
            'actor_type' => 'HUMAN',
            'actor_id' => (string) $user->id,
            'actor_email' => $user->email,
            'organization_id' => $user->currentTeam()?->id,
            'target_type' => 'User',
            'target_id' => (string) $user->id,
            'payload' => [
                'guard' => $event->guard,
            ],
        ]);
    }

    /**
     * Handle password reset.
     */
    public function handlePasswordReset(PasswordReset $event): void
    {
        $user = $event->user;

        ForensicAuditService::record([
            'event_type' => 'auth.password.reset',
            'event_category' => 'AUTH',
            'severity' => 'NOTICE',
            'action_operation' => 'UPDATE',
            'action_result' => 'SUCCESS',
            'actor_type' => 'HUMAN',
            'actor_id' => (string) $user->id,
            'actor_email' => $user->email,
            'organization_id' => $user->currentTeam()?->id,
            'target_type' => 'User',
            'target_id' => (string) $user->id,
        ]);
    }

    /**
     * Handle user registration.
     */
    public function handleRegistered(Registered $event): void
    {
        $user = $event->user;

        ForensicAuditService::record([
            'event_type' => 'auth.user.registered',
            'event_category' => 'AUTH',
            'severity' => 'NOTICE',
            'action_operation' => 'CREATE',
            'action_result' => 'SUCCESS',
            'actor_type' => 'HUMAN',
            'actor_id' => (string) $user->id,
            'actor_email' => $user->email,
            'organization_id' => $user->currentTeam()?->id,
            'target_type' => 'User',
            'target_id' => (string) $user->id,
        ]);
    }

    /**
     * Handle Fortify 2FA enabled.
     */
    public function handleTwoFactorEnabled(mixed $event): void
    {
        $user = data_get($event, 'user');
        if (! $user) {
            return;
        }

        ForensicAuditService::record([
            'event_type' => 'auth.2fa.enabled',
            'event_category' => 'ACCESS_CONTROL',
            'severity' => 'NOTICE',
            'action_operation' => 'UPDATE',
            'action_result' => 'SUCCESS',
            'actor_type' => 'HUMAN',
            'actor_id' => (string) $user->id,
            'actor_email' => $user->email,
            'organization_id' => $user->currentTeam()?->id,
            'target_type' => 'User',
            'target_id' => (string) $user->id,
        ]);
    }

    /**
     * Handle Fortify 2FA disabled (Dangerous operation).
     */
    public function handleTwoFactorDisabled(mixed $event): void
    {
        $user = data_get($event, 'user');
        if (! $user) {
            return;
        }

        ForensicAuditService::recordDangerousOperation(
            eventType: 'auth.2fa.disabled',
            reason: 'User disabled multi-factor authentication',
            target: ['type' => 'User', 'id' => (string) $user->id, 'name' => $user->email],
            payload: ['user_id' => $user->id]
        );
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        $listeners = [
            Login::class => 'handleLogin',
            Failed::class => 'handleFailed',
            Logout::class => 'handleLogout',
            PasswordReset::class => 'handlePasswordReset',
            Registered::class => 'handleRegistered',
        ];

        if (class_exists('Laravel\Fortify\Events\TwoFactorAuthenticationEnabled')) {
            $listeners['Laravel\Fortify\Events\TwoFactorAuthenticationEnabled'] = 'handleTwoFactorEnabled';
        }

        if (class_exists('Laravel\Fortify\Events\TwoFactorAuthenticationDisabled')) {
            $listeners['Laravel\Fortify\Events\TwoFactorAuthenticationDisabled'] = 'handleTwoFactorDisabled';
        }

        return $listeners;
    }
}
