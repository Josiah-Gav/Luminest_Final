import { Navigate, Route, Routes } from "react-router-dom";
import GuestRoute from "./auth/GuestRoute";
import ProtectedRoute from "./auth/ProtectedRoute";
import RoleRoute from "./auth/RoleRoute";
import AppLayout from "./components/layout/AppLayout";
import AdminDashboard from "./pages/admin/Dashboard";
import AdminUserDetail from "./pages/admin/UserDetail";
import AdminUserForm from "./pages/admin/UserForm";
import AdminUsers from "./pages/admin/Users";
import ManagerDashboard from "./pages/manager/Dashboard";
import ManagerHouses from "./pages/manager/Houses";
import ManagerMaintenance from "./pages/manager/Maintenance";
import ManagerMaintenanceHistory from "./pages/manager/MaintenanceHistory";
import ManagerReservations from "./pages/manager/Reservations";
import ManagerStaff from "./pages/manager/Staff";
import ManagerTenants from "./pages/manager/Tenants";
import MyReservations from "./pages/prospect/MyReservations";
import Home from "./pages/public/Home";
import HouseTypeDetail from "./pages/public/HouseTypeDetail";
import Login from "./pages/public/Login";
import NotFound from "./pages/public/NotFound";
import Register from "./pages/public/Register";
import Profile from "./pages/shared/Profile";
import Welcome from "./pages/shared/Welcome";
import StaffAssignments from "./pages/staff/Assignments";
import TenantMaintenance from "./pages/tenant/Maintenance";

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
                        <Route path="/admin" element={<AdminDashboard />} />
                        <Route path="/admin/users" element={<AdminUsers />} />
                        <Route
                            path="/admin/users/new"
                            element={<AdminUserForm />}
                        />
                        <Route
                            path="/admin/users/:id"
                            element={<AdminUserDetail />}
                        />
                        <Route
                            path="/admin/users/:id/edit"
                            element={<AdminUserForm />}
                        />
                    </Route>
                    <Route element={<RoleRoute roles={["property_manager"]} />}>
                        <Route path="/manager" element={<ManagerDashboard />} />
                        <Route
                            path="/manager/houses"
                            element={<ManagerHouses />}
                        />
                        <Route
                            path="/manager/tenants"
                            element={<ManagerTenants />}
                        />
                        <Route
                            path="/manager/staff"
                            element={<ManagerStaff />}
                        />
                        <Route
                            path="/manager/reservations"
                            element={<ManagerReservations />}
                        />
                        <Route
                            path="/manager/maintenance"
                            element={<ManagerMaintenance />}
                        />
                        <Route
                            path="/manager/maintenance/history"
                            element={<ManagerMaintenanceHistory />}
                        />
                    </Route>
                    <Route
                        element={<RoleRoute roles={["maintenance_staff"]} />}
                    >
                        <Route
                            path="/staff"
                            element={<Navigate to="/staff/requests" replace />}
                        />
                        <Route
                            path="/staff/requests"
                            element={<StaffAssignments />}
                        />
                    </Route>
                    <Route element={<RoleRoute roles={["tenant"]} />}>
                        <Route path="/tenant" element={<Welcome />} />
                        <Route
                            path="/tenant/maintenance"
                            element={<TenantMaintenance />}
                        />
                    </Route>
                    <Route element={<RoleRoute roles={["prospect"]} />}>
                        <Route path="/prospect" element={<Welcome />} />
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
