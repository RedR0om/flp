<?php

use App\Support\Reach;

test('reach rate is null when learner counts are missing', function () {
    expect(Reach::rate(null, 10))->toBeNull()
        ->and(Reach::rate(0, 0))->toBeNull()
        ->and(Reach::band(null))->toBe('none')
        ->and(Reach::label('none'))->toBe('No data yet');
});

test('reach bands follow the monitoring thresholds', function () {
    expect(Reach::band(100))->toBe('ex')
        ->and(Reach::band(95))->toBe('ex')
        ->and(Reach::band(94.9))->toBe('ok')
        ->and(Reach::band(90))->toBe('ok')
        ->and(Reach::band(89.9))->toBe('na')
        ->and(Reach::band(80))->toBe('na')
        ->and(Reach::band(79.9))->toBe('hp')
        ->and(Reach::band(70))->toBe('hp')
        ->and(Reach::band(69.9))->toBe('cr')
        ->and(Reach::isIssue('na'))->toBeTrue()
        ->and(Reach::isIssue('ok'))->toBeFalse();
});

test('reach rate rounds to one decimal place', function () {
    expect(Reach::rate(3, 2))->toBe(66.7)
        ->and(Reach::rate(2643, 2223))->toBe(84.1);
});
