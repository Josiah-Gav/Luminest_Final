import { createContext } from "react";

// Filled by AuthProvider, read with useAuth().
export const AuthContext = createContext(null);

// The TanStack Query cache key that holds the logged-in user (or null).
export const USER_QUERY_KEY = ["user"];
