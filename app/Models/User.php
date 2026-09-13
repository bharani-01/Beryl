<?php

namespace App\Models;

use App\Actions\User\RevokeUserTeamTokens;
use App\Jobs\UpdateStripeCustomerEmailJob;
use App\Notifications\Channels\SendsEmail;
use App\Notifications\TransactionalEmails\EmailChangeVerification;
use App\Notifications\TransactionalEmails\ResetPassword as TransactionalEmailsResetPassword;
use App\Services\ChangelogService;
use App\Traits\DeletesUserSessions;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\NewAccessToken;
use OpenApi\Attributes as OA;

#[OA\Schema(
    description: 'User model',
    type: 'object',
    properties: [
        'id' => ['type' => 'integer', 'description' => 'The user identifier in the database.'],
        'name' => ['type' => 'string', 'description' => 'The user name.'],
        'email' => ['type' => 'string', 'description' => 'The user email.'],
        'email_verified_at' => ['type' => 'string', 'description' => 'The date when the user email was verified.'],
        'created_at' => ['type' => 'string', 'description' => 'The date when the user was created.'],
        'updated_at' => ['type' => 'string', 'description' => 'The date when the user was updated.'],
        'two_factor_confirmed_at' => ['type' => 'string', 'description' => 'The date when the user two factor was confirmed.'],
        'force_password_reset' => ['type' => 'boolean', 'description' => 'The flag to force the user to reset the password.'],
        'marketing_emails' => ['type' => 'boolean', 'description' => 'The flag to receive marketing emails.'],
    ],
)]
class User extends Authenticatable implements SendsEmail
{
    use DeletesUserSessions, HasApiTokens, HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'current_team_id',
        'force_password_reset',
        'marketing_emails',
        'pending_email',
        'email_change_code',
        'email_change_code_expires_at',
        'avatar_path',
        'avatar_storage_type',
        'avatar_s3_storage_id',
        'is_suspended',
        'suspension_reason',
        'last_active_at',
        'total_api_calls',
        'last_api_call_at',
        'last_login_at',
        'last_login_ip',
        'recent_locations',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected $casts = [
        'current_team_id' => 'integer',
        'email_verified_at' => 'datetime',
        'force_password_reset' => 'boolean',
        'is_suspended' => 'boolean',
        'show_boarding' => 'boolean',
        'email_change_code_expires_at' => 'datetime',
        'last_active_at' => 'datetime',
        'total_api_calls' => 'integer',
        'last_api_call_at' => 'datetime',
        'last_login_at' => 'datetime',
        'recent_locations' => 'array',
    ];

    /**
     * Record an IP and device into the user's recent locations history (capped at 10 distinct locations).
     */
    public function recordLocation(string $ip, ?string $device = null): void
    {
        if (! $ip || trim($ip) === '') {
            return;
        }

        $geo = \App\Services\Audit\IpLocationService::resolve($ip);
        $locations = is_array($this->recent_locations) ? $this->recent_locations : [];

        $foundIndex = null;
        foreach ($locations as $idx => $loc) {
            if (($loc['ip'] ?? '') === $ip) {
                $foundIndex = $idx;
                break;
            }
        }

        $entry = [
            'ip' => $ip,
            'country' => $geo['country'] ?? 'Unknown',
            'country_code' => $geo['country_code'] ?? null,
            'flag' => null,
            'city' => $geo['city'] ?? 'Unknown',
            'region' => $geo['region'] ?? null,
            'isp' => $geo['isp'] ?? null,
            'device' => $device ?: ($foundIndex !== null ? ($locations[$foundIndex]['device'] ?? 'Web') : 'Web'),
            'first_seen_at' => $foundIndex !== null ? ($locations[$foundIndex]['first_seen_at'] ?? now()->toIso8601String()) : now()->toIso8601String(),
            'last_seen_at' => now()->toIso8601String(),
            'hits_count' => $foundIndex !== null ? (($locations[$foundIndex]['hits_count'] ?? 1) + 1) : 1,
        ];

        if ($foundIndex !== null) {
            unset($locations[$foundIndex]);
        }

        array_unshift($locations, $entry);
        $locations = array_slice($locations, 0, 10);

        $this->updateQuietly([
            'recent_locations' => $locations,
            'last_login_ip' => $ip,
        ]);
    }

