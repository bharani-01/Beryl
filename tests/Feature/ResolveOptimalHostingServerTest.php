<?php

use App\Actions\Server\ResolveOptimalHostingServer;
use App\Models\InstanceSettings;
use App\Models\Server;
use App\Models\StandaloneDocker;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

beforeEach(function () {
    Server::flushIdentityMap();
    Cache::flush();
    InstanceSettings::forceCreate(['id' => 0, 'is_api_enabled' => true]);

    $this->rootTeam = Team::forceCreate(['id' => 0, 'name' => 'Root Team']);
    $this->customerTeam = Team::factory()->create();
    $this->customerUser = User::factory()->create();
    $this->customerTeam->members()->attach($this->customerUser->id, ['role' => 'owner']);

    // Seed Server 0 (localhost)
    $this->server0 = Server::forceCreate([
        'id' => 0,
        'name' => 'localhost',
        'ip' => '18.60.46.17',
        'team_id' => 0,
    ]);
    $this->server0->settings()->update([
        'is_reachable' => true,
        'is_usable' => true,
        'is_build_server' => false,
        'force_disabled' => false,
    ]);
    $this->destination0 = StandaloneDocker::create([
        'name' => 'default',
        'network' => 'coolify',
        'server_id' => 0,
    ]);

    // Seed Server 2 (worker node)
    $this->server2 = Server::forceCreate([
        'id' => 2,
        'name' => 'server-2',
        'ip' => '18.60.110.177',
        'team_id' => 0,
    ]);
    $this->server2->settings()->update([
        'is_reachable' => true,
        'is_usable' => true,
        'is_build_server' => false,
        'force_disabled' => false,
    ]);
    $this->destination2 = StandaloneDocker::create([
        'name' => 'default',
        'network' => 'coolify',
        'server_id' => 2,
    ]);
});

afterEach(function () {
    Server::flushIdentityMap();
    Cache::flush();
});

test('picks server 2 over server 0 due to 2gb admin panel reservation on server 0', function () {
    // Mock telemetry in Redis cache
    Cache::put('server:0:live_telemetry', [
        'total_ram_mb' => 3831,
        'used_ram_mb' => 1980,
        'available_ram_mb' => 1851, // Below 2048 MB reserve -> effective 0 MB
        'used_ram_percent' => 51.6,
        'load_1min' => 0.6,
        'load_5min' => 0.5,
        'load_15min' => 0.5,
        'is_healthy' => true,
        'checked_at' => now()->toIso8601String(),
    ], 60);

    Cache::put('server:2:live_telemetry', [
        'total_ram_mb' => 3831,
        'used_ram_mb' => 693,
        'available_ram_mb' => 3138, // 3138 - 500 = 2638 MB effective headroom
        'used_ram_percent' => 18.1,
        'load_1min' => 0.2,
        'load_5min' => 0.1,
        'load_15min' => 0.1,
        'is_healthy' => true,
        'checked_at' => now()->toIso8601String(),
    ], 60);

    $optimal = ResolveOptimalHostingServer::run();

    expect($optimal)->not->toBeNull();
    expect($optimal['server']->id)->toBe(2);
    expect($optimal['destination']->server_id)->toBe(2);
    expect($optimal['effective_ram_mb'])->toBe(2638);
});

test('returns null when all servers are out of capacity', function () {
    // Both servers exhausted
    Cache::put('server:0:live_telemetry', [
        'total_ram_mb' => 3831,
        'used_ram_mb' => 3600,
        'available_ram_mb' => 231, // Below min acceptable (400 MB) and below 2048 reserve
        'used_ram_percent' => 94.0,
        'load_1min' => 4.5,
        'load_5min' => 4.0,
        'load_15min' => 3.5,
        'is_healthy' => true,
        'checked_at' => now()->toIso8601String(),
    ], 60);

    Cache::put('server:2:live_telemetry', [
        'total_ram_mb' => 3831,
        'used_ram_mb' => 3600,
        'available_ram_mb' => 231, // Below min acceptable (400 MB)
        'used_ram_percent' => 94.0,
        'load_1min' => 5.0,
        'load_5min' => 4.5,
        'load_15min' => 4.0,
        'is_healthy' => true,
        'checked_at' => now()->toIso8601String(),
    ], 60);

    $optimal = ResolveOptimalHostingServer::run();

    expect($optimal)->toBeNull();
});

test('skips unreachable or disabled servers', function () {
    $this->server2->settings()->update(['is_reachable' => false]);

    // Give server 0 excess RAM above the 2048 MB reserve
    Cache::put('server:0:live_telemetry', [
        'total_ram_mb' => 8000,
        'used_ram_mb' => 2000,
        'available_ram_mb' => 6000, // 6000 - 2048 = 3952 MB effective
        'used_ram_percent' => 25.0,
        'load_1min' => 0.5,
        'load_5min' => 0.5,
        'load_15min' => 0.5,
        'is_healthy' => true,
        'checked_at' => now()->toIso8601String(),
    ], 60);

    $optimal = ResolveOptimalHostingServer::run();

    expect($optimal)->not->toBeNull();
    expect($optimal['server']->id)->toBe(0);
});

test('non-root customer teams can resolve destinations on server 2', function () {
    session(['currentTeam' => $this->customerTeam]);

    $destination = find_resource_destination_for_current_team($this->destination2->uuid);

    expect($destination)->not->toBeNull();
    expect($destination->id)->toBe($this->destination2->id);
    expect($destination->server_id)->toBe(2);
});
