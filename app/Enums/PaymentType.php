<?php

namespace App\Enums;

enum PaymentType: string
{
    case ReservationFee = 'reservation_fee';
    case Instalment = 'instalment';
    case Final = 'final';
}
