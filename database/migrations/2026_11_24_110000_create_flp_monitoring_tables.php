<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('clusters', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number')->unique();
            $table->string('supervisor_name');
            $table->string('supervisor_title');
            $table->timestamps();
        });

        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cluster_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('head_name')->nullable();
            $table->timestamps();
        });

        Schema::create('hazard_events', function (Blueprint $table) {
            $table->id();
            $table->string('condition');
            $table->string('level');
            $table->boolean('is_active')->default(false);
            $table->date('starts_on')->nullable();
            $table->timestamps();
        });

        Schema::create('school_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('hazard_event_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('learners_total')->nullable();
            $table->unsignedInteger('learners_reached')->nullable();
            $table->string('flp_level');
            $table->boolean('ta_needed')->default(false);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('cluster_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->foreignId('school_id')->nullable()->after('cluster_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('school_id');
            $table->dropConstrainedForeignId('cluster_id');
        });

        Schema::dropIfExists('school_reports');
        Schema::dropIfExists('hazard_events');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('clusters');
    }
};
