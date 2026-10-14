import client from "./client";

// M6 User Administration (plan §5.7).

export const getAdminDashboard = async () =>
    (await client.get("/api/admin/dashboard")).data.data;

// Paginated: returns { data: [...users], meta: { current_page, last_page, ... } }
export const getUsers = async ({ search, role, page }) =>
    (await client.get("/api/admin/users", { params: { search, role, page } }))
        .data;

export const getUser = async (id) =>
    (await client.get(`/api/admin/users/${id}`)).data.data;

export const createUser = async (details) =>
    (await client.post("/api/admin/users", details)).data.data;

// Partial: the activate/deactivate button sends only { is_active }.
export const updateUser = async (id, details) =>
    (await client.put(`/api/admin/users/${id}`, details)).data.data;

export const deleteUser = (id) => client.delete(`/api/admin/users/${id}`);
