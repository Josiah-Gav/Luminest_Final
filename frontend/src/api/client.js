import axios from "axios";

// The one HTTP client for the whole app. Every request in src/api/* goes through it.
const client = axios.create({
    baseURL: import.meta.env.VITE_API_URL,
    // Send and accept cookies (Laravel session + XSRF-TOKEN) on cross-origin requests to the API.
    withCredentials: true,
    // Copy the XSRF-TOKEN cookie into the X-XSRF-TOKEN header. Axios only does this for
    // same-origin requests by default, and :5173 -> :8000 is cross-origin.
    withXSRFToken: true,
});

export default client;
