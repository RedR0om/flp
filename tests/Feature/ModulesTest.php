<?php

use App\Enums\FlpLevel;
use App\Models\HazardEvent;
use App\Models\TechnicalAssistance;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    foreach (Roles::ALL as $role) {
        Role::findOrCreate($role);
    }
});

test('guests are redirected from the remaining monitoring screens', function () {
    $this->get(route('sections.index'))->assertRedirect(route('login'));
    $this->get(route('modalities.index'))->assertRedirect(route('login'));
    $this->get(route('validations.index'))->assertRedirect(route('login'));
    $this->get(route('actions.index'))->assertRedirect(route('login'));
    $this->get(route('reports.index'))->assertRedirect(route('login'));
    $this->get(route('hazards.index'))->assertRedirect(route('login'));
    $this->get(route('about'))->assertRedirect(route('login'));
    $this->get(route('events.index'))->assertRedirect(route('login'));
    $this->get(route('master-data.index'))->assertRedirect(route('login'));
    $this->get(route('learner-reach.index'))->assertRedirect(route('login'));
    $this->get(route('issues.index'))->assertRedirect(route('login'));
    $this->get(route('activity.index'))->assertRedirect(route('login'));
});

test('a school head manages sections for the cluster', function () {
    $cluster = makeCluster(6);
    $outside = makeCluster(1);
    $own = makeSchool($cluster, 'Silangan Elem. Sch.');
    $other = makeSchool($cluster, 'Palar Integrated Sch.');
    $blocked = makeSchool($outside, 'Ususan Elementary School');

    $head = User::factory()->create([
        'cluster_id' => $cluster->id,
        'school_id' => $own->id,
    ]);
    $head->assignRole(Roles::SCHOOL_HEAD);

    $this->actingAs($head)
        ->post(route('sections.store'), ['name' => 'Grade 6 - A', 'school_id' => $blocked->id])
        ->assertForbidden();

    $this->actingAs($head)
        ->post(route('sections.store'), ['name' => 'Grade 6 - A', 'school_id' => $other->id])
        ->assertRedirect();

    $this->actingAs($head)
        ->get(route('sections.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Sections')
            ->has('sections', 1)
            ->where('sections.0.name', 'Grade 6 - A')
            ->where('sections.0.school_name', 'Palar Integrated Sch.')
        );
});

test('a psds hazard apply stays inside the cluster', function () {
    $six = makeCluster(6);
    $one = makeCluster(1);
    $own = makeSchool($six, 'Silangan Elem. Sch.');
    $other = makeSchool($one, 'Ususan Elementary School');

    HazardEvent::query()->create([
        'condition' => 'Weather – Red Heavy Rainfall Warning',
        'level' => FlpLevel::Hinto,
        'is_active' => true,
        'starts_on' => '2026-08-17',
    ]);

    $psds = User::factory()->create(['cluster_id' => $six->id]);
    $psds->assignRole(Roles::PSDS);

    $this->actingAs($psds)->post(route('hazards.apply'))->assertRedirect();

    expect($own->report()->first()->flp_level)->toBe(FlpLevel::Hinto)
        ->and($other->report()->first()->flp_level)->toBe(FlpLevel::Hinay);
});

test('school status csv stays inside the user scope', function () {
    $six = makeCluster(6);
    $one = makeCluster(1);
    makeSchool($six, 'Silangan Elem. Sch.');
    makeSchool($one, 'Ususan Elementary School');

    $psds = User::factory()->create(['cluster_id' => $six->id]);
    $psds->assignRole(Roles::PSDS);

    $response = $this->actingAs($psds)->get(route('reports.schools'));
    $response->assertOk();
    $csv = $response->streamedContent();

    expect($csv)->toContain('Silangan Elem. Sch.')
        ->and($csv)->not->toContain('Ususan Elementary School');
});

test('an action status cycles to the next step', function () {
    $cluster = makeCluster(6);
    $school = makeSchool($cluster, 'Silangan Elem. Sch.');
    $action = TechnicalAssistance::query()->create([
        'school_id' => $school->id,
        'concern' => 'Connectivity',
        'action' => 'Provide offline kits',
        'responsible' => 'FLP Focal',
        'status' => 'Requested',
    ]);

    $head = User::factory()->create([
        'cluster_id' => $cluster->id,
        'school_id' => $school->id,
    ]);
    $head->assignRole(Roles::SCHOOL_HEAD);

    $this->actingAs($head)->patch(route('actions.update', $action))->assertRedirect();

    expect($action->fresh()->status)->toBe('Ongoing');
});
