import client from "./client";

export const getUser = () => client.get("/api/user");
