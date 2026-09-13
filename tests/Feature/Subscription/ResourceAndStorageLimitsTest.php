<?php

use App\Livewire\Project\Shared\ResourceLimits;
use App\Models\Application;
use App\Models\Environment;
use App\Models\InstanceSettings;
use App\Models\LocalPersistentVolume;
use App\Models\Project;
use App\Models\Server;
use App\Models\Service;
use App\Models\ServiceApplication;
use App\Models\StandaloneDocker;
use App\Models\StandaloneMysql;
use App\Models\StandalonePostgresql;
use App\Models\StandaloneRedis;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('app.maintenance.store', 'array');
    config()->set('constants.coolify.self_hosted', false);
    config()->set('subscription.provider', 'stripe');

    InstanceSettings::unguarded(fn () => InstanceSettings::query()->create(['id' => 0]));

    $this->rootTeam = Team::query()->create(['id' => 0, 'name' => 'Root Team']);
    $this->rootUser = User::factory()->create(['id' => 0]);
    $this->rootTeam->members()->attach($this->rootUser->id, ['role' => 'owner']);

    $this->customerTeam = Team::factory()->create(['created_at' => now()]);
    $this->customerUser = User::factory()->create();
    $this->customerTeam->members()->attach($this->customerUser->id, ['role' => 'owner']);

    $this->server0 = Server::query()->create([
        'id' => 0,
        'name' => 'localhost',
        'ip' => '127.0.0.1',
        'user' => 'root',
        'port' => 22,
        'team_id' => 0,
    ]);
    $this->server0->settings()->create([
        'is_reachable' => true,
        'is_usable' => true,
        'is_swarm_worker' => false,
        'is_build_server' => false,
        'force_disabled' => false,
    ]);

    $this->destination = StandaloneDocker::query()->create([
        'id' => 0,
        'name' => 'default',
        'network' => 'coolify',
        'server_id' => 0,
    ]);

    $this->project = Project::query()->create([
        'name' => 'Test Project',
        'team_id' => $this->customerTeam->id,
    ]);

    $this->environment = Environment::query()->create([
        'name' => 'production',
        'project_id' => $this->project->id,
    ]);
});

test('parseMemoryStringToBytes correctly converts memory strings', function () {
    expect(parseMemoryStringToBytes('512M'))->toBe(536870912);
    expect(parseMemoryStringToBytes('512m'))->toBe(536870912);
    expect(parseMemoryStringToBytes('1G'))->toBe(1073741824);
    expect(parseMemoryStringToBytes('2g'))->toBe(2147483648);
    expect(parseMemoryStringToBytes('1024k'))->toBe(1048576);
    expect(parseMemoryStringToBytes('0'))->toBe(0);
    expect(parseMemoryStringToBytes('unlimited'))->toBe(0);
    expect(parseMemoryStringToBytes(null))->toBeNull();
    expect(parseMemoryStringToBytes('invalid'))->toBeNull();
});

test('isMemoryLimitExceeded correctly evaluates requested memory against plan memory', function () {
    // Trial plan is 512M
    expect(isMemoryLimitExceeded('256M', '512M'))->toBeFalse();
    expect(isMemoryLimitExceeded('512M', '512M'))->toBeFalse();
    expect(isMemoryLimitExceeded('1G', '512M'))->toBeTrue();
    expect(isMemoryLimitExceeded('2G', '512M'))->toBeTrue();

    // Requesting unmetered (0 or empty) on a metered plan is disallowed
    expect(isMemoryLimitExceeded('0', '512M'))->toBeTrue();
    expect(isMemoryLimitExceeded('', '512M'))->toBeTrue();

    // Unlimited plan allows anything
    expect(isMemoryLimitExceeded('8G', 'Unlimited'))->toBeFalse();
    expect(isMemoryLimitExceeded('0', 'Unlimited'))->toBeFalse();
});

test('ResourceLimits component prevents customer teams from setting memory exceeding plan limit', function () {
    $this->actingAs($this->customerUser);
    session(['currentTeam' => $this->customerTeam]);

    $app = Application::query()->create([
        'name' => 'Test App',
        'environment_id' => $this->environment->id,
        'destination_id' => $this->destination->id,
        'destination_type' => StandaloneDocker::class,
    ]);

    // Customer team is on trial (max 512M)
    Livewire::test(ResourceLimits::class, ['resource' => $app])
        ->set('limitsMemory', '2G')
        ->call('submit')
        ->assertDispatched('error', 'Memory limit exceeded');

    // Model should not have been updated to 2G
    expect($app->fresh()->limits_memory)->not->toBe('2G');
});

test('ResourceLimits component prevents customer teams from setting memory to 0 (unlimited)', function () {
    $this->actingAs($this->customerUser);
    session(['currentTeam' => $this->customerTeam]);

    $app = Application::query()->create([
        'name' => 'Test App',
        'environment_id' => $this->environment->id,
        'destination_id' => $this->destination->id,
        'destination_type' => StandaloneDocker::class,
    ]);

    Livewire::test(ResourceLimits::class, ['resource' => $app])
        ->set('limitsMemory', '0')
        ->call('submit')
        ->assertDispatched('error', 'Memory limit exceeded');

    expect($app->fresh()->limits_memory)->not->toBe('0');
});

test('ResourceLimits component allows customer teams to set memory within plan limit', function () {
    $this->actingAs($this->customerUser);
    session(['currentTeam' => $this->customerTeam]);

    $app = Application::query()->create([
        'name' => 'Test App',
        'environment_id' => $this->environment->id,
        'destination_id' => $this->destination->id,
        'destination_type' => StandaloneDocker::class,
    ]);

    Livewire::test(ResourceLimits::class, ['resource' => $app])
        ->set('limitsMemory', '256M')
        ->call('submit')
        ->assertDispatched('success');

    expect($app->fresh()->limits_memory)->toBe('256M');
});

