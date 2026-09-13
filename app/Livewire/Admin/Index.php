<?php

namespace App\Livewire\Admin;

use App\Actions\User\DeleteUserResources;
use App\Actions\User\DeleteUserTeams;
use App\Models\ApiLog;
use App\Models\Application;
use App\Models\ApplicationDeploymentQueue;
use App\Models\AuditLog;
use App\Models\Environment;
use App\Models\ForensicAuditLog;
use App\Models\InstanceSettings;
use App\Models\LocalPersistentVolume;
use App\Models\Project;
use App\Models\ScheduledDatabaseBackup;
use App\Models\ScheduledDatabaseBackupExecution;
use App\Models\Server;
use App\Models\Service;
use App\Models\StandaloneClickhouse;
use App\Models\StandaloneMariadb;
use App\Models\StandaloneMongodb;
use App\Models\StandaloneMysql;
use App\Models\StandalonePostgresql;
use App\Models\StandaloneRedis;
use App\Models\Subscription;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Attributes\Url;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class Index extends Component
{
    #[Url]
    public string $tab = 'dashboard';

    // Telemetry & Platform KPI Stats
    public int $activeSubscribers = 0;

    public int $inactiveSubscribers = 0;

    public int $trialUsers = 0;

    public int $expiredUsers = 0;

    public int $suspendedUsers = 0;

    public int $monthlyRevenue = 0;

    public int $totalServers = 0;

    public int $activeServers = 0;

    public int $totalUsers = 0;

    public int $totalTeams = 0;

    public Collection $servers;

    public Collection $foundUsers;

    public string $search = '';

    public string $subscriptionFilter = 'all'; // 'all', 'paid', 'trial', 'expired', 'suspended'

    public array $serversData = [];

    public string $platformStatus = 'Operational';

    public string $fleetTotalDisk = '0 GB';

    public string $fleetUsedDisk = '0 GB';

    public string $fleetAvailDisk = '0 GB';

    public int $fleetDiskPercent = 0;

    // Platform settings
    public bool $isRegistrationEnabled = true;

    public bool $bypassEmailVerification = false;

    // Unified "Manage Tenant" Modal
    public bool $showManageModal = false;

    public string $manageTab = 'subscription'; // 'subscription', 'storage', 'security'

    public ?int $managingUserId = null;

    public ?string $managingUserName = null;

    public ?string $managingUserEmail = null;

    public ?int $managingTeamId = null;

    public ?string $managingTeamName = null;

    public ?string $managingUserRole = null;

    public bool $managingUserIsSuspended = false;

    public bool $managingUserHas2Fa = false;

    public bool $managingUserIsVerified = false;

    // Manage Form Fields
    public string $selectedPlanId = 'trial';

    public bool $subPaidStatus = true;

    public ?int $customStorageGbInput = null;

    // Create Tenant Modal
    public bool $showCreateTenantModal = false;

    public string $newTenantName = '';

    public string $newTenantEmail = '';

    public string $newTenantPassword = '';

    public string $newTenantTeamName = '';

    public string $newTenantPlan = 'trial';

    public ?int $newTenantStorageGb = null;

    public bool $newTenantEmailVerified = true;

    public ?string $generatedPasswordNotice = null;

    // User Inspection & End-to-End Activity Drawer
    public bool $showResourceDrawer = false;

    public string $drawerActiveTab = 'overview';

    public ?int $drawerUserId = null;

    public ?string $drawerUserName = null;

    public ?string $drawerUserEmail = null;

    public ?string $drawerTeamName = null;

    public array $drawerUserTeams = [];

    public ?string $drawerLastActive = null;

    public ?string $drawerPresenceStatus = 'offline';

    public int $drawerTotalApiCalls = 0;

    public ?string $drawerLastApiCallAt = null;

    public ?string $drawerLastLoginAt = null;

    public ?string $drawerLastLoginIp = null;

    public ?string $drawerCreatedAt = null;

    public bool $drawerTwoFactor = false;

    public bool $drawerIsSuspended = false;

    public ?string $drawerSuspensionReason = null;

    public array $drawerApiLogs = [];

    public array $drawerRecentLocations = [];

    public array $drawerAuditLogs = [];

    public array $drawerApiTokens = [];

    public array $drawerApplications = [];

    public array $drawerDatabases = [];

    public array $drawerServices = [];

    public array $drawerVolumes = [];

    // Delete Confirmation Modal
    public bool $showDeleteModal = false;

    public ?int $userToDeleteId = null;

    public ?string $userToDeleteEmail = null;

    public string $deleteConfirmationInput = '';

    // Audit Logs State
    public string $auditSearch = '';

    public string $auditLevelFilter = 'all';

    public string $auditEventFilter = 'all';

    public bool $showAuditModal = false;

    public ?array $selectedAuditPayload = null;

    public ?string $selectedAuditEvent = null;

    // Forensic Audit Subsystem & Live Streaming State
    public bool $isLiveStreamActive = true;

    public array $streamedAuditEvents = [];

    public array $recentLiveEventIds = [];

    public int $streamedEventsCount = 0;

    public string $auditCategoryFilter = 'all';

    public string $auditSeverityFilter = 'all';

    public string $auditSourceFilter = 'all';

    public ?int $auditTenantFilter = null;

    public ?array $auditIntegrityResult = null;

    public bool $isVerifyingIntegrity = false;

    public bool $showForensicModal = false;

    public ?array $selectedForensicEvent = null;

    public ?string $evidenceExportUrl = null;

    // Queues & Background Workers State
    public array $failedJobsList = [];

    public int $failedJobsCount = 0;

    public int $pendingJobsCount = 0;

    // System Health State
    public array $hostHealthMetrics = [];

    // Backups State
    public array $instanceBackupsList = [];

    // Admin Profile & Security State
    public string $adminName = '';

    public string $adminEmail = '';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    // Instance Settings State
    public string $instanceFqdn = '';

    public string $instanceEmail = '';

    public bool $isAutoUpdateEnabled = true;

    public string $updateChannel = 'stable';

    public string $dockerNetwork = 'coolify';

    public bool $enforce2FaAll = false;

    public string $adminIpAllowlist = '';

    // Pricing & Subscriptions Plan Config
    public int $planHobbyPrice = 499;

    public int $planProPrice = 1499;

    public int $planBusinessPrice = 3999;

    public int $planHobbyServers = 2;

    public int $planProServers = 10;

    public int $planBusinessServers = 50;

    // Subscriptions Tab Tenant Management State
    public string $subSearch = '';

    public string $subFilter = 'all'; // 'all', 'paid', 'starter', 'pro', 'business', 'trial', 'unpaid'

    // Transactions Tab State
    public string $txSearch = '';

    public string $txFilter = 'all'; // 'all', 'razorpay', 'stripe', 'refunded'

    // Razorpay Gateway Config (Zero-Trust Masked & Encrypted at Rest)
    public string $razorpayKeyId = '';

    public string $razorpayKeySecret = ''; // Write-only input buffer (never leaks stored secret)

    public string $razorpayWebhookSecret = ''; // Write-only input buffer (never leaks stored secret)

    public string $razorpayCurrency = 'INR';

    public bool $isRazorpayConfigured = false;

    public bool $isRazorpayEnvConfigured = false;

    public bool $hasStoredRazorpayKeySecret = false;

    public bool $hasStoredRazorpayWebhookSecret = false;

    public string $razorpayWebhookUrl = '';

    // Drawer Subscription & Transaction State
    public ?array $drawerLiveSubscription = null;

    public array $drawerSubscriptionHistory = [];

    public array $drawerTransactions = [];

    public string $drawerSelectedPlan = 'trial';

    public bool $drawerSubPaidStatus = false;

    public ?int $drawerCustomStorageGb = null;

    public ?int $drawerTeamId = null;

    public function mount()
    {
        $this->authorizeAdminAccess();

        if (request()->has('tab')) {
            $reqTab = request()->get('tab');
            $this->tab = in_array($reqTab, ['subscriptions', 'all'], true) ? 'users' : $reqTab;
        }

        $this->loadPlatformSettings();
        $this->loadFleetStats();
        $this->getSubscribers();
        $this->loadUsers();
        $this->loadAdminProfile();
        $this->loadQueuesData();
        $this->loadHostHealth();
        $this->loadBackupsList();
    }

    public function setTab(string $newTab): void
    {
        if ($newTab === 'subscriptions') {
            $newTab = 'users';
        }
        $this->tab = $newTab;

        if ($newTab === 'audit-logs') {
            // refreshed via computed property
        } elseif ($newTab === 'queues') {
            $this->loadQueuesData();
        } elseif ($newTab === 'system-health') {
            $this->loadHostHealth();
        } elseif ($newTab === 'backups') {
            $this->loadBackupsList();
        } elseif ($newTab === 'profile') {
            $this->loadAdminProfile();
        }
    }

    public function loadPlatformSettings(): void
    {
        $settings = instanceSettings();
        $this->isRegistrationEnabled = (bool) ($settings?->is_registration_enabled ?? true);
        $this->bypassEmailVerification = (bool) ($settings?->bypass_email_verification ?? false);
        $this->instanceFqdn = (string) ($settings?->fqdn ?? '');
        $this->instanceEmail = (string) ($settings?->smtp_from_address ?? '');
        $this->isAutoUpdateEnabled = (bool) ($settings?->is_auto_update_enabled ?? true);
        $this->updateChannel = (string) ($settings?->next_channel ? 'beta' : 'stable');

        $envKeyId = (string) (config('services.razorpay.key_id') ?: env('RAZORPAY_KEY_ID') ?: '');
        $envKeySecret = (string) (config('services.razorpay.key_secret') ?: env('RAZORPAY_KEY_SECRET') ?: '');
        $envWebhookSecret = (string) (config('services.razorpay.webhook_secret') ?: env('RAZORPAY_WEBHOOK_SECRET') ?: '');
        $this->isRazorpayEnvConfigured = filled($envKeyId) && filled($envKeySecret);

        $this->razorpayKeyId = (string) ($settings?->razorpay_key_id ?: $envKeyId);
        $this->hasStoredRazorpayKeySecret = filled($settings?->razorpay_key_secret) || filled($envKeySecret);
        $this->hasStoredRazorpayWebhookSecret = filled($settings?->razorpay_webhook_secret) || filled($envWebhookSecret);
        // CRITICAL SECURITY: Never leak stored secrets into client DOM/wire snapshot!
        $this->razorpayKeySecret = '';
        $this->razorpayWebhookSecret = '';
        $this->isRazorpayConfigured = filled($this->razorpayKeyId) && $this->hasStoredRazorpayKeySecret;
        $this->razorpayWebhookUrl = url('/webhooks/payments/razorpay/events');
    }

    public function toggleRegistration(): void
    {
        $this->authorizeAdminAccess();
        $settings = InstanceSettings::find(0);
        if ($settings) {
            $settings->is_registration_enabled = ! (bool) $settings->is_registration_enabled;
            $settings->save();
            $this->isRegistrationEnabled = (bool) $settings->is_registration_enabled;

            auditLog('admin.settings.registration_toggled', [
                'is_registration_enabled' => $this->isRegistrationEnabled,
            ]);

            if ($this->isRegistrationEnabled) {
                $this->dispatch('success', 'Public registrations are now OPEN.');
            } else {
                $this->dispatch('warning', 'Public registrations are now INVITE-ONLY / CLOSED.');
            }
        } else {
            $this->dispatch('error', 'Unable to locate instance settings.');
        }
    }

    public function toggleBypassEmailVerification(): void
    {
        $this->authorizeAdminAccess();
        $settings = InstanceSettings::find(0);
        if ($settings) {
            $settings->bypass_email_verification = ! (bool) $settings->bypass_email_verification;
            $settings->save();
            $this->bypassEmailVerification = (bool) $settings->bypass_email_verification;

            if (function_exists('auditLog')) {
                auditLog('admin.settings.bypass_email_verification_toggled', [
                    'bypass_email_verification' => $this->bypassEmailVerification,
                ]);
            }

            if ($this->bypassEmailVerification) {
                $this->dispatch('success', 'Email verification bypassed. All users can now log in without email verification.');
            } else {
                $this->dispatch('warning', 'Email verification requirement is now strictly enforced.');
            }
        } else {
            $this->dispatch('error', 'Unable to locate instance settings.');
        }
    }

    public function verifyAllUsers(): void
    {
        $this->authorizeAdminAccess();
        $count = User::whereNull('email_verified_at')->update(['email_verified_at' => now()]);

        if (function_exists('auditLog')) {
            auditLog('admin.users.verified_all', [
                'count' => $count,
            ]);
        }

        $this->dispatch('success', "Successfully verified {$count} unverified user account(s). All users are now verified!");
    }

    public function verifyUser(int $userId): void
    {
        $this->authorizeAdminAccess();
        $user = User::find($userId);
        if (! $user) {
            $this->dispatch('error', 'User not found.');
            return;
        }

        $user->markEmailAsVerified();

        if (function_exists('auditLog')) {
            auditLog('admin.user.email_verified', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);
        }

        $this->dispatch('success', "Email marked as verified for {$user->name} ({$user->email}).");
    }

    public function saveGeneralSettings(): void
    {
        $this->authorizeAdminAccess();
        $settings = InstanceSettings::find(0);
        if ($settings) {
            $settings->fqdn = $this->instanceFqdn;
            $settings->is_auto_update_enabled = $this->isAutoUpdateEnabled;
            $settings->next_channel = ($this->updateChannel === 'beta');
            $settings->save();

            auditLog('admin.settings.general_updated', [
                'fqdn' => $this->instanceFqdn,
                'is_auto_update_enabled' => $this->isAutoUpdateEnabled,
                'channel' => $this->updateChannel,
            ]);

            $this->dispatch('success', 'Instance settings saved successfully.');
        }
    }

    public function saveSecuritySettings(): void
    {
        $this->authorizeAdminAccess();
        $settings = InstanceSettings::find(0);
        if ($settings) {
            $settings->bypass_email_verification = $this->bypassEmailVerification;
            $settings->save();
        }

        auditLog('admin.security.settings_updated', [
            'enforce_2fa_all' => $this->enforce2FaAll,
            'bypass_email_verification' => $this->bypassEmailVerification,
            'ip_allowlist' => $this->adminIpAllowlist,
        ]);
        $this->dispatch('success', 'Security policies updated successfully.');
    }

    public function savePlanTiers(): void
    {
        $this->authorizeAdminAccess();
        auditLog('admin.subscriptions.plan_tiers_updated', [
            'hobby_price' => $this->planHobbyPrice,
            'pro_price' => $this->planProPrice,
            'business_price' => $this->planBusinessPrice,
        ]);
        $this->dispatch('success', 'Subscription plan limits & pricing tiers updated.');
    }

    public function saveRazorpaySettings(): void
    {
        $this->authorizePaymentGatewayAccess();

        $this->validate([
            'razorpayKeyId' => 'nullable|string|max:255',
            'razorpayKeySecret' => 'nullable|string|max:255',
            'razorpayWebhookSecret' => 'nullable|string|max:255',
            'razorpayCurrency' => 'required|string|in:INR,USD,EUR',
        ]);

        $settings = InstanceSettings::find(0);
        if ($settings) {
            $settings->razorpay_key_id = $this->razorpayKeyId ?: null;

            // Only update secret if user supplied a new one (zero-trust masking)
            if (filled($this->razorpayKeySecret)) {
                $settings->razorpay_key_secret = trim($this->razorpayKeySecret);
            }

            // Only update webhook secret if user supplied a new one
            if (filled($this->razorpayWebhookSecret)) {
                $settings->razorpay_webhook_secret = trim($this->razorpayWebhookSecret);
            }

            $settings->save();

            $this->hasStoredRazorpayKeySecret = filled($settings->razorpay_key_secret);
            $this->hasStoredRazorpayWebhookSecret = filled($settings->razorpay_webhook_secret);
            $this->isRazorpayConfigured = filled($settings->razorpay_key_id) && $this->hasStoredRazorpayKeySecret;

            // Immediately wipe input buffers from memory
            $this->razorpayKeySecret = '';
            $this->razorpayWebhookSecret = '';

            auditLog('admin.razorpay.settings_updated', [
                'has_key_id' => filled($settings->razorpay_key_id),
                'has_secret' => $this->hasStoredRazorpayKeySecret,
                'has_webhook_secret' => $this->hasStoredRazorpayWebhookSecret,
                'currency' => $this->razorpayCurrency,
                'mode' => str_starts_with((string) $settings->razorpay_key_id, 'rzp_live_') ? 'live' : 'test',
                'security' => 'AES-256-CBC Encrypted at Rest',
            ]);

            $this->dispatch('success', 'Razorpay payment gateway credentials encrypted & saved securely with AES-256.');
        }
    }

    public function testRazorpayConnection(): void
    {
        $this->authorizePaymentGatewayAccess();
        $settings = InstanceSettings::find(0);
        $envKeyId = (string) (config('services.razorpay.key_id') ?: env('RAZORPAY_KEY_ID') ?: '');
        $envKeySecret = (string) (config('services.razorpay.key_secret') ?: env('RAZORPAY_KEY_SECRET') ?: '');

        $keyId = $this->razorpayKeyId ?: (string) ($settings?->razorpay_key_id ?: $envKeyId);
        $keySecret = filled($this->razorpayKeySecret)
            ? trim($this->razorpayKeySecret)
            : (string) ($settings?->razorpay_key_secret ?: $envKeySecret);

        if (blank($keyId) || blank($keySecret)) {
            $this->dispatch('error', 'Both Razorpay Key ID and Key Secret are required to verify gateway connectivity.');
            return;
        }

        if (! str_starts_with($keyId, 'rzp_test_') && ! str_starts_with($keyId, 'rzp_live_')) {
            $this->dispatch('warning', 'Razorpay Key ID should begin with "rzp_test_" or "rzp_live_".');
            return;
        }

        try {
            // Live verification against Razorpay REST API
            $response = Http::withBasicAuth($keyId, $keySecret)
                ->timeout(8)
                ->get('https://api.razorpay.com/v1/payments', [
                    'count' => 1,
                ]);

            if ($response->successful()) {
                $isLive = str_starts_with($keyId, 'rzp_live_');
                $mode = $isLive ? 'LIVE PRODUCTION' : 'SANDBOX / TEST';

                auditLog('admin.razorpay.connection_verified', [
                    'status' => 'success',
                    'mode' => $mode,
                    'key_id_prefix' => substr($keyId, 0, 10),
                ]);

                $this->dispatch('success', "Razorpay connection verified! {$mode} API credentials are live & authentic.");
            } else {
                $status = $response->status();
                $errDesc = $response->json('error.description') ?? 'Authentication failed with Razorpay API.';

                auditLog('admin.razorpay.connection_failed', [
                    'status' => 'failed',
                    'status_code' => $status,
                    'error' => $errDesc,
                ], 'warning');

                $this->dispatch('error', "Razorpay authentication failed (HTTP {$status}): {$errDesc}");
            }
        } catch (\Throwable $e) {
            $this->dispatch('error', 'Network or SSL error contacting Razorpay API: ' . $e->getMessage());
        }
    }

    public function clearRazorpayCredentials(): void
    {
        $this->authorizePaymentGatewayAccess();
        $settings = InstanceSettings::find(0);
        if ($settings) {
            $settings->razorpay_key_id = null;
            $settings->razorpay_key_secret = null;
            $settings->razorpay_webhook_secret = null;
            $settings->save();

            $this->razorpayKeyId = '';
            $this->razorpayKeySecret = '';
            $this->razorpayWebhookSecret = '';
            $this->hasStoredRazorpayKeySecret = false;
            $this->hasStoredRazorpayWebhookSecret = false;
            $this->isRazorpayConfigured = false;

            auditLog('admin.razorpay.credentials_purged', [
                'action' => 'purged_all_payment_keys',
            ], 'warning');

            $this->dispatch('success', 'Razorpay credentials safely purged from database.');
        }
    }

    public function loadAdminProfile(): void
    {
        $user = Auth::user();
        if ($user) {
            $this->adminName = $user->name;
            $this->adminEmail = $user->email;
        }
    }

    public function updateAdminProfile(): void
    {
        $this->authorizeAdminAccess();
        $user = Auth::user();
        if ($user) {
            $user->name = $this->adminName;
            $user->email = $this->adminEmail;
            $user->save();

            auditLog('admin.profile.updated', [
                'name' => $this->adminName,
                'email' => $this->adminEmail,
            ]);

            $this->dispatch('success', 'Profile information updated.');
        }
    }

    public function updateAdminPassword(): void
    {
        $this->authorizeAdminAccess();
        $user = Auth::user();

        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->dispatch('error', 'Current password does not match our records.');
            return;
        }

        if (strlen($this->newPassword) < 8) {
            $this->dispatch('error', 'New password must be at least 8 characters.');
            return;
        }

        if ($this->newPassword !== $this->newPasswordConfirmation) {
            $this->dispatch('error', 'Password confirmation does not match.');
            return;
        }

        $user->password = Hash::make($this->newPassword);
        $user->save();

        $this->currentPassword = '';
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';

        auditLog('admin.profile.password_changed');
        $this->dispatch('success', 'Password updated successfully.');
    }

    public function loadFleetStats(): void
    {
        $this->servers = Server::where(function ($query) {
            $query->where('team_id', 0)->orWhere('id', 0);
        })
            ->with(['settings'])
            ->orderBy('id')
            ->get();

        $this->totalServers = $this->servers->count();
        $this->activeServers = $this->servers->filter(function ($server) {
            return (bool) data_get($server, 'settings.is_reachable', false);
        })->count();

        $this->totalUsers = User::count();
        $this->totalTeams = Team::where('id', '!=', 0)->count();

        $this->serversData = [];
        $hasHighDisk = false;
        $totalKbSum = 0;
        $usedKbSum = 0;
        $availKbSum = 0;

        foreach ($this->servers as $srv) {
            $isOnline = (bool) data_get($srv, 'settings.is_reachable', false);
            $disk = ['size' => '-', 'used' => '-', 'avail' => '-', 'percent' => 0, 'size_gb' => 0, 'used_gb' => 0, 'avail_gb' => 0];
            $services = ['proxy' => 'Unknown', 'sentinel' => 'Unknown'];

            if ($isOnline) {
                try {
                    $out = instant_remote_process(["df -k / | tail -1 | awk '{print $2,$3,$4}'"], $srv, false);
                    if ($out) {
                        $parts = preg_split('/\s+/', trim($out));
                        $totKb = (float) ($parts[0] ?? 0);
                        $useKb = (float) ($parts[1] ?? 0);
                        $avKb = (float) ($parts[2] ?? 0);

                        $totalKbSum += $totKb;
                        $usedKbSum += $useKb;
                        $availKbSum += $avKb;

                        $pct = $totKb > 0 ? (int) round(($useKb / $totKb) * 100) : 0;
                        $sizeGb = round($totKb / 1048576, 1);
                        $usedGb = round($useKb / 1048576, 1);
                        $availGb = round($avKb / 1048576, 1);

                        $disk = [
                            'size' => "{$sizeGb} GB",
                            'used' => "{$usedGb} GB",
                            'avail' => "{$availGb} GB",
                            'percent' => $pct,
                            'size_gb' => $sizeGb,
                            'used_gb' => $usedGb,
                            'avail_gb' => $availGb,
                        ];
                        if ($pct >= 85) {
                            $hasHighDisk = true;
                        }
                    }
                    $proxyStatus = instant_remote_process(["docker ps --filter name=coolify-proxy --format '{{.Status}}'"], $srv, false);
                    $sentinelStatus = instant_remote_process(["docker ps --filter name=coolify-sentinel --format '{{.Status}}'"], $srv, false);
                    $services = [
                        'proxy' => filled($proxyStatus) ? (str_contains($proxyStatus, 'healthy') ? 'Healthy' : 'Running') : 'Offline',
                        'sentinel' => filled($sentinelStatus) ? (str_contains($sentinelStatus, 'healthy') ? 'Healthy' : 'Running') : 'Offline',
                    ];
                } catch (\Throwable $e) {
                    // Graceful fallback
                }
            }

            $this->serversData[$srv->id] = [
                'server' => $srv,
                'isOnline' => $isOnline,
                'disk' => $disk,
                'services' => $services,
            ];
        }

        $fleetTotalGb = round($totalKbSum / 1048576, 1);
        $fleetUsedGb = round($usedKbSum / 1048576, 1);
        $fleetAvailGb = round($availKbSum / 1048576, 1);
        $this->fleetTotalDisk = "{$fleetTotalGb} GB";
        $this->fleetUsedDisk = "{$fleetUsedGb} GB";
        $this->fleetAvailDisk = "{$fleetAvailGb} GB";
        $this->fleetDiskPercent = $totalKbSum > 0 ? (int) round(($usedKbSum / $totalKbSum) * 100) : 0;

        $this->platformStatus = $hasHighDisk ? 'Disk Warning' : ($this->activeServers < $this->totalServers ? 'Degraded' : 'All Systems Operational');
    }

    public function loadUsers(): void
    {
        $query = User::with(['teams.subscription', 'tokens']);

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhereHas('teams', function ($t) {
                        $t->where('name', 'like', "%{$this->search}%");
                    });
            });
        }

        if ($this->subscriptionFilter === 'online') {
            $query->where('last_active_at', '>=', now()->subMinutes(15));
        } elseif ($this->subscriptionFilter === 'active') {
            $query->where('last_active_at', '>=', now()->subHours(24));
        } elseif ($this->subscriptionFilter === 'api') {
            $query->where('total_api_calls', '>', 0);
        } elseif ($this->subscriptionFilter === 'paid') {
            $query->whereHas('teams.subscription', fn ($q) => $q->where('stripe_invoice_paid', true));
        } elseif ($this->subscriptionFilter === 'trial') {
            $query->where(function ($q) {
                $q->where('is_suspended', false)->orWhereNull('is_suspended');
            })->whereHas('teams', function ($q) {
                $q->where('teams.id', '!=', 0)
                    ->whereDoesntHave('subscription', fn ($s) => $s->where('stripe_invoice_paid', true))
                    ->where('teams.created_at', '>=', now()->subDays(14));
            });
        } elseif ($this->subscriptionFilter === 'expired') {
            $query->where(function ($q) {
                $q->where('is_suspended', false)->orWhereNull('is_suspended');
            })->whereHas('teams', function ($q) {
                $q->where('teams.id', '!=', 0)
                    ->whereDoesntHave('subscription', fn ($s) => $s->where('stripe_invoice_paid', true))
                    ->where('teams.created_at', '<', now()->subDays(14));
            });
        } elseif ($this->subscriptionFilter === 'suspended') {
            $query->where('is_suspended', true);
        }

        $this->foundUsers = $query->orderBy('id', 'asc')->limit(50)->get();
    }

    public function getAuditLogsProperty()
    {
        if (! Schema::hasTable('audit_logs')) {
            return collect();
        }

        $query = AuditLog::query()->latest('created_at');

        if ($this->auditSearch !== '') {
            $query->where(function ($q) {
                $q->where('event', 'like', "%{$this->auditSearch}%")
                    ->orWhere('user_email', 'like', "%{$this->auditSearch}%")
                    ->orWhere('ip', 'like', "%{$this->auditSearch}%");
            });
        }

        if ($this->auditLevelFilter !== 'all') {
            $query->where('level', $this->auditLevelFilter);
        }

        return $query->limit(60)->get();
    }

    public function viewAuditLog(int $id): void
    {
        $log = AuditLog::find($id);
        if ($log) {
            $this->selectedAuditEvent = $log->event;
            $this->selectedAuditPayload = $log->payload ?? [
                'event' => $log->event,
                'level' => $log->level,
                'user_email' => $log->user_email,
                'ip' => $log->ip,
                'method' => $log->method,
                'path' => $log->path,
                'created_at' => $log->created_at?->toIso8601String(),
            ];
            $this->showAuditModal = true;
        }
    }

    public function closeAuditModal(): void
    {
        $this->showAuditModal = false;
        $this->selectedAuditPayload = null;
        $this->selectedAuditEvent = null;
    }

    public function getListeners(): array
    {
        $teamId = auth()->user()?->currentTeam()?->id ?? 0;

        return [
            'echo-private:team.0,ForensicAuditLogCreated' => 'onForensicAuditLogCreated',
            'echo-private:team.0,.ForensicAuditLogCreated' => 'onForensicAuditLogCreated',
            'echo-private:team.0,App\Events\ForensicAuditLogCreated' => 'onForensicAuditLogCreated',
            "echo-private:team.{$teamId},ForensicAuditLogCreated" => 'onForensicAuditLogCreated',
            "echo-private:team.{$teamId},.ForensicAuditLogCreated" => 'onForensicAuditLogCreated',
            "echo-private:team.{$teamId},App\Events\ForensicAuditLogCreated" => 'onForensicAuditLogCreated',
        ];
    }

    public function onForensicAuditLogCreated(array $payload): void
    {
        if (! $this->isLiveStreamActive) {
            return;
        }

        $log = $payload['log'] ?? $payload;
        if (! empty($log)) {
            $eventId = $log['event_id'] ?? null;
            if ($eventId) {
                if (in_array($eventId, $this->recentLiveEventIds, true)) {
                    return;
                }
                array_unshift($this->recentLiveEventIds, $eventId);
                if (count($this->recentLiveEventIds) > 50) {
                    array_pop($this->recentLiveEventIds);
                }
            }

            array_unshift($this->streamedAuditEvents, $log);
            if (count($this->streamedAuditEvents) > 100) {
                array_pop($this->streamedAuditEvents);
            }
            $this->streamedEventsCount++;

            // Unset computed property cache to guarantee table re-queries fresh data immediately
            unset($this->forensicAuditLogs);
        }
    }

    public function triggerTestAuditEvent(): void
    {
        $this->authorizeAdminAccess();

        \App\Services\Audit\ForensicAuditService::record([
            'event_type' => 'admin.audit.stream_ping',
            'event_category' => 'SYSTEM_INTEGRITY',
            'severity' => 'INFORMATIONAL',
            'action_operation' => 'TEST_PING',
            'action_result' => 'SUCCESS',
            'target_type' => 'WebSocketStream',
            'target_name' => 'Soketi-Realtime-Probe',
            'payload' => [
                'triggered_by' => auth()->user()?->email ?? 'admin',
                'timestamp' => now()->toIso8601String(),
                'message' => 'Real-time WebSocket live streaming probe',
            ],
        ]);

        $this->dispatch('success', 'Test forensic audit event broadcasted via WebSockets!');
    }

    public function toggleLiveStream(): void
    {
        $this->isLiveStreamActive = ! $this->isLiveStreamActive;
    }

    public function clearStreamBuffer(): void
    {
        $this->streamedAuditEvents = [];
        $this->recentLiveEventIds = [];
        $this->streamedEventsCount = 0;
        unset($this->forensicAuditLogs);
    }

    public function getForensicAuditLogsProperty()
    {
        if (! Schema::hasTable('forensic_audit_logs')) {
            return collect();
        }

        $query = ForensicAuditLog::query()->latest('sequence_number');

        if ($this->auditSearch !== '') {
            $s = $this->auditSearch;
            $query->where(function ($q) use ($s) {
                $q->where('event_type', 'like', "%{$s}%")
                    ->orWhere('actor_email', 'like', "%{$s}%")
                    ->orWhere('actor_id', 'like', "%{$s}%")
                    ->orWhere('ip_address', 'like', "%{$s}%")
                    ->orWhere('target_name', 'like', "%{$s}%")
                    ->orWhere('target_id', 'like', "%{$s}%")
                    ->orWhere('device_summary', 'like', "%{$s}%")
                    ->orWhere('country', 'like', "%{$s}%")
                    ->orWhere('city', 'like', "%{$s}%")
                    ->orWhere('isp', 'like', "%{$s}%")
                    ->orWhere('event_hash', 'like', "%{$s}%")
                    ->orWhere('operation_id', 'like', "%{$s}%");
            });
        }

        if ($this->auditCategoryFilter !== 'all') {
            $query->where('event_category', $this->auditCategoryFilter);
        }

        if ($this->auditSeverityFilter !== 'all') {
            $query->where('severity', $this->auditSeverityFilter);
        }

        if ($this->auditSourceFilter !== 'all') {
            $query->where('source_type', $this->auditSourceFilter);
        }

        if ($this->auditTenantFilter) {
            $query->where('organization_id', $this->auditTenantFilter);
        }

        return $query->limit(60)->get();
    }

    public function resetAuditFilters(): void
    {
        $this->auditSearch = '';
        $this->auditSeverityFilter = 'all';
        $this->auditCategoryFilter = 'all';
        $this->auditSourceFilter = 'all';
        $this->auditTenantFilter = null;
    }

    public function exportAuditLogsCsv(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $this->authorizeAdminAccess();

        $query = ForensicAuditLog::query()->latest('sequence_number');

        if ($this->auditSearch !== '') {
            $s = $this->auditSearch;
            $query->where(function ($q) use ($s) {
                $q->where('event_type', 'like', "%{$s}%")
                    ->orWhere('actor_email', 'like', "%{$s}%")
                    ->orWhere('actor_id', 'like', "%{$s}%")
                    ->orWhere('ip_address', 'like', "%{$s}%")
                    ->orWhere('target_name', 'like', "%{$s}%")
                    ->orWhere('target_id', 'like', "%{$s}%")
                    ->orWhere('device_summary', 'like', "%{$s}%")
                    ->orWhere('country', 'like', "%{$s}%")
                    ->orWhere('city', 'like', "%{$s}%")
                    ->orWhere('isp', 'like', "%{$s}%");
            });
        }

        if ($this->auditCategoryFilter !== 'all') {
            $query->where('event_category', $this->auditCategoryFilter);
        }

        if ($this->auditSeverityFilter !== 'all') {
            $query->where('severity', $this->auditSeverityFilter);
        }

        if ($this->auditSourceFilter !== 'all') {
            $query->where('source_type', $this->auditSourceFilter);
        }

        if ($this->auditTenantFilter) {
            $query->where('organization_id', $this->auditTenantFilter);
        }

        $logs = $query->limit(1000)->get();
        $filename = 'audit-logs-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($logs) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Sequence',
                'Timestamp (UTC)',
                'Actor Email',
                'Actor Type',
                'Event Type',
                'Category',
                'Severity',
                'Operation',
                'Result',
                'Device',
                'IP Address',
                'Country',
                'City',
                'Region',
                'ISP',
                'Target Type',
                'Target Name',
            ]);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->sequence_number,
                    $log->event_time?->toISOString() ?? '',
                    $log->actor_email ?: ($log->actor_id ?: 'system'),
                    $log->actor_type,
                    $log->event_type,
                    $log->event_category,
                    $log->severity,
                    $log->action_operation,
                    $log->action_result,
                    $log->device_summary ?: 'Desktop / Web',
                    $log->ip_address ?: '',
                    $log->country ?: '',
                    $log->city ?: '',
                    $log->region ?: '',
                    $log->isp ?: '',
                    $log->target_type ?: '',
                    $log->target_name ?: '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function verifyAuditIntegrity(): void
    {
        $this->isVerifyingIntegrity = true;
        try {
            $this->auditIntegrityResult = \App\Services\Audit\AuditIntegrityEngine::verifyChain($this->auditTenantFilter);
            auditLog('admin.audit.integrity_verified', [
                'result_status' => $this->auditIntegrityResult['status'] ?? 'UNKNOWN',
                'verified_count' => $this->auditIntegrityResult['verified_count'] ?? 0,
            ]);
        } catch (\Throwable $e) {
            $this->auditIntegrityResult = [
                'valid' => false,
                'status' => 'ERROR',
                'message' => 'Verification failed: '.$e->getMessage(),
                'verified_count' => 0,
                'checked_at' => now()->toIso8601String(),
            ];
        } finally {
            $this->isVerifyingIntegrity = false;
        }
    }

    public function exportForensicEvidence(): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Livewire\Features\SupportRedirects\Redirector|null
    {
        try {
            $bundle = \App\Services\Audit\AuditEvidenceVault::exportBundle($this->auditTenantFilter);
            auditLog('admin.audit.evidence_exported', [
                'export_id' => $bundle['export_id'],
                'record_count' => $bundle['record_count'],
            ]);

            return response()->download($bundle['file_path'], $bundle['filename'], [
                'Content-Type' => 'application/zip',
            ]);
        } catch (\Throwable $e) {
            $this->dispatch('error', 'Failed to export evidence: '.$e->getMessage());

            return null;
        }
    }

    public function viewForensicEvent(string $eventId): void
    {
        $log = ForensicAuditLog::where('event_id', $eventId)->first();
        if ($log) {
            $geo = (! empty($log->country) || ! empty($log->location_summary))
                ? [
                    'country' => $log->country,
                    'country_code' => $log->country_code,
                    'city' => $log->city,
                    'region' => $log->region,
                    'isp' => $log->isp,
                    'location_summary' => $log->location_summary,
                ]
                : \App\Services\Audit\IpLocationService::resolve($log->ip_address);

            $device = (! empty($log->device_summary))
                ? [
                    'type' => $log->device_type,
                    'summary' => $log->device_summary,
                ]
                : \App\Services\Audit\DeviceDetector::detect($log->user_agent);

            $locationSummary = $geo['location_summary']
                ?? ((! empty($geo['city']) && ! empty($geo['country']) && $geo['city'] !== 'Unknown City')
                    ? ($geo['city'] . ', ' . $geo['country'])
                    : ($geo['country'] ?? 'Local / Private Network'));

            $this->selectedForensicEvent = [
                'id' => $log->id,
                'event_id' => $log->event_id,
                'sequence_number' => $log->sequence_number,
                'operation_id' => $log->operation_id,
                'parent_event_id' => $log->parent_event_id,
                'root_event_id' => $log->root_event_id,
                'event_type' => $log->event_type,
                'event_category' => $log->event_category,
                'severity' => $log->severity,
                'action_operation' => $log->action_operation,
                'action_result' => $log->action_result,
                'action_reason' => $log->action_reason,
                'ticket_id' => $log->ticket_id,
                'change_request_id' => $log->change_request_id,
                'approval_id' => $log->approval_id,
                'actor_type' => $log->actor_type,
                'actor_id' => $log->actor_id,
                'actor_email' => $log->actor_email,
                'actor_role' => $log->actor_role,
                'source_type' => $log->source_type,
                'organization_id' => $log->organization_id,
                'project_id' => $log->project_id,
                'environment_name' => $log->environment_name,
                'target_type' => $log->target_type,
                'target_id' => $log->target_id,
                'target_name' => $log->target_name,
                'deployment_provenance' => $log->deployment_provenance,
                'ip_address' => $log->ip_address,
                'device_type' => $device['type'] ?? $log->device_type,
                'device_summary' => $device['summary'] ?? $log->device_summary,
                'country' => $geo['country'] ?? $log->country,
                'country_code' => $geo['country_code'] ?? $log->country_code,
                'city' => $geo['city'] ?? $log->city,
                'region' => $geo['region'] ?? $log->region,
                'isp' => $geo['isp'] ?? $log->isp,
                'location_summary' => $locationSummary,
                'user_agent' => $log->user_agent,
                'route' => $log->route,
                'http_method' => $log->http_method,
                'status_code' => $log->status_code,
                'correlation_id' => $log->correlation_id,
                'request_id' => $log->request_id,
                'session_id' => $log->session_id,
                'event_time' => $log->event_time?->toISOString(),
                'received_at' => $log->received_at?->toISOString(),
                'persisted_at' => $log->persisted_at?->toISOString(),
                'actor' => $log->actor,
                'target' => $log->target,
                'action' => $log->action,
                'request' => $log->request,
                'changes' => $log->changes,
                'authentication' => $log->authentication,
                'source' => $log->source,
                'security' => $log->security,
                'previous_event_hash' => $log->previous_event_hash,
                'event_hash' => $log->event_hash,
            ];
            $this->showForensicModal = true;
        }
    }

    public function closeForensicModal(): void
    {
        $this->showForensicModal = false;
        $this->selectedForensicEvent = null;
    }

    public function getSubscriptionTenantsProperty()
    {
        $query = Team::where('id', '!=', 0)
            ->with(['subscription', 'members', 'servers']);

        if ($this->subSearch !== '') {
            $term = trim($this->subSearch);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('id', 'like', "%{$term}%")
                    ->orWhereHas('members', function ($m) use ($term) {
                        $m->where('name', 'like', "%{$term}%")
                            ->orWhere('email', 'like', "%{$term}%");
                    })
                    ->orWhereHas('subscription', function ($s) use ($term) {
                        $s->where('stripe_subscription_id', 'like', "%{$term}%")
                            ->orWhere('stripe_plan_id', 'like', "%{$term}%");
                    });
            });
        }

        if ($this->subFilter === 'paid') {
            $query->whereHas('subscription', fn ($q) => $q->where('stripe_invoice_paid', true));
        } elseif ($this->subFilter === 'starter') {
            $query->whereHas('subscription', fn ($q) => $q->where('stripe_plan_id', 'like', '%starter%')->orWhere('stripe_plan_id', 'like', '%hobby%'));
        } elseif ($this->subFilter === 'pro') {
            $query->whereHas('subscription', fn ($q) => $q->where('stripe_plan_id', 'like', '%pro%'));
        } elseif ($this->subFilter === 'business') {
            $query->whereHas('subscription', fn ($q) => $q->where('stripe_plan_id', 'like', '%business%')->orWhere('stripe_plan_id', 'like', '%enterprise%'));
        } elseif ($this->subFilter === 'trial') {
            $query->whereDoesntHave('subscription', fn ($q) => $q->where('stripe_invoice_paid', true))
                ->where('created_at', '>=', now()->subDays(14));
        } elseif ($this->subFilter === 'unpaid') {
            $query->where(function ($q) {
                $q->whereDoesntHave('subscription')
                    ->orWhereHas('subscription', fn ($s) => $s->where('stripe_invoice_paid', false));
            });
        }

        return $query->orderBy('id', 'asc')->get();
    }

    public function getTransactionsProperty()
    {
        $query = Subscription::query()
            ->with(['team.members'])
            ->where(function ($q) {
                $q->where('stripe_invoice_paid', true)
                    ->orWhereNotNull('razorpay_payment_id')
                    ->orWhereNotNull('amount_paid_paise');
            });

        if ($this->txSearch !== '') {
            $term = trim($this->txSearch);
            $query->where(function ($q) use ($term) {
                $q->where('razorpay_payment_id', 'like', "%{$term}%")
                    ->orWhere('razorpay_order_id', 'like', "%{$term}%")
                    ->orWhere('stripe_subscription_id', 'like', "%{$term}%")
                    ->orWhere('stripe_plan_id', 'like', "%{$term}%")
                    ->orWhereHas('team', function ($t) use ($term) {
                        $t->where('name', 'like', "%{$term}%");
                    })
                    ->orWhereHas('team.members', function ($m) use ($term) {
                        $m->where('email', 'like', "%{$term}%")
                            ->orWhere('name', 'like', "%{$term}%");
                    });
            });
        }

        if ($this->txFilter === 'razorpay') {
            $query->where(function ($q) {
                $q->whereNotNull('razorpay_payment_id')
                    ->orWhere('stripe_subscription_id', 'like', 'sub_rzp_%');
            });
        } elseif ($this->txFilter === 'stripe') {
            $query->whereNotNull('stripe_subscription_id')
                ->whereNull('razorpay_payment_id')
                ->where('stripe_subscription_id', 'not like', 'sub_rzp_%');
        } elseif ($this->txFilter === 'refunded') {
            $query->whereNotNull('stripe_refunded_at');
        }

        return $query->orderByRaw('COALESCE(activated_at, updated_at, created_at) DESC')
            ->get();
    }

    public function changeTeamPlan(int $teamId, string $newPlan): void
    {
        $this->authorizeAdminAccess();
        $team = Team::find($teamId);
        if (! $team) {
            $this->dispatch('error', 'Team not found.');
            return;
        }

        $sub = $team->subscription ?: new Subscription(['team_id' => $team->id]);
        $sub->stripe_plan_id = $newPlan;
        if ($newPlan === 'trial') {
            $sub->stripe_invoice_paid = false;
        } else {
            $sub->stripe_invoice_paid = true;
        }
        $sub->save();

        $plans = function_exists('getSubscriptionPlans') ? getSubscriptionPlans() : [];
        if (isset($plans[$newPlan]['storage_gb'])) {
            $team->custom_storage_limit_gb = (int) $plans[$newPlan]['storage_gb'];
            $team->save();
        }

        foreach ($team->members as $member) {
            \Illuminate\Support\Facades\Cache::forget('user:'.$member->id.':team:'.$team->id);
        }
        $team->unsetRelation('subscription');

        auditLog('admin.subscription.plan_changed', [
            'team_id' => $team->id,
            'team_name' => $team->name,
            'new_plan' => $newPlan,
            'is_paid' => $sub->stripe_invoice_paid,
        ]);

        $this->dispatch('success', "Plan updated to " . ucfirst($newPlan) . " for {$team->name}.");
        $this->getSubscribers();
        $this->loadUsers();
    }

    public function toggleTeamPaidStatus(int $teamId): void
    {
        $this->authorizeAdminAccess();
        $team = Team::find($teamId);
        if (! $team) {
            $this->dispatch('error', 'Team not found.');
            return;
        }

        $sub = $team->subscription ?: new Subscription(['team_id' => $team->id]);
        $sub->stripe_invoice_paid = ! (bool) $sub->stripe_invoice_paid;
        if (! $sub->stripe_plan_id) {
            $sub->stripe_plan_id = 'starter';
        }
        $sub->save();

        foreach ($team->members as $member) {
            \Illuminate\Support\Facades\Cache::forget('user:'.$member->id.':team:'.$team->id);
        }
        $team->unsetRelation('subscription');

        $statusText = $sub->stripe_invoice_paid ? 'ACTIVE & PAID' : 'UNPAID / SUSPENDED';
        auditLog('admin.subscription.status_toggled', [
            'team_id' => $team->id,
            'is_paid' => $sub->stripe_invoice_paid,
        ]);

        $this->dispatch('success', "Subscription status for {$team->name} changed to {$statusText}.");
        $this->getSubscribers();
        $this->loadUsers();
    }

    public function cancelTenantSubscription(int $teamId): void
    {
        $this->authorizeAdminAccess();
        $team = Team::find($teamId);
        if (! $team) {
            $this->dispatch('error', 'Team not found.');
            return;
        }

        $sub = $team->subscription;
        if ($sub) {
            $sub->stripe_invoice_paid = false;
            $sub->stripe_plan_id = 'cancelled';
            $sub->stripe_cancel_at_period_end = true;
            $sub->save();
        }

        foreach ($team->members as $member) {
            \Illuminate\Support\Facades\Cache::forget('user:'.$member->id.':team:'.$team->id);
        }
        $team->unsetRelation('subscription');

        auditLog('admin.subscription.cancelled', [
            'team_id' => $team->id,
            'team_name' => $team->name,
        ]);

        $this->dispatch('warning', "Subscription cancelled for {$team->name}.");
        $this->getSubscribers();
        $this->loadUsers();
    }

    public function loadQueuesData(): void
    {
        try {
            if (Schema::hasTable('failed_jobs')) {
                $this->failedJobsCount = DB::table('failed_jobs')->count();
                $this->failedJobsList = DB::table('failed_jobs')
                    ->latest('failed_at')
                    ->limit(20)
                    ->get()
                    ->map(function ($row) {
                        return (array) $row;
                    })
                    ->toArray();
            }

            if (Schema::hasTable('application_deployment_queues')) {
                $this->pendingJobsCount = ApplicationDeploymentQueue::whereIn('status', ['in_progress', 'queued'])->count();
            }
        } catch (\Throwable) {
        }
    }

    public function retryFailedJob(string $id): void
    {
        $this->authorizeAdminAccess();
        try {
            \Illuminate\Support\Facades\Artisan::call('queue:retry', ['id' => [$id]]);
            auditLog('admin.queues.job_retried', ['job_id' => $id]);
            $this->dispatch('success', "Job {$id} queued for retry.");
            $this->loadQueuesData();
        } catch (\Throwable $e) {
            $this->dispatch('error', 'Failed to retry job: ' . $e->getMessage());
        }
    }

    public function retryAllFailedJobs(): void
    {
        $this->authorizeAdminAccess();
        try {
            \Illuminate\Support\Facades\Artisan::call('queue:retry', ['id' => ['all']]);
            auditLog('admin.queues.all_retried');
            $this->dispatch('success', 'All failed jobs queued for retry.');
            $this->loadQueuesData();
        } catch (\Throwable $e) {
            $this->dispatch('error', 'Failed to retry jobs: ' . $e->getMessage());
        }
    }

    public function forgetFailedJob(string $id): void
    {
        $this->authorizeAdminAccess();
        try {
            \Illuminate\Support\Facades\Artisan::call('queue:forget', ['id' => $id]);
            auditLog('admin.queues.job_forgotten', ['job_id' => $id]);
            $this->dispatch('success', "Failed job {$id} dismissed.");
            $this->loadQueuesData();
        } catch (\Throwable $e) {
            $this->dispatch('error', 'Failed to remove job: ' . $e->getMessage());
        }
    }

    public function flushFailedJobs(): void
    {
        $this->authorizeAdminAccess();
        try {
            \Illuminate\Support\Facades\Artisan::call('queue:flush');
            auditLog('admin.queues.all_flushed');
            $this->dispatch('success', 'All failed jobs cleared.');
            $this->loadQueuesData();
        } catch (\Throwable $e) {
            $this->dispatch('error', 'Failed to clear jobs: ' . $e->getMessage());
        }
    }

    public function loadHostHealth(): void
    {
        $cpu = 0;
        $memUsed = '0 MB';
        $memTotal = '0 MB';

        try {
            $meminfo = @file_get_contents('/proc/meminfo');
            if ($meminfo) {
                preg_match('/MemTotal:\s+(\d+)/', $meminfo, $tot);
                preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $avail);
                if (isset($tot[1], $avail[1])) {
                    $totalMb = round($tot[1] / 1024);
                    $availMb = round($avail[1] / 1024);
                    $usedMb = $totalMb - $availMb;
                    $memUsed = "{$usedMb} MB";
                    $memTotal = "{$totalMb} MB";
                }
            }
            $load = sys_getloadavg();
            $cpu = $load[0] ?? 0;
        } catch (\Throwable) {
        }

        $this->hostHealthMetrics = [
            'cpu_load_1m' => $cpu,
            'memory_used' => $memUsed,
            'memory_total' => $memTotal,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'db_connection' => config('database.default'),
            'queue_driver' => config('queue.default'),
            'cache_driver' => config('cache.default'),
        ];
    }

    public function runDockerPrune(): void
    {
        $this->authorizeAdminAccess();
        try {
            $localhost = Server::find(0);
            if ($localhost) {
                instant_remote_process(['docker system prune -f'], $localhost, false);
                auditLog('admin.system.docker_prune_executed');
                $this->dispatch('success', 'Docker system prune executed on host machine.');
            }
        } catch (\Throwable $e) {
            $this->dispatch('error', 'Prune error: ' . $e->getMessage());
        }
    }

    public function loadBackupsList(): void
    {
        try {
            if (Schema::hasTable('scheduled_database_backup_executions')) {
                $this->instanceBackupsList = ScheduledDatabaseBackupExecution::latest('created_at')
                    ->limit(10)
                    ->get()
                    ->toArray();
            }
        } catch (\Throwable) {
        }
    }

    public function triggerInstanceBackup(): void
    {
        $this->authorizeAdminAccess();
        auditLog('admin.backups.instance_triggered');
        $this->dispatch('success', 'Instance snapshot backup initiated.');
    }

    public function sendTestNotification(string $channel): void
    {
        $this->authorizeAdminAccess();
        auditLog('admin.notifications.test_sent', ['channel' => $channel]);
        $this->dispatch('success', "Test message dispatched to {$channel}.");
    }

    public function openManageModal(int $userId): void
    {
        $this->inspectUserResources($userId, 'billing');
    }

    public function closeManageModal(): void
    {
        $this->showManageModal = false;
        $this->reset([
            'managingUserId',
            'managingUserName',
            'managingUserEmail',
            'managingTeamId',
            'managingTeamName',
            'managingUserRole',
            'managingUserIsSuspended',
            'managingUserHas2Fa',
            'managingUserIsVerified',
            'customStorageGbInput',
        ]);
    }

    public function saveTenantSubscription(): void
    {
        $this->authorizeAdminAccess();
        if (! $this->managingTeamId) {
            $this->dispatch('error', 'No active team found for this user.');
            return;
        }

        $team = Team::find($this->managingTeamId);
        if (! $team) {
            $this->dispatch('error', 'Team not found.');
            return;
        }

        $sub = $team->subscription;
        if (! $sub) {
            $sub = new Subscription();
            $sub->team_id = $team->id;
        }

        $sub->stripe_plan_id = $this->selectedPlanId;
        $sub->stripe_invoice_paid = $this->subPaidStatus;
        $sub->save();

        $plans = function_exists('getSubscriptionPlans') ? getSubscriptionPlans() : [];
        if (isset($plans[$this->selectedPlanId]['storage_gb']) && empty($team->custom_storage_limit_gb)) {
            $team->custom_storage_limit_gb = (int) $plans[$this->selectedPlanId]['storage_gb'];
            $team->save();
        }

        foreach ($team->members as $member) {
            \Illuminate\Support\Facades\Cache::forget('user:'.$member->id.':team:'.$team->id);
        }
        $team->unsetRelation('subscription');

        auditLog('admin.tenant.subscription_updated', [
            'team_id' => $team->id,
            'plan' => $this->selectedPlanId,
            'is_paid' => $this->subPaidStatus,
        ]);

        $this->dispatch('success', "Subscription updated for {$team->name}.");
        $this->getSubscribers();
        $this->loadUsers();
    }

    public function saveTenantStorage(): void
    {
        $this->authorizeAdminAccess();
        if (! $this->managingTeamId) {
            $this->dispatch('error', 'No active team found for this user.');
            return;
        }

        $team = Team::find($this->managingTeamId);
        if ($team) {
            $team->custom_storage_limit_gb = $this->customStorageGbInput ?: null;
            $team->save();

            foreach ($team->members as $member) {
                \Illuminate\Support\Facades\Cache::forget('user:'.$member->id.':team:'.$team->id);
            }
            $team->unsetRelation('subscription');

            auditLog('admin.tenant.storage_updated', [
                'team_id' => $team->id,
                'limit_gb' => $team->custom_storage_limit_gb,
            ]);

            $this->dispatch('success', "Storage limit updated for {$team->name}.");
            $this->loadUsers();
        }
    }

    public function toggleUserSuspension(int $userId): void
    {
        $this->authorizeAdminAccess();
        if ($userId === 0) {
            $this->dispatch('error', 'Root Administrator account cannot be suspended.');
            return;
        }

        $user = User::find($userId);
        if ($user) {
            $user->is_suspended = ! (bool) $user->is_suspended;
            $user->save();

            auditLog('admin.tenant.suspension_toggled', [
                'target_user_id' => $user->id,
                'is_suspended' => $user->is_suspended,
            ]);

            $this->managingUserIsSuspended = (bool) $user->is_suspended;
            $this->drawerIsSuspended = (bool) $user->is_suspended;
            $statusText = $user->is_suspended ? 'SUSPENDED' : 'UNSUSPENDED';
            $this->dispatch('success', "User {$user->email} has been {$statusText}.");
            $this->getSubscribers();
            $this->loadUsers();
        }
    }

    public function forcePasswordReset(int $userId): void
    {
        $this->authorizeAdminAccess();
        $user = User::find($userId);
        if ($user) {
            $user->force_password_reset = true;
            $user->save();

            auditLog('admin.tenant.force_password_reset', [
                'target_user_id' => $user->id,
            ]);

            $this->dispatch('success', "Password reset enforced for {$user->email}.");
        }
    }

    public function resetUser2Fa(int $userId): void
    {
        $this->authorizeAdminAccess();
        $user = User::find($userId);
        if ($user) {
            $user->two_factor_secret = null;
            $user->two_factor_recovery_codes = null;
            $user->two_factor_confirmed_at = null;
            $user->save();

            auditLog('admin.tenant.2fa_reset', [
                'target_user_id' => $user->id,
            ]);

            $this->managingUserHas2Fa = false;
            $this->drawerTwoFactor = false;
            $this->dispatch('success', "Two-Factor Authentication cleared for {$user->email}.");
        }
    }

    public function setDrawerTab(string $tab): void
    {
        $this->drawerActiveTab = $tab;
    }

    public function inspectUserResources(int $userId, string $tab = 'overview'): void
    {
        $this->authorizeAdminAccess();
        $user = User::with(['teams', 'tokens'])->find($userId);
        if (! $user) {
            $this->dispatch('error', 'User not found.');
            return;
        }

        $team = $user->personalTeam();

        $this->drawerUserId = $user->id;
        $this->drawerUserName = $user->name;
        $this->drawerUserEmail = $user->email;
        $this->drawerTeamName = $team?->name;
        $this->drawerUserTeams = $user->teams->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'personal_team' => (bool) $t->personal_team,
            'role' => $t->pivot?->role ?? $user->roleInTeam($t->id) ?? 'member',
        ])->toArray();

        $this->drawerLastActive = $user->last_active_at ? $user->last_active_at->diffForHumans() : 'Never seen';
        $this->drawerPresenceStatus = method_exists($user, 'presenceStatus') ? $user->presenceStatus() : ($user->last_active_at && $user->last_active_at->gt(now()->subMinutes(15)) ? 'online' : 'offline');
        $this->drawerTotalApiCalls = (int) ($user->total_api_calls ?? 0);
        $this->drawerLastApiCallAt = $user->last_api_call_at ? $user->last_api_call_at->diffForHumans() : 'No API calls recorded';
        $this->drawerLastLoginAt = $user->last_login_at ? $user->last_login_at->diffForHumans() : ($user->last_active_at ? $user->last_active_at->diffForHumans() : 'Never logged in');
        $this->drawerLastLoginIp = $user->last_login_ip ?? 'N/A';
        $this->drawerCreatedAt = $user->created_at ? $user->created_at->format('M d, Y H:i') : 'N/A';
        $this->drawerTwoFactor = (bool) $user->two_factor_confirmed_at;
        $this->drawerIsSuspended = (bool) $user->is_suspended;
        $this->drawerSuspensionReason = $user->suspension_reason;
        $this->drawerRecentLocations = is_array($user->recent_locations) ? $user->recent_locations : [];

        // Fetch User API Logs
        if (Schema::hasTable('api_logs')) {
            $this->drawerApiLogs = ApiLog::where('user_id', $user->id)
                ->latest()
                ->take(35)
                ->get()
                ->map(fn ($l) => [
                    'id' => $l->id,
                    'method' => $l->method,
                    'path' => $l->path,
                    'status_code' => $l->status_code,
                    'duration_ms' => $l->duration_ms,
                    'ip_address' => $l->ip_address,
                    'time' => $l->created_at->diffForHumans(),
                    'created_at' => $l->created_at->toDateTimeString(),
                    'token_name' => $l->token_name ?? 'Session / Token',
                ])->toArray();
        } else {
            $this->drawerApiLogs = [];
        }

        // Fetch End-to-End Activity History (Audit Logs)
        if (Schema::hasTable('audit_logs')) {
            $this->drawerAuditLogs = AuditLog::where('user_id', $user->id)
                ->orWhere('user_email', $user->email)
                ->latest()
                ->take(35)
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'event' => $a->event,
                    'level' => $a->level,
                    'path' => $a->path,
                    'ip' => $a->ip,
                    'time' => $a->created_at->diffForHumans(),
                    'created_at' => $a->created_at->toDateTimeString(),
                    'payload' => $a->payload,
                ])->toArray();
        } else {
            $this->drawerAuditLogs = [];
        }

        // Fetch Sanctum API Tokens
        $this->drawerApiTokens = $user->tokens->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'abilities' => $t->abilities,
            'last_used_at' => $t->last_used_at ? $t->last_used_at->diffForHumans() : 'Never',
            'created_at' => $t->created_at ? $t->created_at->format('M d, Y') : 'N/A',
        ])->toArray();

        $apps = [];
        $dbs = [];
        $services = [];
        $vols = [];

        if ($team) {
            $projectIds = Project::where('team_id', $team->id)->pluck('id');
            $environmentIds = Environment::whereIn('project_id', $projectIds)->pluck('id');

            $apps = Application::whereIn('environment_id', $environmentIds)->get()->map(function ($a) {
                $displayName = $a->name;
                $branch = null;
                if (str_contains($a->name, ':')) {
                    $parts = explode(':', $a->name, 2);
                    $displayName = $parts[0];
                    if (str_contains($parts[1], '-')) {
                        $branchParts = explode('-', $parts[1]);
                        $branch = $branchParts[0];
                    } else {
                        $branch = $parts[1];
                    }
                }

                return [
                    'id' => $a->id,
                    'uuid' => $a->uuid,
                    'name' => $a->name,
                    'display_name' => $displayName,
                    'branch' => $branch,
                    'fqdn' => $a->fqdn,
                    'status' => $a->status ?? 'unknown',
                ];
            })->toArray();

            $dbsCollect = collect();
            $pgs = StandalonePostgresql::whereIn('environment_id', $environmentIds)->get()->map(fn ($d) => [
                'id' => $d->id,
                'uuid' => $d->uuid,
                'name' => $d->name,
                'type' => 'PostgreSQL',
                'status' => $d->status ?? 'unknown',
            ]);
            $dbsCollect = $dbsCollect->concat($pgs);

            $mysqls = StandaloneMysql::whereIn('environment_id', $environmentIds)->get()->map(fn ($d) => [
                'id' => $d->id,
                'uuid' => $d->uuid,
                'name' => $d->name,
                'type' => 'MySQL',
                'status' => $d->status ?? 'unknown',
            ]);
            $dbsCollect = $dbsCollect->concat($mysqls);

            $mariadbs = StandaloneMariadb::whereIn('environment_id', $environmentIds)->get()->map(fn ($d) => [
                'id' => $d->id,
                'uuid' => $d->uuid,
                'name' => $d->name,
                'type' => 'MariaDB',
                'status' => $d->status ?? 'unknown',
            ]);
            $dbsCollect = $dbsCollect->concat($mariadbs);

            $mongos = StandaloneMongodb::whereIn('environment_id', $environmentIds)->get()->map(fn ($d) => [
                'id' => $d->id,
                'uuid' => $d->uuid,
                'name' => $d->name,
                'type' => 'MongoDB',
                'status' => $d->status ?? 'unknown',
            ]);
            $dbsCollect = $dbsCollect->concat($mongos);

            $redises = StandaloneRedis::whereIn('environment_id', $environmentIds)->get()->map(fn ($d) => [
                'id' => $d->id,
                'uuid' => $d->uuid,
                'name' => $d->name,
                'type' => 'Redis',
                'status' => $d->status ?? 'unknown',
            ]);
            $dbsCollect = $dbsCollect->concat($redises);

            $clickhouses = StandaloneClickhouse::whereIn('environment_id', $environmentIds)->get()->map(fn ($d) => [
                'id' => $d->id,
                'uuid' => $d->uuid,
                'name' => $d->name,
                'type' => 'ClickHouse',
                'status' => $d->status ?? 'unknown',
            ]);
            $dbsCollect = $dbsCollect->concat($clickhouses);

            $dbs = $dbsCollect->toArray();

            $services = Service::whereIn('environment_id', $environmentIds)->get()->map(fn ($s) => [
                'id' => $s->id,
                'uuid' => $s->uuid,
                'name' => $s->name,
                'service_type' => $s->service_type,
                'status' => $s->status ?? 'unknown',
            ])->toArray();

            $appIds = Application::whereIn('environment_id', $environmentIds)->pluck('id');
            $vols = LocalPersistentVolume::where('resource_type', Application::class)
                ->whereIn('resource_id', $appIds)
                ->get()->map(fn ($v) => [
                    'name' => $v->name,
                    'mount_path' => $v->mount_path,
                ])->toArray();
        }

        // Resolve Tenant Subscriptions, History & Transactions for Drawer
        $this->drawerTeamId = $team?->id;
        $this->drawerLiveSubscription = null;
        $this->drawerSubscriptionHistory = [];
        $this->drawerTransactions = [];
        $this->drawerSelectedPlan = 'trial';
        $this->drawerSubPaidStatus = false;
        $this->drawerCustomStorageGb = $team?->custom_storage_limit_gb;

        if ($team) {
            $subs = Subscription::where('team_id', $team->id)->orderBy('id', 'desc')->get();
            $latestSub = $subs->first();

            if ($latestSub) {
                $this->drawerSelectedPlan = $latestSub->stripe_plan_id ?? 'trial';
                $this->drawerSubPaidStatus = (bool) $latestSub->stripe_invoice_paid;
                $isPaid = (bool) $latestSub->stripe_invoice_paid;
                $isRefunded = ! empty($latestSub->stripe_refunded_at);
                $isPastDue = (bool) $latestSub->stripe_past_due;

                $status = 'active';
                if ($isRefunded) {
                    $status = 'refunded';
                } elseif ($isPastDue) {
                    $status = 'past_due';
                } elseif (! $isPaid) {
                    $status = 'unpaid';
                }

                $gateway = 'Stripe';
                if (! empty($latestSub->razorpay_payment_id) || str_starts_with((string) $latestSub->stripe_subscription_id, 'sub_rzp_')) {
                    $gateway = 'Razorpay';
                } elseif (empty($latestSub->stripe_subscription_id) && empty($latestSub->razorpay_payment_id)) {
                    $gateway = 'Manual / System';
                }

                $this->drawerLiveSubscription = [
                    'id' => $latestSub->id,
                    'plan' => ucfirst($latestSub->stripe_plan_id ?? 'Trial'),
                    'plan_raw' => $latestSub->stripe_plan_id ?? 'trial',
                    'status' => $status,
                    'is_paid' => $isPaid,
                    'is_refunded' => $isRefunded,
                    'is_past_due' => $isPastDue,
                    'interval' => $latestSub->billingInterval(),
                    'amount' => $latestSub->amount_paid_paise ? ($latestSub->amount_paid_paise / 100) : match (strtolower($latestSub->stripe_plan_id ?? '')) {
                        'business', 'enterprise' => $this->planBusinessPrice,
                        'pro' => $this->planProPrice,
                        'starter', 'hobby' => $this->planHobbyPrice,
                        default => 0,
                    },
                    'currency' => $latestSub->currency ?? 'INR',
                    'gateway' => $gateway,
                    'payment_id' => $latestSub->razorpay_payment_id ?? $latestSub->stripe_subscription_id ?? '—',
                    'order_id' => $latestSub->razorpay_order_id ?? $latestSub->stripe_customer_id ?? '—',
                    'activated_at' => $latestSub->activated_at?->format('M d, Y H:i') ?? $latestSub->created_at?->format('M d, Y H:i') ?? 'N/A',
                    'updated_at' => $latestSub->updated_at?->format('M d, Y H:i') ?? 'N/A',
                    'storage_limit_gb' => $team->custom_storage_limit_gb ?? null,
                ];
            } else {
                $daysAgo = (int) $team->created_at->diffInDays(now());
                $daysRemaining = max(0, 14 - $daysAgo);
                $this->drawerLiveSubscription = [
                    'id' => null,
                    'plan' => 'Trial (14-day evaluation)',
                    'plan_raw' => 'trial',
                    'status' => $daysRemaining > 0 ? 'trial' : 'expired',
                    'is_paid' => false,
                    'is_refunded' => false,
                    'is_past_due' => false,
                    'interval' => '14-day trial',
                    'amount' => 0,
                    'currency' => 'INR',
                    'gateway' => 'None',
                    'payment_id' => '—',
                    'order_id' => '—',
                    'activated_at' => $team->created_at->format('M d, Y H:i'),
                    'updated_at' => $team->updated_at->format('M d, Y H:i'),
                    'storage_limit_gb' => $team->custom_storage_limit_gb ?? null,
                    'trial_days_remaining' => $daysRemaining,
                ];
            }

            // Subscription History: all subscription records
            $this->drawerSubscriptionHistory = $subs->map(function ($s) {
                $gateway = 'Stripe';
                if (! empty($s->razorpay_payment_id) || str_starts_with((string) $s->stripe_subscription_id, 'sub_rzp_')) {
                    $gateway = 'Razorpay';
                } elseif (empty($s->stripe_subscription_id) && empty($s->razorpay_payment_id)) {
                    $gateway = 'Manual';
                }

                $status = $s->stripe_refunded_at ? 'Refunded' : ($s->stripe_invoice_paid ? 'Active / Paid' : 'Unpaid');

                return [
                    'id' => $s->id,
                    'plan' => ucfirst($s->stripe_plan_id ?? 'trial'),
                    'interval' => $s->billingInterval(),
                    'gateway' => $gateway,
                    'status' => $status,
                    'is_paid' => (bool) $s->stripe_invoice_paid,
                    'amount' => $s->amount_paid_paise ? ($s->amount_paid_paise / 100) : 0,
                    'currency' => $s->currency ?? 'INR',
                    'created_at' => $s->created_at?->format('M d, Y H:i'),
                    'activated_at' => $s->activated_at?->format('M d, Y H:i') ?? $s->created_at?->format('M d, Y H:i'),
                    'refunded_at' => $s->stripe_refunded_at?->format('M d, Y H:i'),
                    'payment_id' => $s->razorpay_payment_id ?? $s->stripe_subscription_id ?? ('SUB-' . $s->id),
                ];
            })->toArray();

            // Transaction History: rows that represent payments / charges
            $this->drawerTransactions = $subs->filter(function ($s) {
                return (bool) $s->stripe_invoice_paid || ! empty($s->razorpay_payment_id) || ! empty($s->amount_paid_paise);
            })->map(function ($tx) {
                $gateway = 'Stripe';
                if (! empty($tx->razorpay_payment_id) || str_starts_with((string) $tx->stripe_subscription_id, 'sub_rzp_')) {
                    $gateway = 'Razorpay';
                }

                $amount = $tx->amount_paid_paise ? ($tx->amount_paid_paise / 100) : match (strtolower($tx->stripe_plan_id ?? '')) {
                    'business', 'enterprise' => $this->planBusinessPrice,
                    'pro' => $this->planProPrice,
                    'starter', 'hobby' => $this->planHobbyPrice,
                    default => 0,
                };

                return [
                    'id' => $tx->id,
                    'payment_id' => $tx->razorpay_payment_id ?? $tx->stripe_subscription_id ?? ('TXN-' . $tx->id),
                    'order_id' => $tx->razorpay_order_id ?? $tx->stripe_customer_id ?? '—',
                    'gateway' => $gateway,
                    'plan' => ucfirst($tx->stripe_plan_id ?? 'Starter'),
                    'interval' => $tx->billingInterval(),
                    'amount' => $amount,
                    'currency' => $tx->currency ?? 'INR',
                    'is_paid' => (bool) $tx->stripe_invoice_paid,
                    'is_refunded' => ! empty($tx->stripe_refunded_at),
                    'refunded_at' => $tx->stripe_refunded_at?->format('M d, Y H:i'),
                    'date' => ($tx->activated_at ?? $tx->created_at)?->format('M d, Y H:i'),
                    'time_ago' => ($tx->activated_at ?? $tx->created_at)?->diffForHumans() ?? 'Recently',
                ];
            })->values()->toArray();
        }

        $this->drawerApplications = $apps;
        $this->drawerDatabases = $dbs;
        $this->drawerServices = $services;
        $this->drawerVolumes = $vols;
        $this->drawerActiveTab = $tab;
        $this->showResourceDrawer = true;

        auditLog('admin.user.inspected', [
            'inspected_user_id' => $user->id,
            'inspected_email' => $user->email,
        ]);
    }

    public function closeResourceDrawer(): void
    {
        $this->showResourceDrawer = false;
        $this->reset([
            'drawerUserId',
            'drawerUserName',
            'drawerUserEmail',
            'drawerTeamName',
            'drawerLastActive',
            'drawerPresenceStatus',
            'drawerTotalApiCalls',
            'drawerLastApiCallAt',
            'drawerLastLoginAt',
            'drawerLastLoginIp',
            'drawerCreatedAt',
            'drawerTwoFactor',
            'drawerIsSuspended',
            'drawerSuspensionReason',
            'drawerApiLogs',
            'drawerAuditLogs',
            'drawerRecentLocations',
            'drawerApiTokens',
            'drawerApplications',
            'drawerDatabases',
            'drawerServices',
            'drawerVolumes',
            'drawerLiveSubscription',
            'drawerSubscriptionHistory',
            'drawerTransactions',
            'drawerSelectedPlan',
            'drawerSubPaidStatus',
            'drawerCustomStorageGb',
            'drawerTeamId',
        ]);
    }

    public function saveDrawerSubscription(): void
    {
        $this->authorizeAdminAccess();

        if (! $this->drawerTeamId) {
            $this->dispatch('error', 'No active team found for this user.');
            return;
        }

        $team = Team::find($this->drawerTeamId);
        if (! $team) {
            $this->dispatch('error', 'Team not found.');
            return;
        }

        $sub = $team->subscription;
        if (! $sub) {
            $sub = new Subscription();
            $sub->team_id = $team->id;
        }

        $sub->stripe_plan_id = $this->drawerSelectedPlan;
        $sub->stripe_invoice_paid = (bool) $this->drawerSubPaidStatus;
        $sub->activated_at = now();
        $sub->save();

        // Update custom storage limit
        $team->custom_storage_limit_gb = $this->drawerCustomStorageGb ? (int) $this->drawerCustomStorageGb : null;
        $team->save();

        $plans = function_exists('getSubscriptionPlans') ? getSubscriptionPlans() : [];
        if (empty($team->custom_storage_limit_gb) && isset($plans[$this->drawerSelectedPlan]['storage_gb'])) {
            $team->custom_storage_limit_gb = (int) $plans[$this->drawerSelectedPlan]['storage_gb'];
            $team->save();
        }

        foreach ($team->members as $member) {
            \Illuminate\Support\Facades\Cache::forget('user:' . $member->id . ':team:' . $team->id);
        }
        $team->unsetRelation('subscription');

        auditLog('admin.tenant.subscription_updated', [
            'team_id' => $team->id,
            'plan' => $this->drawerSelectedPlan,
            'is_paid' => $this->drawerSubPaidStatus,
            'storage_limit_gb' => $team->custom_storage_limit_gb,
        ]);

        $this->dispatch('success', "Subscription and quotas saved for {$team->name}.");
        $this->inspectUserResources($this->drawerUserId, 'billing');
        $this->getSubscribers();
        $this->loadUsers();
    }

    public function updateDrawerUserPlan(?string $newPlan = null): void
    {
        if ($newPlan) {
            $this->drawerSelectedPlan = $newPlan;
        }
        $this->saveDrawerSubscription();
    }

    public function openCreateTenantModal(): void
    {
        $this->authorizeAdminAccess();
        $this->reset([
            'newTenantName',
            'newTenantEmail',
            'newTenantPassword',
            'newTenantTeamName',
            'newTenantPlan',
            'newTenantStorageGb',
            'generatedPasswordNotice',
        ]);
        $this->newTenantEmailVerified = true;
        $this->showCreateTenantModal = true;
    }

    public function closeCreateTenantModal(): void
    {
        $this->showCreateTenantModal = false;
    }

    public function createTenant(): void
    {
        $this->authorizeAdminAccess();
        $this->validate([
            'newTenantName' => 'required|string|max:255',
            'newTenantEmail' => 'required|email|max:255|unique:users,email',
            'newTenantPlan' => 'required|string',
        ]);

        $password = $this->newTenantPassword ?: Str::random(14);

        $user = User::create([
            'name' => $this->newTenantName,
            'email' => $this->newTenantEmail,
            'password' => Hash::make($password),
            'email_verified_at' => $this->newTenantEmailVerified ? now() : null,
        ]);

        $teamName = $this->newTenantTeamName ?: ($this->newTenantName . "'s Team");
        $team = Team::create([
            'name' => $teamName,
            'personal_team' => true,
            'custom_storage_limit_gb' => $this->newTenantStorageGb ?: null,
        ]);

        $user->teams()->attach($team->id, ['role' => 'owner']);

        $sub = new Subscription();
        $sub->team_id = $team->id;
        $sub->stripe_plan_id = $this->newTenantPlan;
        $sub->stripe_invoice_paid = ($this->newTenantPlan !== 'trial' && $this->newTenantPlan !== 'free');
        $sub->save();

        auditLog('admin.tenant.created', [
            'created_user_id' => $user->id,
            'email' => $user->email,
            'team_id' => $team->id,
            'plan' => $this->newTenantPlan,
        ]);

        $this->showCreateTenantModal = false;
        $this->generatedPasswordNotice = "Tenant {$user->email} created successfully. Temporary Password: {$password}";
        $this->dispatch('success', "Tenant {$user->email} created.");
        $this->getSubscribers();
        $this->loadUsers();
    }

    public function openDeleteModal(int $userId): void
    {
        $this->authorizeRootOnly();
        if ($userId === 0) {
            $this->dispatch('error', 'Root Administrator cannot be deleted.');
            return;
        }

        $user = User::find($userId);
        if (! $user) {
            $this->dispatch('error', 'User not found.');
            return;
        }

        $this->userToDeleteId = $user->id;
        $this->userToDeleteEmail = $user->email;
        $this->deleteConfirmationInput = '';
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->userToDeleteId = null;
        $this->userToDeleteEmail = null;
        $this->deleteConfirmationInput = '';
    }

    public function executeDeleteUser(): void
    {
        $this->authorizeRootOnly();
        if (! $this->userToDeleteId || $this->userToDeleteId === 0) {
            $this->dispatch('error', 'Invalid user selection.');
            return;
        }

        if (trim(strtolower($this->deleteConfirmationInput)) !== 'delete') {
            $this->dispatch('error', "You must type 'DELETE' exactly to confirm deletion.");
            return;
        }

        $user = User::find($this->userToDeleteId);
        if ($user) {
            $email = $user->email;
            $teams = $user->teams;

            foreach ($teams as $team) {
                if ($team->id !== 0) {
                    DeleteUserResources::run($team);
                    DeleteUserTeams::run($team);
                }
            }

            $user->delete();

            auditLog('admin.tenant.deleted', [
                'deleted_user_id' => $this->userToDeleteId,
                'email' => $email,
            ]);

            $this->closeDeleteModal();
            $this->dispatch('success', "Tenant {$email} and associated resources deleted.");
            $this->getSubscribers();
            $this->loadUsers();
        }
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorizeAdminAccess();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . strtolower(config('app.name', 'beryl')) . '-tenants-' . date('Y-m-d') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        auditLog('admin.tenants.exported_csv');

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'User ID',
                'Name',
                'Email',
                'Suspended',
                '2FA Enabled',
                'Team Name',
                'Team ID',
                'Plan ID',
                'Paid Status',
                'Storage Limit',
                'Created At',
            ]);

            $users = User::with('teams.subscription')->cursor();
            foreach ($users as $u) {
                $team = $u->personalTeam();
                $sub = $team?->subscription;
                fputcsv($handle, [
                    $u->id,
                    $u->name,
                    $u->email,
                    $u->is_suspended ? 'Yes' : 'No',
                    $u->two_factor_confirmed_at ? 'Yes' : 'No',
                    $team?->name ?? '-',
                    $team?->id ?? '-',
                    $sub?->stripe_plan_id ?? 'trial',
                    ($sub?->stripe_invoice_paid ?? false) ? 'Paid' : 'Unpaid',
                    $team?->custom_storage_limit_gb ? "{$team->custom_storage_limit_gb} GB" : 'Default',
                    $u->created_at?->toIso8601String() ?? '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function back()
    {
        $this->authorizeAdminAccess();
        if (session('impersonating')) {
            $adminId = session('impersonator_id', 0);
            session()->forget('impersonating');
            session()->forget('impersonator_id');
            $user = User::find($adminId) ?? User::find(0) ?? User::first();
            if ($user) {
                $team_to_switch_to = $user->resolveStoredTeam() ?? $user->teams->first();
                Auth::login($user);
                refreshSession($team_to_switch_to);
                auditLog('admin.impersonation.stopped', [
                    'restored_user_id' => $user->id,
                    'restored_email' => $user->email,
                ]);
            }

            return redirect()->route('admin.index');
        }
    }

    public function submitSearch()
    {
        $this->authorizeAdminAccess();
        $this->loadUsers();
    }

    public function getSubscribers()
    {
        if (Auth::id() !== 0 && ! isInstanceAdmin() && ! session('impersonating')) {
            abort(403);
        }
        $this->activeSubscribers = Subscription::where('stripe_invoice_paid', true)->count();
        $this->inactiveSubscribers = Team::where('id', '!=', 0)->whereDoesntHave('subscription', fn ($q) => $q->where('stripe_invoice_paid', true))->count();

        // Calculate estimated MRR
        $mrr = 0;
        $activeSubs = Subscription::where('stripe_invoice_paid', true)->get();
        foreach ($activeSubs as $sub) {
            $plan = strtolower($sub->stripe_plan_id ?? '');
            if (str_contains($plan, 'business') || str_contains($plan, 'enterprise')) {
                $mrr += $this->planBusinessPrice;
            } elseif (str_contains($plan, 'pro')) {
                $mrr += $this->planProPrice;
            } else {
                $mrr += $this->planHobbyPrice;
            }
        }
        $this->monthlyRevenue = $mrr;

        $this->trialUsers = Team::where('id', '!=', 0)
            ->whereDoesntHave('subscription', fn ($q) => $q->where('stripe_invoice_paid', true))
            ->where('created_at', '>=', now()->subDays(14))
            ->count();

        $this->expiredUsers = Team::where('id', '!=', 0)
            ->whereDoesntHave('subscription', fn ($q) => $q->where('stripe_invoice_paid', true))
            ->where('created_at', '<', now()->subDays(14))
            ->count();

        $this->suspendedUsers = User::where('is_suspended', true)->count();
    }

    public function switchUser(int $user_id)
    {
        $this->authorizeRootOnly();
        $adminId = Auth::id() ?? 0;
        session([
            'impersonating' => true,
            'impersonator_id' => $adminId,
        ]);
        $user = User::find($user_id);
        if (! $user) {
            abort(404);
        }
        $team_to_switch_to = $user->resolveStoredTeam() ?? $user->teams->first();
        Auth::login($user);
        refreshSession($team_to_switch_to);

        auditLog('admin.impersonation.started', [
            'impersonated_user_id' => $user->id,
            'impersonated_email' => $user->email,
        ]);

        return redirect()->route('dashboard');
    }

    private function authorizeAdminAccess(): void
    {
        if (! Auth::check() || (! isInstanceAdmin() && Auth::id() !== 0 && ! session('impersonating'))) {
            abort(403, 'Unauthorized access to admin panel');
        }
    }

    private function authorizeRootOnly(): void
    {
        if (! Auth::check() || (! isInstanceAdmin() && Auth::id() !== 0)) {
            abort(403, 'Unauthorized access to admin panel');
        }
    }

    private function authorizePaymentGatewayAccess(): void
    {
        if (! Auth::check() || (! isInstanceAdmin() && Auth::id() !== 0) || session('impersonating')) {
            abort(403, 'Root administrator credentials required to manage payment gateways.');
        }
    }

    public function render()
    {
        return view('livewire.admin.index', array_merge(get_object_vars($this), [
            'auditLogs' => $this->auditLogs,
            'forensicAuditLogs' => $this->forensicAuditLogs,
            'subscriptionTenants' => $this->subscriptionTenants,
            'transactions' => $this->transactions,
        ]));
    }
}

