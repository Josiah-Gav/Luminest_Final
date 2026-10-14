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
        { label: "Users", to: "/admin/users", icon: UsersIcon, soon: true },
        profile,
    ],
    property_manager: [
        { label: "Dashboard", to: "/manager", icon: HomeIcon },
        {
            label: "Houses",
            to: "/manager/houses",
            icon: BuildingOffice2Icon,
            soon: true,
        },
        {
            label: "Reservations",
            to: "/manager/reservations",
            icon: CreditCardIcon,
            soon: true,
        },
        {
            label: "Tenants",
            to: "/manager/tenants",
            icon: UserGroupIcon,
            soon: true,
        },
        {
            label: "Maintenance",
            to: "/manager/maintenance",
            icon: WrenchScrewdriverIcon,
            soon: true,
        },
        {
            label: "Maintenance history",
            to: "/manager/maintenance/history",
            icon: ClockIcon,
            soon: true,
        },
        { label: "Staff", to: "/manager/staff", icon: UsersIcon, soon: true },
        profile,
    ],
    maintenance_staff: [
        { label: "Dashboard", to: "/staff", icon: HomeIcon },
        {
            label: "Assigned requests",
            to: "/staff/requests",
            icon: ClipboardDocumentListIcon,
            soon: true,
        },
        { label: "History", to: "/staff/history", icon: ClockIcon, soon: true },
        profile,
    ],
    tenant: [
        { label: "Dashboard", to: "/tenant", icon: HomeIcon },
        {
            label: "Maintenance requests",
            to: "/tenant/maintenance",
            icon: WrenchScrewdriverIcon,
            soon: true,
        },
        profile,
    ],
    prospect: [
        { label: "Dashboard", to: "/prospect", icon: HomeIcon },
        {
            label: "Reserve a unit",
            to: "/prospect/reserve",
            icon: HomeModernIcon,
            soon: true,
        },
        {
            label: "My reservations",
            to: "/prospect/reservations",
            icon: CreditCardIcon,
            soon: true,
        },
        profile,
    ],
};
