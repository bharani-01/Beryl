<?php

use App\Models\InstanceSettings;
use App\Models\User;

beforeEach(function () {
    InstanceSettings::firstOrCreate(
        ['id' => 0],
        [
            'is_registration_enabled' => true,
            'fqdn' => 'http://localhost:8000',
        ]
    );
});

test('guest visiting root URL sees softly themed landing page with beryl branding', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('Beryl', false);
    $response->assertSee('Outfit', false);
    $response->assertSee('Reenie+Beanie', false);
    $response->assertSee('id="root"', false);
    $response->assertSee('/landing/assets/', false);
});

test('authenticated user visiting root URL is redirected to dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/');

    $response->assertRedirect(route('dashboard'));
});
