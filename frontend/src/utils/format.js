// Money is whole pesos (plan §1.2 R8): 2495000 → "₱2,495,000".
export const peso = (amount) => `₱${Number(amount).toLocaleString("en-PH")}`;

// "2026-10-14T05:16:25Z" → "Oct 14, 2026"
export const date = (value) =>
    new Date(value).toLocaleDateString("en-PH", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
