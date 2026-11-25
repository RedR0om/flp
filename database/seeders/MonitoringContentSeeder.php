<?php

namespace Database\Seeders;

use App\Models\ModalityCount;
use App\Models\School;
use App\Models\TechnicalAssistance;
use App\Models\Validation;
use App\Support\DivisionSnapshot;
use App\Support\Modalities;
use Illuminate\Database\Seeder;

class MonitoringContentSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::query()->get()->keyBy('name');

        foreach ($schools as $school) {
            foreach (Modalities::ALL as $modality) {
                ModalityCount::query()->firstOrCreate(
                    ['school_id' => $school->id, 'modality' => $modality],
                    ['learners_total' => 0, 'learners_reached' => 0],
                );
            }
        }

        $validations = [
            ['Silangan Elem. Sch.', 'Minor Concern – learner tracking incomplete', 'minor'],
            ["Wester Bicutan Nat'l High Sch.", 'Major Concern – reach rate below target', 'major'],
            ['CHFES-Main', 'Major Concern – offline/HTML materials not yet distributed', 'major'],
            ['Pitogo High School', 'Minor Concern – teacher readiness gap', 'minor'],
            ["Mayor Simplicio Manalo Nat'l High Sch.", 'For Revalidation – evidence incomplete', 'major'],
            ['Sto. Rosario Elem. Sch', 'Minor Concern – parent contact rate low', 'minor'],
            ['Taguig Integrated School', 'Compliant', 'ok'],
            ['Taguig Integrated Stand Alone', 'Compliant', 'ok'],
            ['Gen. R. Papa Mem. High School-Annex', 'Compliant', 'ok'],
        ];

        foreach ($validations as [$name, $finding, $severity]) {
            $school = $schools->get($name);
            if ($school === null) {
                continue;
            }

            Validation::query()->firstOrCreate(
                ['school_id' => $school->id, 'finding' => $finding],
                ['severity' => $severity],
            );
        }

        $actions = [
            ["Taguig Nat'l High Sch.", 'Connectivity', 'Resource support for offline learning kits', 'FLP Focal', 'Ongoing'],
            ['Pitogo Elem. Sch.', 'Learning Materials', 'Expedite printed ADM distribution', 'PSDS', 'Requested'],
            ['Pitogo High School', 'Teacher Capacity', 'Coaching on modality delivery', 'FLP Focal', 'Ongoing'],
            ['Silangan Elem. Sch.', 'Learner Participation', 'TA on learner tracking and follow-up', 'PSDS', 'Ongoing'],
            ['Bagong Tanyag Elem. Sch.-Annex A', 'Device', 'Coordinate device-sharing arrangement', 'FLP Focal', 'Requested'],
            ["Wester Bicutan Nat'l High Sch.", 'Learner Participation', 'Home visitation for unreached learners', 'PSDS', 'Ongoing'],
            ["Mayor Simplicio Manalo Nat'l High Sch.", 'Data Quality', 'Revalidate submitted reach data', 'EPS', 'Requested'],
            ['CHFES-Main', 'Learning Materials', 'Resource support for offline/HTML materials', 'PSDS', 'Resolved'],
            ['CHFES-Annex', 'Connectivity', 'Coordinate with LGU on connectivity access points', 'FLP Focal', 'Ongoing'],
            ['Sto. Rosario Elem. Sch', 'Parent Support', 'Barangay coordination for parent engagement', 'FLP Focal', 'Resolved'],
        ];

        foreach ($actions as [$name, $concern, $action, $responsible, $status]) {
            $school = $schools->get($name);
            if ($school === null) {
                continue;
            }

            TechnicalAssistance::query()->firstOrCreate(
                ['school_id' => $school->id, 'concern' => $concern, 'action' => $action],
                ['responsible' => $responsible, 'status' => $status],
            );
        }

        DivisionSnapshot::record();
    }
}
