import client from "./client";

// M1 endpoints (plan §5.1). Laravel API Resources wrap the payload in { data };
// these functions return the payload itself, so pages never see the HTTP layer.

// Starts a session and sets the XSRF-TOKEN cookie that axios echoes back on writes.
export const csrf = () => client.get("/sanctum/csrf-cookie");

export const getUser = async () => (await client.get("/api/user")).data.data;

export async function login(credentials) {
    await csrf();
    return (await client.post("/api/login", credentials)).data.data;
}

export async function register(details) {
    await csrf();
    return (await client.post("/api/register", details)).data.data;
}

export const logout = () => client.post("/api/logout");

export const updateProfile = async (details) =>
    (await client.put("/api/profile", details)).data.data;

export const changePassword = (passwords) =>
    client.put("/api/profile/password", passwords);
