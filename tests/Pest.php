<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function makeCluster(int $number): App\Models\Cluster
{
    return App\Models\Cluster::query()->create([
        'number' => $number,
        'supervisor_name' => 'Supervisor '.$number,
        'supervisor_title' => 'PSDS',
    ]);
}

function makeSchool(App\Models\Cluster $cluster, string $name, ?int $learners = 100, ?int $reached = 90): App\Models\School
{
    $school = App\Models\School::query()->create([
        'cluster_id' => $cluster->id,
        'name' => $name,
        'head_name' => 'Head',
    ]);

    $school->report()->create([
        'learners_total' => $learners,
        'learners_reached' => $reached,
        'flp_level' => App\Enums\FlpLevel::Hinay,
        'ta_needed' => $learners !== null && $reached !== null && ($reached / $learners) < 0.7,
    ]);

    return $school->fresh(['cluster', 'report']);
}
