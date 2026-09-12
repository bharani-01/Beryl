<?php

use App\Livewire\Subscription\Index;
use App\Livewire\Subscription\PricingPlans;
use App\Models\InstanceSettings;
use App\Models\Server;
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
});

test('14-day free trial is active for newly created customer teams', function () {
    expect(isTeamOnTrial($this->customerTeam))->toBeTrue();
    expect(trialDaysRemaining($this->customerTeam))->toBe(14);
    expect(isSubscriptionActive($this->customerTeam))->toBeTrue();
});

test('free trial expires after 14 days', function () {
    $this->customerTeam->update(['created_at' => now()->subDays(15)]);

    expect(isTeamOnTrial($this->customerTeam))->toBeFalse();
    expect(trialDaysRemaining($this->customerTeam))->toBe(0);
    expect(isSubscriptionActive($this->customerTeam))->toBeFalse();
});

test('root team (id=0) does not require a subscription and is not on trial', function () {
    expect(isTeamOnTrial($this->rootTeam))->toBeFalse();
    expect(isSubscriptionActive($this->rootTeam))->toBeTrue();
    expect(teamResourceLimits($this->rootTeam)['plan'])->toBe('root');
});

test('customer teams receive trial resource limits of 0.5 vCPU and 512M RAM', function () {
    $limits = teamResourceLimits($this->customerTeam);

    expect($limits['plan'])->toBe('trial');
    expect($limits['cpus'])->toBe('0.5');
    expect($limits['memory'])->toBe('512M');
    expect($limits['max_apps'])->toBe(1);
});

test('pricing plans render INR pricing in rupees', function () {
    $this->actingAs($this->customerUser);
    session(['currentTeam' => $this->customerTeam]);

    Livewire::test(PricingPlans::class)
        ->assertSuccessful()
        ->assertSee('₹0')
        ->assertSee('₹499')
        ->assertSee('₹1,499')
        ->assertSee('₹3,999')
        ->assertSee('Starter')
        ->assertSee('Pro')
        ->assertSee('Business')
        ->assertSee('Active Trial');
});

test('subscription index displays 14-day free trial banner', function () {
    $this->actingAs($this->customerUser);
    session(['currentTeam' => $this->customerTeam]);

    Livewire::test(Index::class)
        ->assertSuccessful()
        ->assertSee('14-Day Free Trial Active')
        ->assertSee('14 days remaining');
});

test('server policy denies customer users from viewing or creating servers', function () {
    $this->actingAs($this->customerUser);
    session(['currentTeam' => $this->customerTeam]);

    expect($this->customerUser->can('viewAny', Server::class))->toBeFalse();
    expect($this->customerUser->can('create', Server::class))->toBeFalse();
    expect($this->customerUser->can('view', $this->server0))->toBeFalse();
});

test('server policy allows root admin team to manage servers', function () {
    $this->actingAs($this->rootUser);
    session(['currentTeam' => $this->rootTeam]);

    expect($this->rootUser->can('viewAny', Server::class))->toBeTrue();
    expect($this->rootUser->can('create', Server::class))->toBeTrue();
    expect($this->rootUser->can('view', $this->server0))->toBeTrue();
});

test('managed server 0 is usable as deployment destination for customer teams', function () {
    $this->actingAs($this->customerUser);
    session(['currentTeam' => $this->customerTeam]);

    $usableServers = Server::isUsable()->get();
    expect($usableServers->contains('id', 0))->toBeTrue();
});
