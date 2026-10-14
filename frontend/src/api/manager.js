import client from "./client";

// M5 Property Management (plan §5.6). Maintenance and reservation calls are in
// api/maintenance.js and api/reservations.js.

export const getManagerDashboard = async () =>
    (await client.get("/api/manager/dashboard")).data.data;

// Paginated: returns { data: [...houses], meta: { current_page, last_page, ... } }
export const getHouses = async ({ search, status, house_type, page }) =>
    (
        await client.get("/api/manager/houses", {
            params: { search, status, house_type, page },
        })
    ).data;

export const getTenants = async (search) =>
    (await client.get("/api/manager/tenants", { params: { search } })).data
        .data;

// Demote: the staff member becomes a prospect and their open requests go back to pending.
export const demoteStaff = (id) => client.delete(`/api/manager/staff/${id}`);
