<?php

use App\Enums\HouseStatus;
use App\Enums\MaintenanceStatus;
use App\Enums\ReservationStatus;

/**
 * Checks every (from, to) pair of an enum against the list of allowed moves,
 * so a forbidden transition fails the test just like a missing allowed one.
 *
 * @param  class-string<BackedEnum>  $enum
 * @param  list<array{0: BackedEnum, 1: BackedEnum}>  $allowed
 */
function assertTransitions(string $enum, array $allowed): void
{
    foreach ($enum::cases() as $from) {
        foreach ($enum::cases() as $to) {
            $expected = in_array([$from, $to], $allowed, true);

            expect($from->canTransitionTo($to))
                ->toBe($expected, "{$from->value} → {$to->value}");
        }
    }
}

it('lets a reservation only go from reserved to cancelled or paid (§1.2)', function () {
    assertTransitions(ReservationStatus::class, [
        [ReservationStatus::Reserved, ReservationStatus::Cancelled],
        [ReservationStatus::Reserved, ReservationStatus::Paid],
    ]);
});

it('lets a house be reserved, released or sold, and nothing else', function () {
    assertTransitions(HouseStatus::class, [
        [HouseStatus::Available, HouseStatus::Reserved],
        [HouseStatus::Reserved, HouseStatus::Available],
        [HouseStatus::Reserved, HouseStatus::Sold],
    ]);
});

it('follows the maintenance lifecycle without the legacy shortcuts (defect #14)', function () {
    assertTransitions(MaintenanceStatus::class, [
        [MaintenanceStatus::Pending, MaintenanceStatus::Accepted],
        [MaintenanceStatus::Accepted, MaintenanceStatus::Pending],
        [MaintenanceStatus::InProgress, MaintenanceStatus::Pending],
        [MaintenanceStatus::Accepted, MaintenanceStatus::InProgress],
        [MaintenanceStatus::InProgress, MaintenanceStatus::Completed],
        [MaintenanceStatus::Completed, MaintenanceStatus::Resolved],
    ]);
});
