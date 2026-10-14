import { useQuery } from "@tanstack/react-query";
import { getAdminDashboard } from "../../api/admin";
import { ROLE_LABELS } from "../../auth/roles";
import { useAuth } from "../../auth/useAuth";
import Spinner from "../../components/ui/Spinner";
import StatCard from "../../components/ui/StatCard";

// Admin, route /admin.
export default function Dashboard() {
    const { user } = useAuth();

    // GET /api/admin/dashboard → Admin\DashboardController → JsonResource (counts)
    const query = useQuery({
        queryKey: ["admin-dashboard"],
        queryFn: getAdminDashboard,
    });

    return (
        <div className="max-w-5xl">
            <h1 className="font-display text-3xl font-bold text-ink">
                Welcome, {user.name}!
            </h1>
            <p className="mt-2 text-gray-600">Accounts at a glance.</p>

            {query.isPending && (
                <Spinner className="mt-8 size-8 text-brand-blue" />
            )}
            {query.isError && (
                <p className="mt-8 text-brand-red">
                    Couldn't load the numbers. Please refresh the page.
                </p>
            )}

            {query.data && (
                <>
                    <div className="mt-8 grid gap-4 sm:grid-cols-3">
                        <StatCard label="All users" value={query.data.total} />
                        <StatCard label="Active" value={query.data.active} />
                        <StatCard
                            label="Inactive"
                            value={query.data.inactive}
                        />
                    </div>

                    <h2 className="mt-10 font-display text-xl font-bold text-ink">
                        By role
                    </h2>
                    <div className="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                        {Object.entries(query.data.by_role).map(
                            ([role, count]) => (
                                <StatCard
                                    key={role}
                                    label={ROLE_LABELS[role]}
                                    value={count}
                                />
                            ),
                        )}
                    </div>
                </>
            )}
        </div>
    );
}
