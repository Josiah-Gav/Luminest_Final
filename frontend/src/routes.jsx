import { Route, Routes } from "react-router-dom";
import GuestRoute from "./auth/GuestRoute";
import ProtectedRoute from "./auth/ProtectedRoute";
import RoleRoute from "./auth/RoleRoute";
import AppLayout from "./components/layout/AppLayout";
import ManagerReservations from "./pages/manager/Reservations";
import MyReservations from "./pages/prospect/MyReservations";
import Home from "./pages/public/Home";
import HouseTypeDetail from "./pages/public/HouseTypeDetail";
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
            <Route path="/houses/:slug" element={<HouseTypeDetail />} />

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
                        <Route
                            path="/manager/reservations"
                            element={<ManagerReservations />}
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
                    {/* A tenant keeps seeing their purchase and receipts. */}
                    <Route
                        element={<RoleRoute roles={["prospect", "tenant"]} />}
                    >
                        <Route
                            path="/reservations"
                            element={<MyReservations />}
                        />
                    </Route>
                </Route>
            </Route>

            <Route path="*" element={<NotFound />} />
        </Routes>
    );
}
