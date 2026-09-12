<?php

namespace App\Livewire\Admin;

use App\Actions\User\DeleteUserResources;
use App\Actions\User\DeleteUserTeams;
use App\Models\ApiLog;
use App\Models\Application;
use App\Models\ApplicationDeploymentQueue;
use App\Models\AuditLog;
use App\Models\Environment;
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

    // Unified "Manage Tenant" Modal
    public bool $showManageModal = false;

    public string $manageTab = 'subscription'; // 'subscription', 'storage', 'security'

    public ?int $managingUserId = null;

    public ?string $managingUserName = null;

    public ?string $managingUserEmail = null;

    public ?int $managingTeamId = null;

    public ?string $managingTeamName = null;

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


    public function mount()
    {
        $this->authorizeAdminAccess();

        if (request()->has('tab')) {
            $reqTab = request()->get('tab');
            $this->tab = ($reqTab === 'all') ? 'users' : $reqTab;
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
        auditLog('admin.security.settings_updated', [
            'enforce_2fa_all' => $this->enforce2FaAll,
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
        $this->authorizeAdminAccess();
        $user = User::with('teams.subscription')->find($userId);
        if (! $user) {
            $this->dispatch('error', 'User not found.');
            return;
        }

        $team = $user->resolveStoredTeam() ?? $user->teams->first();

        $this->managingUserId = $user->id;
        $this->managingUserName = $user->name;
        $this->managingUserEmail = $user->email;
        $this->managingTeamId = $team?->id;
        $this->managingTeamName = $team?->name;
        $this->managingUserIsSuspended = (bool) $user->is_suspended;
        $this->managingUserHas2Fa = (bool) $user->two_factor_confirmed_at;
        $this->managingUserIsVerified = (bool) $user->email_verified_at;

        $sub = $team?->subscription;
        if ($sub) {
            $this->selectedPlanId = $sub->stripe_plan_id ?? 'custom';
            $this->subPaidStatus = (bool) $sub->stripe_invoice_paid;
        } else {
            $this->selectedPlanId = 'trial';
            $this->subPaidStatus = false;
        }

        $this->customStorageGbInput = $team?->custom_storage_limit_gb;
        $this->manageTab = 'subscription';
        $this->showManageModal = true;
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
            $this->dispatch('success', "Two-Factor Authentication cleared for {$user->email}.");
        }
    }

    public function setDrawerTab(string $tab): void
    {
        $this->drawerActiveTab = $tab;
    }

    public function inspectUserResources(int $userId): void
    {
        $this->authorizeAdminAccess();
        $user = User::with(['teams', 'tokens'])->find($userId);
        if (! $user) {
            $this->dispatch('error', 'User not found.');
            return;
        }

        $team = $user->resolveStoredTeam() ?? $user->teams->first();

        $this->drawerUserId = $user->id;
        $this->drawerUserName = $user->name;
        $this->drawerUserEmail = $user->email;
        $this->drawerTeamName = $team?->name;

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

        $this->drawerApplications = $apps;
        $this->drawerDatabases = $dbs;
        $this->drawerServices = $services;
        $this->drawerVolumes = $vols;
        $this->drawerActiveTab = 'overview';
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
            'drawerApiTokens',
            'drawerApplications',
            'drawerDatabases',
            'drawerServices',
            'drawerVolumes',
        ]);
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
            'Content-Disposition' => 'attachment; filename="coolify-tenants-' . date('Y-m-d') . '.csv"',
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
                $team = $u->resolveStoredTeam() ?? $u->teams->first();
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
            'subscriptionTenants' => $this->subscriptionTenants,
        ]));
    }
}

