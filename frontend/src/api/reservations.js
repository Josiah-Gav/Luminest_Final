import client from "./client";

// M3 Reservation & Purchase (plan §5.3). Each function returns ReservationResource data.

export const getMyReservations = async () =>
    (await client.get("/api/reservations")).data.data;

export const reserve = async ({ house_id, fee }) =>
    (await client.post("/api/reservations", { house_id, fee })).data.data;

export const pay = async (reservationId, amount) =>
    (
        await client.post(`/api/reservations/${reservationId}/payments`, {
            amount,
        })
    ).data.data;

export const cancel = async (reservationId) =>
    (await client.post(`/api/reservations/${reservationId}/cancel`)).data.data;

// Property manager, read-only (R7).
export const getAllReservations = async () =>
    (await client.get("/api/manager/reservations")).data.data;

// A plain link: the browser sends the session cookie, the API answers with the PDF.
export const receiptUrl = (paymentId) =>
    `${import.meta.env.VITE_API_URL}/api/payments/${paymentId}/receipt`;
