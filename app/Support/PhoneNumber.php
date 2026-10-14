<?php

namespace App\Support;

/**
 * Philippine mobile numbers are stored as 09XXXXXXXXX (SYSTEM_DOCUMENTATION §6.5).
 * The one copy of this rule; the legacy app had four.
 */
class PhoneNumber
{
    /**
     * "+63 917 123 4567", "639171234567" and "9171234567" all become "09171234567".
     * Other lengths keep their digits, so validation can reject them. Empty becomes null.
     */
    public static function normalize(?string $phone): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $phone);

        return match (true) {
            $digits === '' => null,
            strlen($digits) === 12 && str_starts_with($digits, '63') => '0'.substr($digits, 2),
            strlen($digits) === 10 && ! str_starts_with($digits, '0') => '0'.$digits,
            default => $digits,
        };
    }
}