    public function apiLogs()
    {
        return $this->hasMany(ApiLog::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    public function isOnline(): bool
    {
        return $this->last_active_at !== null && $this->last_active_at->gt(now()->subMinutes(15));
    }

    public function presenceStatus(): string
    {
        if (! $this->last_active_at) {
            return 'offline';
        }
        if ($this->last_active_at->gt(now()->subMinutes(15))) {
            return 'online';
        }
        if ($this->last_active_at->gt(now()->subHours(2))) {
            return 'idle';
        }

        return 'offline';
    }

    public function isSuspended(): bool
    {
        return (bool) $this->is_suspended;
    }

    /**
     * Set the email attribute to lowercase.
     */
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }

    /**
     * Set the pending_email attribute to lowercase.
     */
    public function setPendingEmailAttribute($value)
    {
        $this->attributes['pending_email'] = $value ? strtolower($value) : null;
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function (User $user) {
            $team = [
                'name' => $user->name."'s Team",
                'personal_team' => true,
                'show_boarding' => true,
            ];
            if ($user->id === 0) {
                $team['id'] = 0;
                $team['name'] = 'Root Team';
            }
            $new_team = $user->id === 0 ? Team::find(0) : null;

            if ($new_team !== null) {
                $new_team->forceFill($team);
                $new_team->save();

                if (! $user->teams()->whereKey($new_team->id)->exists()) {
                    $user->teams()->attach($new_team, ['role' => 'owner']);
                } else {
                    $user->teams()->updateExistingPivot($new_team->id, ['role' => 'owner']);
                }

                return;
            }

            $new_team = (new Team)->forceFill($team);
            $new_team->save();

            $user->teams()->attach($new_team, ['role' => 'owner']);
        });

        static::deleting(function (User $user) {
            \DB::transaction(function () use ($user) {
                RevokeUserTeamTokens::forUser($user);

                $teams = $user->teams;
                foreach ($teams as $team) {
                    $user_alone_in_team = $team->members->count() === 1;

                    // Prevent deletion if user is alone in root team
                    if ($team->id === 0 && $user_alone_in_team) {
                        throw new \Exception('User is alone in the root team, cannot delete');
                    }

                    if ($user_alone_in_team) {
                        static::finalizeTeamDeletion($user, $team);
                        // Delete any pending team invitations for this user
                        TeamInvitation::whereEmail($user->email)->delete();

                        continue;
                    }

                    // Load the user's role for this team
                    $userRole = $team->members->where('id', $user->id)->first()?->pivot?->role;

                    if ($userRole === 'owner') {
                        $found_other_owner_or_admin = $team->members->filter(function ($member) use ($user) {
                            return ($member->pivot->role === 'owner' || $member->pivot->role === 'admin') && $member->id !== $user->id;
                        })->first();

                        if ($found_other_owner_or_admin) {
                            $team->members()->detach($user->id);

                            continue;
                        } else {
                            $found_other_member_who_is_not_owner = $team->members->filter(function ($member) {
                                return $member->pivot->role === 'member';
                            })->first();

                            if ($found_other_member_who_is_not_owner) {
                                $found_other_member_who_is_not_owner->pivot->role = 'owner';
                                $found_other_member_who_is_not_owner->pivot->save();
                                RevokeUserTeamTokens::forUserTeam($found_other_member_who_is_not_owner, $team->id);
                                $team->members()->detach($user->id);
                            } else {
                                static::finalizeTeamDeletion($user, $team);
                            }

                            continue;
                        }
                    } else {
                        $team->members()->detach($user->id);
                    }
                }
            });
        });
    }

    /**
     * Finalize team deletion by cleaning up all associated resources
     */
    private static function finalizeTeamDeletion(User $user, Team $team)
    {
        $servers = $team->servers;
        foreach ($servers as $server) {
            $resources = $server->definedResources();
            foreach ($resources as $resource) {
                $resource->forceDelete();
            }
            $server->forceDelete();
        }

        $projects = $team->projects;
        foreach ($projects as $project) {
            $project->forceDelete();
        }

        $team->members()->detach($user->id);
        $team->delete();
    }

    /**
     * Delete the user if they are not verified and have a force password reset.
     * This is used to clean up users that have been invited, did not accept the invitation (and did not verify their email and have a force password reset).
     */
    public function deleteIfNotVerifiedAndForcePasswordReset()
    {
        if ($this->hasVerifiedEmail() === false && $this->force_password_reset === true) {
            $this->delete();
        }
    }

    public function recreate_personal_team()
    {
        $team = [
            'name' => $this->name."'s Team",
            'personal_team' => true,
            'show_boarding' => true,
        ];
        if ($this->id === 0) {
            $team['id'] = 0;
            $team['name'] = 'Root Team';
        }
        $new_team = (new Team)->forceFill($team);
        $new_team->save();
        $this->teams()->attach($new_team, ['role' => 'owner']);

        return $new_team;
    }

