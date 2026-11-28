<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('school_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cluster_id')->nullable()->constrained()->nullOnDelete();
            $table->string('summary');
            $table->text('detail')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });

        Schema::create('activity_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->unique(['activity_id', 'user_id']);
        });

        $this->backfill();
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_reads');
        Schema::dropIfExists('activities');
    }

    private function backfill(): void
    {
        if (! Schema::hasTable('school_reports')) {
            return;
        }

        $now = now();

        foreach (DB::table('school_reports')->orderBy('id')->get() as $report) {
            $counts = $report->learners_total !== null && $report->learners_reached !== null
                ? $report->learners_reached.' of '.$report->learners_total.' learners reached'
                : 'no learner count yet';

            DB::table('activities')->insert([
                'school_id' => $report->school_id,
                'summary' => 'School report on file',
                'detail' => $report->flp_level.' · '.$counts,
                'occurred_at' => $report->updated_at ?? $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (Schema::hasTable('validations')) {
            foreach (DB::table('validations')->orderBy('id')->get() as $validation) {
                DB::table('activities')->insert([
                    'school_id' => $validation->school_id,
                    'summary' => 'Validation finding on file',
                    'detail' => $validation->finding,
                    'occurred_at' => $validation->created_at ?? $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        if (Schema::hasTable('technical_assistances')) {
            foreach (DB::table('technical_assistances')->orderBy('id')->get() as $action) {
                DB::table('activities')->insert([
                    'school_id' => $action->school_id,
                    'summary' => 'Technical assistance on file',
                    'detail' => $action->concern.' · '.$action->status,
                    'occurred_at' => $action->created_at ?? $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $hazard = DB::table('hazard_events')->where('is_active', true)->orderByDesc('id')->first();

        if ($hazard !== null) {
            DB::table('activities')->insert([
                'summary' => 'Active condition on file',
                'detail' => $hazard->condition.' · '.$hazard->level,
                'occurred_at' => $hazard->starts_on ?? $hazard->created_at ?? $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
