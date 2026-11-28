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
 * - admin@flp.test — division administrator, all clusters, can edit
 * - sds@flp.test — SDS, all clusters, view only
 * - psds.cluster6@flp.test — Lovely Rollaine B. Cruz, Cluster 6
 * - head.silangan@flp.test — Magdalena G. Centeno, Cluster 6
 * - focal.cluster6@flp.test — FLP focal, Cluster 6
 * - oic.cluster1@flp.test — John Keeneth Ferrera, Cluster 1
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

        $this->account('Division Administrator', 'admin@flp.test', Roles::DIVISION_ADMIN);

        $this->account('Schools Division Superintendent', 'sds@flp.test', Roles::SDS);

        $clusterSix = $clusters[6];
        $this->account($clusterSix->supervisor_name, 'psds.cluster6@flp.test', Roles::PSDS, $clusterSix->id);

        $silangan = School::query()->where('name', 'Silangan Elem. Sch.')->firstOrFail();
        $this->account($silangan->head_name, 'head.silangan@flp.test', Roles::SCHOOL_HEAD, $silangan->cluster_id, $silangan->id);
        $this->account('Cluster 6 FLP Focal', 'focal.cluster6@flp.test', Roles::FLP_FOCAL, $clusterSix->id);

        $clusterOne = $clusters[1];
        $this->account($clusterOne->supervisor_name, 'oic.cluster1@flp.test', Roles::OIC, $clusterOne->id);

        $this->call(MonitoringContentSeeder::class);
    }

    private function account(string $name, string $email, string $role, ?int $clusterId = null, ?int $schoolId = null): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => 'password',
                'cluster_id' => $clusterId,
                'school_id' => $schoolId,
            ],
        );

        if ($user->email_verified_at === null) {
            $user->markEmailAsVerified();
        }

        $user->syncRoles([$role]);
    }
}
