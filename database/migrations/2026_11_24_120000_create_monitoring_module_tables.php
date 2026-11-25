<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('learners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('net');
            $table->string('sim')->nullable();
            $table->timestamps();
        });

        Schema::create('modality_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('modality');
            $table->unsignedInteger('learners_total')->default(0);
            $table->unsignedInteger('learners_reached')->default(0);
            $table->timestamps();
            $table->unique(['school_id', 'modality']);
        });

        Schema::create('validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('finding');
            $table->string('severity');
            $table->timestamps();
        });

        Schema::create('technical_assistances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->string('concern');
            $table->text('action');
            $table->string('responsible');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('reach_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('recorded_on')->unique();
            $table->decimal('reach_rate', 5, 1);
            $table->unsignedInteger('learners');
            $table->unsignedInteger('reached');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reach_snapshots');
        Schema::dropIfExists('technical_assistances');
        Schema::dropIfExists('validations');
        Schema::dropIfExists('modality_counts');
        Schema::dropIfExists('learners');
        Schema::dropIfExists('sections');
    }
};
