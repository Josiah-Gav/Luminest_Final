import { Navigate, Outlet, useLocation } from "react-router-dom";
import { useAuth } from "./useAuth";

// Logged-in users only. Remembers the page, so Login can send them back to it.
// This only hides pages; the API's auth:sanctum is the real guard.
export default function ProtectedRoute() {
    const { user } = useAuth();
    const location = useLocation();

    if (!user)
        return <Navigate to="/login" replace state={{ from: location }} />;

    return <Outlet />;
}
