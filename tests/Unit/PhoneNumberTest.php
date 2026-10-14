<?php

use App\Support\PhoneNumber;

// SYSTEM_DOCUMENTATION §6.5, kept in one place instead of the legacy app's four copies.
it('normalizes Philippine mobile numbers to 09XXXXXXXXX', function (?string $input, ?string $expected) {
    expect(PhoneNumber::normalize($input))->toBe($expected);
})->with([
    'local format stays' => ['09171234567', '09171234567'],
    'spaces and dashes are stripped' => ['0917-123 4567', '09171234567'],
    '+63 prefix' => ['+63 917 123 4567', '09171234567'],
    '63 prefix without plus' => ['639171234567', '09171234567'],
    'missing leading zero' => ['9171234567', '09171234567'],
    'other lengths keep their digits' => ['12345', '12345'],
    'empty becomes null' => ['', null],
    'only symbols becomes null' => [' - ', null],
    'null stays null' => [null, null],
]);
