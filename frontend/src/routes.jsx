import { Route, Routes } from "react-router-dom";
import GuestRoute from "./auth/GuestRoute";
import ProtectedRoute from "./auth/ProtectedRoute";
import RoleRoute from "./auth/RoleRoute";
import AppLayout from "./components/layout/AppLayout";
import Home from "./pages/public/Home";
import Login from "./pages/public/Login";
import NotFound from "./pages/public/NotFound";
import Register from "./pages/public/Register";
import DashboardPlaceholder from "./pages/shared/DashboardPlaceholder";
import Profile from "./pages/shared/Profile";

// The page map (plan §6). Later phases add their pages inside the matching RoleRoute.
export default function AppRoutes() {
    return (
        <Routes>
            <Route path="/" element={<Home />} />

            <Route element={<GuestRoute />}>
                <Route path="/login" element={<Login />} />
                <Route path="/register" element={<Register />} />
            </Route>

            <Route element={<ProtectedRoute />}>
                <Route element={<AppLayout />}>
                    <Route path="/profile" element={<Profile />} />

                    <Route element={<RoleRoute roles={["admin"]} />}>
                        <Route
                            path="/admin"
                            element={<DashboardPlaceholder />}
                        />
                    </Route>
                    <Route element={<RoleRoute roles={["property_manager"]} />}>
                        <Route
                            path="/manager"
                            element={<DashboardPlaceholder />}
                        />
                    </Route>
                    <Route
                        element={<RoleRoute roles={["maintenance_staff"]} />}
                    >
                        <Route
                            path="/staff"
                            element={<DashboardPlaceholder />}
                        />
                    </Route>
                    <Route element={<RoleRoute roles={["tenant"]} />}>
                        <Route
                            path="/tenant"
                            element={<DashboardPlaceholder />}
                        />
                    </Route>
                    <Route element={<RoleRoute roles={["prospect"]} />}>
                        <Route
                            path="/prospect"
                            element={<DashboardPlaceholder />}
                        />
                    </Route>
                </Route>
            </Route>

            <Route path="*" element={<NotFound />} />
        </Routes>
    );
}
