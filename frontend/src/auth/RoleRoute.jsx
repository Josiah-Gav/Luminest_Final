import { Navigate, Outlet } from "react-router-dom";
import { homeFor } from "./roles";
import { useAuth } from "./useAuth";

// Pages for some roles only. Anyone else goes back to their own dashboard.
// The API's role middleware still answers 403 if they call the endpoints directly.
export default function RoleRoute({ roles }) {
    const { user } = useAuth();

    if (!roles.includes(user.role))
        return <Navigate to={homeFor(user)} replace />;

    return <Outlet />;
}
