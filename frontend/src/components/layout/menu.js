import {
    BuildingOffice2Icon,
    ClipboardDocumentListIcon,
    ClockIcon,
    CreditCardIcon,
    HomeIcon,
    HomeModernIcon,
    UserCircleIcon,
    UserGroupIcon,
    UsersIcon,
    WrenchScrewdriverIcon,
} from "@heroicons/react/24/outline";

// Sidebar items per role (plan §6 page map). `soon` marks pages that a later
// phase builds; they show greyed out until then.
const profile = { label: "My profile", to: "/profile", icon: UserCircleIcon };

export const MENU = {
    admin: [
        { label: "Dashboard", to: "/admin", icon: HomeIcon },
        { label: "Users", to: "/admin/users", icon: UsersIcon },
        profile,
    ],
    property_manager: [
        { label: "Dashboard", to: "/manager", icon: HomeIcon },
        {
            label: "Houses",
            to: "/manager/houses",
            icon: BuildingOffice2Icon,
        },
        {
            label: "Reservations",
            to: "/manager/reservations",
            icon: CreditCardIcon,
        },
        {
            label: "Tenants",
            to: "/manager/tenants",
            icon: UserGroupIcon,
        },
        {
            label: "Maintenance",
            to: "/manager/maintenance",
            icon: WrenchScrewdriverIcon,
        },
        {
            label: "Maintenance history",
            to: "/manager/maintenance/history",
            icon: ClockIcon,
        },
        { label: "Staff", to: "/manager/staff", icon: UsersIcon },
        profile,
    ],
    maintenance_staff: [
        {
            label: "Assigned requests",
            to: "/staff/requests",
            icon: ClipboardDocumentListIcon,
        },
        profile,
    ],
    tenant: [
        { label: "Dashboard", to: "/tenant", icon: HomeIcon },
        {
            label: "Maintenance requests",
            to: "/tenant/maintenance",
            icon: WrenchScrewdriverIcon,
        },
        { label: "My purchase", to: "/reservations", icon: CreditCardIcon },
        profile,
    ],
    prospect: [
        { label: "Dashboard", to: "/prospect", icon: HomeIcon },
        { label: "Browse houses", to: "/", icon: HomeModernIcon },
        { label: "My reservations", to: "/reservations", icon: CreditCardIcon },
        profile,
    ],
};
