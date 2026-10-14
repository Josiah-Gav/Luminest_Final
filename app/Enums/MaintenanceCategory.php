<?php

namespace App\Enums;

/**
 * Also used for users.expertise: a staff member can take requests of their
 * own category, or any category when their expertise is general.
 */
enum MaintenanceCategory: string
{
    case Plumbing = 'plumbing';
    case Electrical = 'electrical';
    case Carpentry = 'carpentry';
    case Appliance = 'appliance';
    case General = 'general';
}
