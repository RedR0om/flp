<?php

use App\Enums\FlpLevel;
use App\Models\Activity;
use App\Models\HazardEvent;
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

test('continuity pages stay inside the signed-in cluster and use stored reports', function () {
    $six = makeCluster(6);
    $one = makeCluster(1);
    $silangan = makeSchool($six, 'Silangan Elem. Sch.', 2643, 2223);
    makeSchool($one, 'Ususan Elementary School', 100, 50);

    $event = HazardEvent::query()->create([
        'condition' => 'Weather – Yellow Heavy Rainfall Warning',
        'level' => FlpLevel::Hinay,
        'is_active' => true,
        'starts_on' => '2026-08-17',
    ]);
    $silangan->report->update(['hazard_event_id' => $event->id]);

    $psds = User::factory()->create(['cluster_id' => $six->id]);
    $psds->assignRole(Roles::PSDS);

    $this->actingAs($psds)
        ->get(route('events.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events', 1)
            ->where('events.0.school', 'Silangan Elem. Sch.')
            ->where('events.0.scenario', 'Weather – Yellow Heavy Rainfall Warning')
            ->where('events.0.level', 'HINAY')
        );

    $this->actingAs($psds)
        ->get(route('master-data.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('schools', 1)
            ->where('schools.0.name', 'Silangan Elem. Sch.')
            ->where('schools.0.cluster', 'Cluster 6')
        );

    $this->actingAs($psds)
        ->get(route('learner-reach.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('totals.learners', 2643)
            ->where('totals.reached', 2223)
            ->has('schools', 1)
        );

    $this->actingAs($psds)
        ->get(route('issues.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('schools', 1)
            ->where('schools.0.name', 'Silangan Elem. Sch.')
            ->where('schools.0.status', 'na')
        );

    $this->actingAs($psds)
        ->patch(route('schools.report.update', $silangan), [
            'learners_total' => 2643,
            'learners_reached' => 2223,
            'flp_level' => FlpLevel::Hinay->value,
            'ta_needed' => true,
        ])
        ->assertRedirect();

    $activity = Activity::query()->where('summary', 'School report updated')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->school_id)->toBe($silangan->id);

    $this->actingAs($psds)
        ->get(route('activity.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('activities.0.summary', 'School report updated')
            ->where('activities.0.school', 'Silangan Elem. Sch.')
        );

    $this->actingAs($psds)
        ->post(route('activity.read', $activity))
        ->assertRedirect();

    expect($psds->activitiesRead()->whereKey($activity->id)->exists())->toBeTrue();
});
