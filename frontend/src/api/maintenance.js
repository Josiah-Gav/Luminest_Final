import client from "./client";

// M4 Maintenance (plan §5.4–5.6). Each function returns MaintenanceRequestResource data.

// Tenant
export const getMyHouses = async () =>
    (await client.get("/api/tenant/houses")).data.data;

export const getMyRequests = async () =>
    (await client.get("/api/tenant/maintenance-requests")).data.data;

export const submitRequest = async (details) =>
    (await client.post("/api/tenant/maintenance-requests", details)).data.data;

export const resolveRequest = async (id) =>
    (await client.post(`/api/tenant/maintenance-requests/${id}/resolve`)).data
        .data;

// Staff: scope is "active" or "history"
export const getAssignments = async (scope) =>
    (await client.get("/api/staff/maintenance-requests", { params: { scope } }))
        .data.data;

export const updateProgress = async (id, { status, resolution_notes }) =>
    (
        await client.patch(`/api/staff/maintenance-requests/${id}`, {
            status,
            resolution_notes,
        })
    ).data.data;

// Property manager: scope is "open" or "closed"
export const getQueue = async (scope) =>
    (
        await client.get("/api/manager/maintenance-requests", {
            params: { scope },
        })
    ).data.data;

export const getStaff = async () =>
    (await client.get("/api/manager/staff")).data.data;

// staffId null unassigns
export const assignStaff = async (id, staffId) =>
    (
        await client.patch(
            `/api/manager/maintenance-requests/${id}/assignment`,
            {
                staff_id: staffId,
            },
        )
    ).data.data;
