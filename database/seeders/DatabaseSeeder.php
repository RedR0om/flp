<?php

namespace Database\Seeders;

use App\Enums\FlpLevel;
use App\Models\Cluster;
use App\Models\HazardEvent;
use App\Models\School;
use App\Models\User;
use App\Support\Roles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Local demo accounts. Every seeded user signs in with the password "password".
 *
 * - admin@flp.test — division admin, all clusters
 * - psds.cluster6@flp.test — Lovely Rollaine B. Cruz, Cluster 6
 * - head.silangan@flp.test — Magdalena G. Centeno, Silangan Elem. Sch.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (Roles::ALL as $role) {
            Role::findOrCreate($role);
        }

        $supervisors = [
            1 => ['John Keeneth Ferrera', 'OIC-PSDS'],
            2 => ['Elvira Bagacina', 'PSDS'],
            3 => ['Joter Belleza', 'PSDS'],
            4 => ['Maria Ligaya Gepollo', 'OIC-PSDS'],
            5 => ['Danny Espelico', 'PSDS'],
            6 => ['Lovely Rollaine B. Cruz', 'PSDS'],
            7 => ['Ma. Leny Manaoat', 'PSDS'],
            8 => ['Rowena O. Roa', 'PSDS'],
            9 => ['Marcial C. Sison', 'PSDS'],
            10 => ['Rayand C. Saballe', 'PSDS'],
        ];

        $clusters = collect($supervisors)->mapWithKeys(function (array $supervisor, int $number) {
            $cluster = Cluster::query()->create([
                'number' => $number,
                'supervisor_name' => $supervisor[0],
                'supervisor_title' => $supervisor[1],
            ]);

            return [$number => $cluster];
        });

        $event = HazardEvent::query()->create([
            'condition' => 'Weather – Yellow Heavy Rainfall Warning',
            'level' => FlpLevel::Hinay,
            'is_active' => true,
            'starts_on' => '2026-08-17',
        ]);

        foreach (require database_path('data/schools.php') as $row) {
            $school = School::query()->create([
                'cluster_id' => $clusters[$row['cluster']]->id,
                'name' => $row['name'],
                'head_name' => $row['head_name'] !== '' ? $row['head_name'] : null,
            ]);

            $school->report()->create([
                'hazard_event_id' => $event->id,
                'learners_total' => $row['learners_total'],
                'learners_reached' => $row['learners_reached'],
                'flp_level' => FlpLevel::Hinay,
                'ta_needed' => $row['ta_needed'],
            ]);
        }

        User::query()->create([
            'name' => 'Division Administrator',
            'email' => 'admin@flp.test',
            'password' => 'password',
            'email_verified_at' => now(),
        ])->assignRole(Roles::DIVISION_ADMIN);

        $clusterSix = $clusters[6];

        User::query()->create([
            'name' => $clusterSix->supervisor_name,
            'email' => 'psds.cluster6@flp.test',
            'password' => 'password',
            'email_verified_at' => now(),
            'cluster_id' => $clusterSix->id,
        ])->assignRole(Roles::PSDS);

        $silangan = School::query()->where('name', 'Silangan Elem. Sch.')->firstOrFail();

        User::query()->create([
            'name' => $silangan->head_name,
            'email' => 'head.silangan@flp.test',
            'password' => 'password',
            'email_verified_at' => now(),
            'cluster_id' => $silangan->cluster_id,
            'school_id' => $silangan->id,
        ])->assignRole(Roles::SCHOOL_HEAD);

        $this->call(MonitoringContentSeeder::class);
    }
}
