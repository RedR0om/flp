<?php

use App\Models\Activity;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (Roles::ALL as $role) {
        Role::findOrCreate($role);
    }
});

test('guests and other roles cannot open user management', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));

    $psds = User::factory()->create(['cluster_id' => makeCluster(6)->id]);
    $psds->assignRole(Roles::PSDS);

    $this->actingAs($psds)->get(route('users.index'))->assertForbidden();
    $this->actingAs($psds)->post(route('users.store'), [])->assertForbidden();
});

test('a division administrator creates and updates a cluster account', function () {
    $cluster = makeCluster(6);
    $admin = User::factory()->create();
    $admin->assignRole(Roles::DIVISION_ADMIN);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Cluster 6 FLP Focal',
            'email' => 'focal.cluster6@flp.test',
            'role' => Roles::FLP_FOCAL,
            'cluster_id' => $cluster->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $focal = User::query()->where('email', 'focal.cluster6@flp.test')->first();

    expect($focal)->not->toBeNull()
        ->and($focal->hasRole(Roles::FLP_FOCAL))->toBeTrue()
        ->and($focal->cluster_id)->toBe($cluster->id)
        ->and($focal->email_verified_at)->not->toBeNull();

    $this->actingAs($admin)
        ->patch(route('users.update', $focal), [
            'name' => 'Cluster 6 Focal Person',
            'email' => 'focal.cluster6@flp.test',
            'role' => Roles::PSDS,
            'cluster_id' => $cluster->id,
            'password' => '',
            'password_confirmation' => '',
        ])
        ->assertRedirect();

    expect($focal->fresh()->name)->toBe('Cluster 6 Focal Person')
        ->and($focal->fresh()->hasRole(Roles::PSDS))->toBeTrue();

    expect(Activity::query()->where('summary', 'Account created')->exists())->toBeTrue();
});

test('a cluster role requires a cluster and an sds does not keep one', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Roles::DIVISION_ADMIN);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'School head',
            'email' => 'head@flp.test',
            'role' => Roles::SCHOOL_HEAD,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertSessionHasErrors('cluster_id');

    $cluster = makeCluster(1);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'Schools Division Superintendent',
            'email' => 'sds@flp.test',
            'role' => Roles::SDS,
            'cluster_id' => $cluster->id,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect();

    expect(User::query()->where('email', 'sds@flp.test')->first())
        ->cluster_id->toBeNull()
        ->hasRole(Roles::SDS)->toBeTrue();
});

test('a division administrator cannot remove their own account', function () {
    $admin = User::factory()->create();
    $admin->assignRole(Roles::DIVISION_ADMIN);
    $other = User::factory()->create();
    $other->assignRole(Roles::SDS);

    $this->actingAs($admin)->delete(route('users.destroy', $admin))->assertForbidden();
    expect(User::query()->whereKey($admin->id)->exists())->toBeTrue();

    $this->actingAs($admin)->delete(route('users.destroy', $other))->assertRedirect();
    expect(User::query()->whereKey($other->id)->exists())->toBeFalse();
});

test('the users page lists accounts for a division administrator', function () {
    $admin = User::factory()->create(['name' => 'Division Administrator']);
    $admin->assignRole(Roles::DIVISION_ADMIN);

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Users')
            ->has('accounts', 1)
            ->where('accounts.0.email', $admin->email)
            ->where('accounts.0.role', Roles::DIVISION_ADMIN)
        );
});
