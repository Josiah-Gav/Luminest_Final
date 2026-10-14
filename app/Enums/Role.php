<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case PropertyManager = 'property_manager';
    case MaintenanceStaff = 'maintenance_staff';
    case Tenant = 'tenant';
    case Prospect = 'prospect';
}
