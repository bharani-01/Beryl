<?php

use App\Livewire\Admin\Index as AdminIndex;
use App\Models\InstanceSettings;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    InstanceSettings::create(['id' => 0]);
    config()->set('constants.coolify.self_hosted', false);
});

test('user personalTeam resolves personal team even when current_team_id points elsewhere', function () {
    $userA = User::factory()->create(['name' => 'User Alpha']);
    $personalTeam = $userA->personalTeam();

    expect($personalTeam)->not->toBeNull();
    expect($personalTeam->name)->toBe("User Alpha's Team");
    expect($userA->primaryRole())->toBe('owner');

    // Create another team and add userA as a member
    $teamB = Team::factory()->create(['name' => 'Team Beta', 'personal_team' => false]);
    $teamB->members()->attach($userA->id, ['role' => 'member']);
    $userA->current_team_id = $teamB->id;
    $userA->save();
    $userA->refresh();

    // personalTeam should still return User Alpha's Team, not Team Beta
    expect($userA->personalTeam()->id)->toBe($personalTeam->id);
    expect($userA->primaryRole())->toBe('owner');
    expect($userA->roleInTeam($teamB->id))->toBe('member');
});

test('admin console displays correct primary team, role badge, and extra teams pill', function () {
    $rootTeam = Team::find(0) ?? Team::factory()->create(['id' => 0, 'name' => 'Root Team']);
    $rootUser = User::factory()->create(['id' => 0, 'name' => 'Bharani KR']);
    $rootTeam->members()->attach($rootUser->id, ['role' => 'owner']);

    // Create a regular user who also joined another team
    $user = User::factory()->create(['name' => 'Test User', 'email' => 'testuser@example.com']);
    $otherTeam = Team::factory()->create(['name' => 'Acme Corp Team', 'personal_team' => false]);
    $otherTeam->members()->attach($user->id, ['role' => 'member']);
    $user->current_team_id = $otherTeam->id;
    $user->save();

    $this->actingAs($rootUser);
    session(['currentTeam' => ['id' => $rootTeam->id]]);

    $test = Livewire::test(AdminIndex::class)
        ->assertOk()
        ->assertSee("Test User's Team")
        ->assertSee('+1 team')
        ->assertSee('Instance Admin');

    // Test opening manage modal for the user
    $test->call('openManageModal', $user->id)
        ->assertSet('managingTeamId', $user->personalTeam()->id)
        ->assertSet('managingTeamName', "Test User's Team")
        ->assertSet('managingUserRole', 'owner');

    // Test inspecting user resources / drawer
    $test->call('inspectUserResources', $user->id)
        ->assertSet('drawerTeamName', "Test User's Team");

    $drawerTeams = $test->get('drawerUserTeams');
    expect(count($drawerTeams))->toBe(2);
});
