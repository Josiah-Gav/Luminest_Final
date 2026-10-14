// One place for status labels and badge colours (plan §8).
export const RESERVATION_STATUS = {
    reserved: { label: "Reserved", className: "bg-amber-100 text-amber-800" },
    paid: { label: "Paid", className: "bg-green-100 text-green-800" },
    cancelled: { label: "Cancelled", className: "bg-gray-100 text-gray-700" },
};

export const PAYMENT_TYPE = {
    reservation_fee: "Reservation fee",
    instalment: "Instalment",
    final: "Final payment",
};

export const MAINTENANCE_STATUS = {
    pending: { label: "Pending", className: "bg-amber-100 text-amber-800" },
    accepted: { label: "Assigned", className: "bg-blue-100 text-blue-800" },
    in_progress: { label: "In progress", className: "bg-sky-100 text-sky-800" },
    completed: { label: "Completed", className: "bg-green-100 text-green-800" },
    resolved: { label: "Resolved", className: "bg-gray-100 text-gray-700" },
};

export const PRIORITY = {
    low: { label: "Low", className: "bg-gray-100 text-gray-700" },
    medium: { label: "Medium", className: "bg-blue-100 text-blue-800" },
    high: { label: "High", className: "bg-amber-100 text-amber-800" },
    urgent: { label: "Urgent", className: "bg-red-100 text-red-800" },
};

// Same values as App\Enums\MaintenanceCategory (also used for staff expertise).
export const CATEGORY = {
    plumbing: "Plumbing",
    electrical: "Electrical",
    carpentry: "Carpentry",
    appliance: "Appliance",
    general: "General",
};