test('standalone databases inherit plan resource limits on creation', function () {
    session(['currentTeam' => $this->customerTeam]);

    $pg = StandalonePostgresql::query()->create([
        'name' => 'test-pg',
        'environment_id' => $this->environment->id,
        'destination_id' => $this->destination->id,
        'destination_type' => StandaloneDocker::class,
    ]);

    $limits = teamResourceLimits($this->customerTeam);
    expect($pg->limits_cpus)->toBe($limits['cpus']);
    expect($pg->limits_memory)->toBe($limits['memory']);

    $mysql = StandaloneMysql::query()->create([
        'name' => 'test-mysql',
        'environment_id' => $this->environment->id,
        'destination_id' => $this->destination->id,
        'destination_type' => StandaloneDocker::class,
    ]);
    expect($mysql->limits_cpus)->toBe($limits['cpus']);
    expect($mysql->limits_memory)->toBe($limits['memory']);

    $redis = StandaloneRedis::query()->create([
        'name' => 'test-redis',
        'environment_id' => $this->environment->id,
        'destination_id' => $this->destination->id,
        'destination_type' => StandaloneDocker::class,
    ]);
    expect($redis->limits_cpus)->toBe($limits['cpus']);
    expect($redis->limits_memory)->toBe($limits['memory']);
});

test('teamStorageUsage accurately counts volumes and canTeamCreateVolume detects limit', function () {
    session(['currentTeam' => $this->customerTeam]);

    // Trial plan allows max 2 volumes
    expect(canTeamCreateVolume($this->customerTeam))->toBeTrue();

    $app = Application::query()->create([
        'name' => 'App With Volumes',
        'environment_id' => $this->environment->id,
        'destination_id' => $this->destination->id,
        'destination_type' => StandaloneDocker::class,
    ]);

    // Add 1st volume
    LocalPersistentVolume::query()->create([
        'name' => 'vol-1',
        'mount_path' => '/data1',
        'resource_id' => $app->id,
        'resource_type' => $app->getMorphClass(),
    ]);

    $usage1 = teamStorageUsage($this->customerTeam);
    expect($usage1['volumes_count'])->toBe(1);
    expect(canTeamCreateVolume($this->customerTeam))->toBeTrue();

    // Add 2nd volume
    LocalPersistentVolume::query()->create([
        'name' => 'vol-2',
        'mount_path' => '/data2',
        'resource_id' => $app->id,
        'resource_type' => $app->getMorphClass(),
    ]);

    $usage2 = teamStorageUsage($this->customerTeam);
    expect($usage2['volumes_count'])->toBe(2);
    // At maximum 2 volumes, cannot create more
    expect(canTeamCreateVolume($this->customerTeam))->toBeFalse();
});

test('countTeamRunningResources correctly calculates running apps, databases and services', function () {
    session(['currentTeam' => $this->customerTeam]);

    expect(countTeamRunningResources($this->customerTeam))->toBe(0);

    $app = Application::query()->create([
        'name' => 'Running App',
        'status' => 'running',
        'environment_id' => $this->environment->id,
        'destination_id' => $this->destination->id,
        'destination_type' => StandaloneDocker::class,
    ]);

    expect(countTeamRunningResources($this->customerTeam))->toBe(1);

    $pg = StandalonePostgresql::query()->create([
        'name' => 'Running PG',
        'status' => 'running',
        'environment_id' => $this->environment->id,
        'destination_id' => $this->destination->id,
        'destination_type' => StandaloneDocker::class,
    ]);

    expect(countTeamRunningResources($this->customerTeam))->toBe(2);

    $service = Service::query()->create([
        'name' => 'Test Service',
        'environment_id' => $this->environment->id,
        'destination_id' => $this->destination->id,
        'destination_type' => StandaloneDocker::class,
    ]);

    $svcApp = ServiceApplication::query()->create([
        'service_id' => $service->id,
        'name' => 'svc-app',
        'status' => 'running',
    ]);

    expect(countTeamRunningResources($this->customerTeam))->toBe(3);
});

test('checkResourceLimitForDeployment blocks when active running count meets or exceeds plan limit', function () {
    session(['currentTeam' => $this->customerTeam]);

    // Trial plan allows max 1 app
    expect(checkResourceLimitForDeployment($this->customerTeam)['allowed'])->toBeTrue();

    Application::query()->create([
        'name' => 'Running App 1',
        'status' => 'running',
        'environment_id' => $this->environment->id,
        'destination_id' => $this->destination->id,
        'destination_type' => StandaloneDocker::class,
    ]);

    $check = checkResourceLimitForDeployment($this->customerTeam);
    expect($check['allowed'])->toBeFalse();
    expect($check['reason'])->toBe('limit_reached');
    expect($check['title'])->toBe('Active Resource Limit Reached');
});

test('Select component blocks setType and dispatches open-plan-limit-modal when limit reached', function () {
    $this->actingAs($this->customerUser);
    session(['currentTeam' => $this->customerTeam]);

    // Create a running application to max out the trial plan (1 app limit)
    Application::query()->create([
        'name' => 'Running App',
        'status' => 'running',
        'environment_id' => $this->environment->id,
        'destination_id' => $this->destination->id,
        'destination_type' => StandaloneDocker::class,
    ]);

    Livewire::test(\App\Livewire\Project\New\Select::class, [
        'project_uuid' => $this->project->uuid,
        'environment_uuid' => $this->environment->uuid,
    ])
        ->assertSet('isResourceLimitReached', true)
        ->call('setType', 'one-click-service-affine')
        ->assertDispatched('open-plan-limit-modal')
        ->assertNoRedirect();
});

