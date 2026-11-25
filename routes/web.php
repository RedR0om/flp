<?php

use App\Http\Controllers\AboutController;
use App\Http\Controllers\HazardController;
use App\Http\Controllers\LearnerController;
use App\Http\Controllers\ModalityController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SchoolController;
use App\Http\Controllers\SchoolReportController;
use App\Http\Controllers\SchoolStatusController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\TechnicalAssistanceController;
use App\Http\Controllers\ValidationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', OverviewController::class)->name('dashboard');
    Route::get('schools', [SchoolStatusController::class, 'index'])->name('schools.index');
    Route::post('schools', [SchoolController::class, 'store'])->name('schools.store');
    Route::patch('schools/{school}/report', [SchoolReportController::class, 'update'])->name('schools.report.update');
    Route::delete('schools/{school}', [SchoolController::class, 'destroy'])->name('schools.destroy');

    Route::get('sections', [SectionController::class, 'index'])->name('sections.index');
    Route::post('sections', [SectionController::class, 'store'])->name('sections.store');
    Route::delete('sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy');
    Route::post('sections/{section}/learners', [LearnerController::class, 'store'])->name('learners.store');
    Route::patch('learners/{learner}', [LearnerController::class, 'update'])->name('learners.update');
    Route::delete('learners/{learner}', [LearnerController::class, 'destroy'])->name('learners.destroy');

    Route::get('modalities', [ModalityController::class, 'index'])->name('modalities.index');
    Route::patch('modalities', [ModalityController::class, 'update'])->name('modalities.update');

    Route::get('validations', [ValidationController::class, 'index'])->name('validations.index');
    Route::post('validations', [ValidationController::class, 'store'])->name('validations.store');
    Route::delete('validations/{validation}', [ValidationController::class, 'destroy'])->name('validations.destroy');

    Route::get('actions', [TechnicalAssistanceController::class, 'index'])->name('actions.index');
    Route::post('actions', [TechnicalAssistanceController::class, 'store'])->name('actions.store');
    Route::patch('actions/{technicalAssistance}', [TechnicalAssistanceController::class, 'update'])->name('actions.update');
    Route::delete('actions/{technicalAssistance}', [TechnicalAssistanceController::class, 'destroy'])->name('actions.destroy');

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/schools.csv', [ReportController::class, 'schools'])->name('reports.schools');
    Route::get('reports/actions.csv', [ReportController::class, 'actions'])->name('reports.actions');

    Route::get('hazards', [HazardController::class, 'index'])->name('hazards.index');
    Route::patch('hazards', [HazardController::class, 'select'])->name('hazards.select');
    Route::post('hazards/apply', [HazardController::class, 'apply'])->name('hazards.apply');

    Route::get('about', AboutController::class)->name('about');
});

require __DIR__.'/settings.php';
