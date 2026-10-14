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
