<?php

declare(strict_types=1);

use App\ValueObjects\Season;
use Carbon\Carbon;
use Illuminate\Support\Facades\Date;

test('builds a season from its start year', function (): void {
    $season = Season::fromYears(2026);

    expect($season->label)->toBe('2026-2027')
        ->and($season->startYear)->toBe(2026)
        ->and($season->endYear)->toBe(2027);
});

test('parses a valid season label', function (): void {
    expect(Season::fromString('2024-2025'))->toEqual(Season::fromYears(2024));
});

test('rejects invalid season labels', function (string $label): void {
    expect(fn (): Season => Season::fromString($label))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'missing second year' => '2024',
    'mismatched years' => '2024-2026',
    'garbage' => 'not-a-season',
    'empty' => '',
]);

test('derives the season from a date', function (string $date, string $expected): void {
    expect(Season::fromDate(new Carbon($date))->label)->toBe($expected);
})->with([
    'July starts the new season' => ['2026-07-01', '2026-2027'],
    'June belongs to the previous season' => ['2026-06-30', '2025-2026'],
    'January belongs to the previous season' => ['2026-01-15', '2025-2026'],
    'November is mid-season' => ['2026-11-15', '2026-2027'],
    'season start day itself' => ['2026-07-31', '2026-2027'],
]);

test('compares seasons', function (): void {
    expect(Season::fromYears(2026)->equals(Season::fromString('2026-2027')))->toBeTrue()
        ->and(Season::fromYears(2026)->equals(Season::fromYears(2025)))->toBeFalse();
});

test('navigates between seasons', function (): void {
    $season = Season::fromYears(2026);

    expect($season->previous()->label)->toBe('2025-2026')
        ->and($season->next()->label)->toBe('2027-2028');
});

test('current season matches today', function (): void {
    Date::setTestNow('2026-08-30');

    expect(Season::current()->label)->toBe('2026-2027');
});

test('the constructor enforces consecutive years even when bypassed directly', function (): void {
    // fromYears() and fromString() can never produce a non-consecutive
    // season; the constructor guard is the last line of defense if a new
    // factory is ever added...
    $constructor = new ReflectionClass(Season::class)->getConstructor();

    expect(fn (): mixed => $constructor->invoke(
        new ReflectionClass(Season::class)->newInstanceWithoutConstructor(),
        '2024-2026',
        2024,
        2026,
    ))->toThrow(InvalidArgumentException::class);
});
