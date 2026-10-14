import { useContext } from "react";
import { AuthContext } from "./AuthContext";

/** { user, isLoading, login, register, logout, updateProfile, refreshUser } from AuthProvider. */
export function useAuth() {
    const auth = useContext(AuthContext);
    if (!auth) throw new Error("useAuth() must be used inside <AuthProvider>.");
    return auth;
}