    public function createToken(string $name, array $abilities = ['*'], ?DateTimeInterface $expiresAt = null)
    {
        $plainTextToken = sprintf(
            '%s%s%s',
            config('sanctum.token_prefix', ''),
            $tokenEntropy = Str::random(40),
            hash('crc32b', $tokenEntropy)
        );

        $token = $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
            'abilities' => $abilities,
            'expires_at' => $expiresAt,
            'team_id' => session('currentTeam')->id,
        ]);

        return new NewAccessToken($token, $token->getKey().'|'.$plainTextToken);
    }

    public function teams()
    {
        return $this->belongsToMany(Team::class)->withPivot('role');
    }

    public function changelogReads()
    {
        return $this->hasMany(UserChangelogRead::class);
    }

    public function getUnreadChangelogCount(): int
    {
        return app(ChangelogService::class)->getUnreadCountForUser($this);
    }

    public function getRecipients(): array
    {
        return [$this->email];
    }

    public function sendVerificationEmail()
    {
        $mail = new MailMessage;
        $url = URL::temporarySignedRoute(
            'verify.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $this->getKey(),
                'hash' => hash('sha256', $this->getEmailForVerification()),
            ]
        );
        $mail->view('emails.email-verification', [
            'url' => $url,
        ]);
        $mail->subject('Coolify: Verify your email.');
        send_user_an_email($mail, $this->email);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this?->notify(new TransactionalEmailsResetPassword($token));
    }

    public function isAdmin()
    {
        return $this->role() === 'admin' || $this->role() === 'owner';
    }

    public function isOwner()
    {
        return $this->role() === 'owner';
    }

    public function isMember()
    {
        return $this->role() === 'member';
    }

    public function isAdminFromSession()
    {
        if (Auth::id() === 0) {
            return true;
        }
        $teams = $this->teams()->get();

        $is_part_of_root_team = $teams->where('id', 0)->first();
        $is_admin_of_root_team = $is_part_of_root_team &&
            ($is_part_of_root_team->pivot->role === 'admin' || $is_part_of_root_team->pivot->role === 'owner');

        if ($is_part_of_root_team && $is_admin_of_root_team) {
            return true;
        }
        $team = $teams->where('id', session('currentTeam')->id)->first();
        $role = data_get($team, 'pivot.role');

        return $role === 'admin' || $role === 'owner';
    }

    public function isInstanceAdmin()
    {
        $found_root_team = $this->teams->filter(function ($team) {
            if ($team->id == 0) {
                $role = $team->pivot->role;
                if ($role !== 'admin' && $role !== 'owner') {
                    return false;
                }

                return true;
            }

            return false;
        });

        return $found_root_team->count() > 0;
    }

    public function currentTeam(): ?Team
    {
        $sessionTeamId = data_get(session('currentTeam'), 'id');

        // Fallback for stateless API requests: resolve team from Sanctum token
        if (is_null($sessionTeamId) && $this->currentAccessToken()) {
            $sessionTeamId = data_get($this->currentAccessToken(), 'team_id');
        }

        if (is_null($sessionTeamId)) {
            return null;
        }

        // Check if user actually belongs to this team
        if (! $this->teams->contains('id', $sessionTeamId)) {
            session()->forget('currentTeam');
            Cache::forget('user:'.$this->id.':team:'.$sessionTeamId);

            return null;
        }

        return Cache::remember('user:'.$this->id.':team:'.$sessionTeamId, 3600, function () use ($sessionTeamId) {
            return Team::find($sessionTeamId);
        });
    }

    /**
     * Resolve the team to activate when the session has no current team
     * (fresh login or an invalidated session).
     *
     * Returns the user's last active team when they still belong to it, or the
     * sole team of a single-team user. Returns null when the choice is ambiguous
     * (more than one team and no valid stored preference) — the caller must then
     * prompt the user to pick a team instead of defaulting silently.
     */
    public function resolveStoredTeam(): ?Team
    {
        if (! is_null($this->current_team_id)) {
            $storedTeam = $this->teams->firstWhere('id', $this->current_team_id);
            if ($storedTeam) {
                return $storedTeam;
            }
        }

        if ($this->teams->count() === 1) {
            return $this->teams->first();
        }

        return null;
    }

    /**
     * Get the user's primary/personal team.
     */
    public function personalTeam(): ?Team
    {
        return $this->teams->firstWhere('personal_team', true)
            ?? $this->teams->firstWhere('pivot.role', 'owner')
            ?? $this->teams->first();
    }

    /**
     * Get the user's primary role in their personal or first team.
     */
    public function primaryRole(): string
    {
        $team = $this->personalTeam();
        if (! $team) {
            return 'member';
        }

        return data_get($team, 'pivot.role') ?? $this->roleInTeam($team->id) ?? 'owner';
    }

    /**
     * Reset the persisted active team when it points to the given team.
     *
     * Called when the user is removed from a team (or the team is deleted) so a
     * stale current_team_id can never be trusted after the fact. Read paths
     * already re-validate membership; this is defense-in-depth that clears the
     * dangling value at the source event instead of relying on self-healing.
     */
    public function clearStoredTeamIfMatches(int $teamId): void
    {
        // Atomic conditional update: only null the column when the database value
        // still points at this team, so a newer team selection made concurrently
        // (in another request) is preserved rather than clobbered.
        static::query()
            ->whereKey($this->getKey())
            ->where('current_team_id', $teamId)
            ->update(['current_team_id' => null]);

        if ($this->current_team_id === $teamId) {
            $this->current_team_id = null;
        }
    }

    public function role(): ?string
    {
        if (data_get($this, 'pivot')) {
            return $this->pivot->role;
        }

        $current = $this->currentTeam();
        if (is_null($current)) {
            return null;
        }

        $team = $this->teams->where('id', $current->id)->first();

        return data_get($team, 'pivot.role');
    }

    /**
     * Get the user's role in a specific team
     */
    public function roleInTeam(int $teamId): ?string
    {
        $team = $this->teams->where('id', $teamId)->first();

        return data_get($team, 'pivot.role');
    }

    /**
     * Check if the user is an admin or owner of a specific team
     */
    public function isAdminOfTeam(int $teamId): bool
    {
        if ($this->id === 0 || $this->isInstanceAdmin()) {
            return true;
        }

        $team = $this->teams->where('id', $teamId)->first();

        if (! $team) {
            return false;
        }

        $role = $team->pivot->role ?? null;

        return $role === 'admin' || $role === 'owner';
    }

    /**
     * Check if the user can access system resources (team_id=0)
     * Must be admin/owner of root team
     */
    public function canAccessSystemResources(): bool
    {
        // Check if user is member of root team
        $rootTeam = $this->teams->where('id', 0)->first();

        if (! $rootTeam) {
            return false;
        }

        // Check if user is admin or owner of root team
        return $this->isAdminOfTeam(0);
    }

    public function requestEmailChange(string $newEmail): void
    {
        // Generate 6-digit code
        $code = sprintf('%06d', random_int(0, 999999));

        // Set expiration using config value
        $expiryMinutes = config('constants.email_change.verification_code_expiry_minutes', 10);
        $expiresAt = Carbon::now()->addMinutes($expiryMinutes);

        $this->fill([
            'pending_email' => $newEmail,
            'email_change_code' => $code,
            'email_change_code_expires_at' => $expiresAt,
        ])->save();

        // Send verification email to new address
        $this->notify(new EmailChangeVerification($this, $code, $newEmail, $expiresAt));
    }

    public function isEmailChangeCodeValid(string $code): bool
    {
        return $this->email_change_code === $code
            && $this->email_change_code_expires_at
            && Carbon::now()->lessThan($this->email_change_code_expires_at);
    }

    public function confirmEmailChange(string $code): bool
    {
        if (! $this->isEmailChangeCodeValid($code)) {
            return false;
        }

        $oldEmail = $this->email;
        $newEmail = $this->pending_email;

        // Update email and clear change request fields
        $this->update([
            'email' => $newEmail,
            'pending_email' => null,
            'email_change_code' => null,
            'email_change_code_expires_at' => null,
        ]);

        // For cloud users, dispatch job to update Stripe customer email asynchronously
        $currentTeam = $this->currentTeam();
        if (isCloud() && $currentTeam?->subscription) {
            dispatch(new UpdateStripeCustomerEmailJob(
                $currentTeam,
                $this->id,
                $newEmail,
                $oldEmail
            ));
        }

        return true;
    }

    public function clearEmailChangeRequest(): void
    {
        $this->update([
            'pending_email' => null,
            'email_change_code' => null,
            'email_change_code_expires_at' => null,
        ]);
    }

    public function hasEmailChangeRequest(): bool
    {
        return ! is_null($this->pending_email)
            && ! is_null($this->email_change_code)
            && $this->email_change_code_expires_at
            && Carbon::now()->lessThan($this->email_change_code_expires_at);
    }

    /**
     * Check if the user has a password set.
     * OAuth users are created without passwords.
     */
    public function hasPassword(): bool
    {
        return ! empty($this->password);
    }
}
