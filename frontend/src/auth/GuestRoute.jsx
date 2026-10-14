import { Navigate, Outlet, useLocation } from "react-router-dom";
import { homeFor } from "./roles";
import { useAuth } from "./useAuth";

// Login and Register. Once someone is logged in (also right after submitting
// either form), send them to the page they wanted, or to their dashboard.
export default function GuestRoute() {
    const { user } = useAuth();
    const location = useLocation();

    if (user) {
        return (
            <Navigate
                to={location.state?.from?.pathname ?? homeFor(user)}
                replace
            />
        );
    }

    return <Outlet />;
}
