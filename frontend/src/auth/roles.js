// Role values come from App\Enums\Role on the API.

export const ROLE_LABELS = {
    admin: "Admin",
    property_manager: "Property Manager",
    maintenance_staff: "Maintenance Staff",
    tenant: "Tenant",
    prospect: "Prospect",
};

// Where each role lands after login.
export const ROLE_HOMES = {
    admin: "/admin",
    property_manager: "/manager",
    maintenance_staff: "/staff",
    tenant: "/tenant",
    prospect: "/prospect",
};

export const homeFor = (user) => ROLE_HOMES[user.role] ?? "/";
