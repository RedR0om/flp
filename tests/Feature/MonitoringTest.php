<?php

use App\Enums\FlpLevel;
use App\Models\School;
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

test('guests are redirected from the monitoring screens', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get(route('schools.index'))->assertRedirect(route('login'));
});

test('a psds sees only schools in their cluster', function () {
    $six = makeCluster(6);
    $one = makeCluster(1);
    makeSchool($six, 'Silangan Elem. Sch.');
    makeSchool($one, 'Ususan Elementary School');

    $psds = User::factory()->create(['cluster_id' => $six->id]);
    $psds->assignRole(Roles::PSDS);

    $this->actingAs($psds)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Overview')
            ->has('snapshot', 1)
            ->where('snapshot.0.name', 'Silangan Elem. Sch.')
            ->where('totals.schools', 1)
            ->has('clusters', 0)
        );
});

test('a school head sees and edits every school in the cluster', function () {
    $cluster = makeCluster(6);
    $outside = makeCluster(1);
    $own = makeSchool($cluster, 'Silangan Elem. Sch.', 2643, 2223);
    $other = makeSchool($cluster, 'Palar Integrated Sch.');
    $blocked = makeSchool($outside, 'Ususan Elementary School');

    $head = User::factory()->create([
        'cluster_id' => $cluster->id,
        'school_id' => $own->id,
    ]);
    $head->assignRole(Roles::SCHOOL_HEAD);

    $this->actingAs($head)
        ->get(route('schools.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('SchoolStatus')
            ->has('schools', 2)
            ->where('schools.0.name', 'Palar Integrated Sch.')
            ->where('schools.1.name', 'Silangan Elem. Sch.')
            ->where('schools.1.status', 'na')
            ->where('schools.1.reach_rate', 84.1)
        );

    $this->actingAs($head)
        ->patch(route('schools.report.update', $blocked), [
            'learners_total' => 10,
            'learners_reached' => 10,
            'flp_level' => FlpLevel::Hayo->value,
            'ta_needed' => false,
        ])
        ->assertForbidden();

    $this->actingAs($head)
        ->patch(route('schools.report.update', $own), [
            'learners_total' => 100,
            'learners_reached' => 96,
            'flp_level' => FlpLevel::Hayo->value,
            'ta_needed' => true,
        ])
        ->assertRedirect();

    expect($own->report()->first())
        ->learners_reached->toBe(96)
        ->flp_level->toBe(FlpLevel::Hayo)
        ->ta_needed->toBeTrue();
});

test('a division admin sees every cluster and can add a school', function () {
    $six = makeCluster(6);
    $one = makeCluster(1);
    makeSchool($six, 'Silangan Elem. Sch.');
    makeSchool($one, 'Ususan Elementary School');

    $admin = User::factory()->create();
    $admin->assignRole(Roles::DIVISION_ADMIN);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.schools', 2)
            ->has('clusters', 2)
        );

    $this->actingAs($admin)
        ->post(route('schools.store'), [
            'name' => 'New FLP School',
            'cluster_id' => $six->id,
            'head_name' => 'New Head',
        ])
        ->assertRedirect();

    $created = School::query()->where('name', 'New FLP School')->first();

    expect($created)->not->toBeNull()
        ->and($created->report)->not->toBeNull()
        ->and($created->report->flp_level)->toBe(FlpLevel::Hinay);

    $this->actingAs($admin)
        ->delete(route('schools.destroy', $created))
        ->assertRedirect();

    expect(School::query()->where('name', 'New FLP School')->exists())->toBeFalse();
});

test('an sds can view every school and cannot edit a report', function () {
    $six = makeCluster(6);
    $one = makeCluster(1);
    $school = makeSchool($six, 'Silangan Elem. Sch.', 100, 90);
    makeSchool($one, 'Ususan Elementary School');

    $sds = User::factory()->create();
    $sds->assignRole(Roles::SDS);

    $this->actingAs($sds)
        ->get(route('schools.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('schools', 2));

    $this->actingAs($sds)
        ->patch(route('schools.report.update', $school), [
            'learners_total' => 10,
            'learners_reached' => 10,
            'flp_level' => FlpLevel::Hayo->value,
            'ta_needed' => false,
        ])
        ->assertForbidden();

    $this->actingAs($sds)->post(route('hazards.apply'))->assertForbidden();
});

test('an oic edits only the assigned cluster', function () {
    $one = makeCluster(1);
    $six = makeCluster(6);
    $own = makeSchool($one, 'Ususan Elementary School');
    $other = makeSchool($six, 'Silangan Elem. Sch.');

    $oic = User::factory()->create(['cluster_id' => $one->id]);
    $oic->assignRole(Roles::OIC);

    $this->actingAs($oic)
        ->get(route('schools.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('schools', 1)
            ->where('schools.0.name', 'Ususan Elementary School')
        );

    $this->actingAs($oic)
        ->patch(route('schools.report.update', $other), [
            'learners_total' => 10,
            'learners_reached' => 10,
            'flp_level' => FlpLevel::Hayo->value,
            'ta_needed' => false,
        ])
        ->assertForbidden();

    $this->actingAs($oic)
        ->patch(route('schools.report.update', $own), [
            'learners_total' => 80,
            'learners_reached' => 80,
            'flp_level' => FlpLevel::Hayo->value,
            'ta_needed' => false,
        ])
        ->assertRedirect();
});

test('overview enrollment sums submitted learner counts by cluster', function () {
    $six = makeCluster(6);
    $one = makeCluster(1);
    makeSchool($six, 'Silangan Elem. Sch.', 100, 90);
    makeSchool($six, 'Palar Integrated Sch.', null, null);
    makeSchool($one, 'Ususan Elementary School', 50, 50);

    $admin = User::factory()->create();
    $admin->assignRole(Roles::DIVISION_ADMIN);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('enrollment.total', 150)
            ->where('enrollment.with_records', 2)
            ->where('enrollment.schools', 3)
            ->has('schools', 3)
            ->where('enrollment.rows.0.cluster', 1)
            ->where('enrollment.rows.0.enrollment', 50)
            ->where('enrollment.rows.0.share', 33.3)
            ->where('enrollment.rows.1.cluster', 6)
            ->where('enrollment.rows.1.schools', 2)
            ->where('enrollment.rows.1.enrollment', 100)
            ->where('enrollment.rows.1.share', 66.7)
        );
});

test('school status includes recorded sections for the detail view', function () {
    $cluster = makeCluster(6);
    $school = makeSchool($cluster, 'Silangan Elem. Sch.', 2643, 2223);
    $section = $school->sections()->create(['name' => 'Grade 6 - Rizal']);
    $section->learners()->create(['net' => 'Home WiFi / Broadband', 'sim' => null]);
    $section->learners()->create(['net' => 'None', 'sim' => null]);

    $admin = User::factory()->create();
    $admin->assignRole(Roles::DIVISION_ADMIN);

    $this->actingAs($admin)
        ->get(route('schools.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('schools.0.sections.0.name', 'Grade 6 - Rizal')
            ->where('schools.0.sections.0.learners', 2)
            ->where('schools.0.sections.0.internet_percent', 50)
        );
});
