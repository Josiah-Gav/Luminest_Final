import { useQuery } from "@tanstack/react-query";
import { getManagerDashboard } from "../../api/manager";
import { useAuth } from "../../auth/useAuth";
import Badge from "../../components/maintenance/Badge";
import Card from "../../components/ui/Card";
import Spinner from "../../components/ui/Spinner";
import StatCard from "../../components/ui/StatCard";
import { date, peso } from "../../utils/format";
import { CATEGORY, MAINTENANCE_STATUS, PRIORITY } from "../../utils/status";

// Property manager, route /manager. Refreshes by itself every 30 seconds.
export default function Dashboard() {
    const { user } = useAuth();

    // GET /api/manager/dashboard → PropertyManager\DashboardController → JsonResource (counts, money, recent requests)
    const query = useQuery({
        queryKey: ["manager-dashboard"],
        queryFn: getManagerDashboard,
        refetchInterval: 30_000,
    });
    const d = query.data;

    return (
        <div className="max-w-6xl">
            <h1 className="font-display text-3xl font-bold text-ink">
                Welcome, {user.name}!
            </h1>

            {query.isPending && (
                <Spinner className="mt-8 size-8 text-brand-blue" />
            )}
            {query.isError && (
                <p className="mt-8 text-brand-red">
                    Couldn't load the dashboard. Please refresh the page.
                </p>
            )}

            {d && (
                <>
                    <h2 className="mt-8 font-display text-xl font-bold text-ink">
                        Money
                    </h2>
                    <div className="mt-4 grid gap-4 sm:grid-cols-3">
                        <StatCard
                            label="Collected"
                            value={peso(d.total_collected)}
                            hint="All payments received"
                        />
                        <StatCard
                            label="Outstanding"
                            value={peso(d.total_outstanding)}
                            hint="Still to pay on active reservations"
                        />
                        <StatCard
                            label="Active reservations"
                            value={d.active_reservations}
                        />
                    </div>

                    <h2 className="mt-10 font-display text-xl font-bold text-ink">
                        Houses
                    </h2>
                    <div className="mt-4 grid gap-4 sm:grid-cols-4">
                        <StatCard label="Total" value={d.houses.total} />
                        <StatCard
                            label="Available"
                            value={d.houses.available}
                        />
                        <StatCard label="Reserved" value={d.houses.reserved} />
                        <StatCard label="Sold" value={d.houses.sold} />
                    </div>

                    <h2 className="mt-10 font-display text-xl font-bold text-ink">
                        Maintenance
                    </h2>
                    <div className="mt-4 grid gap-4 sm:grid-cols-5">
                        {Object.entries(d.maintenance).map(
                            ([status, count]) => (
                                <StatCard
                                    key={status}
                                    label={MAINTENANCE_STATUS[status].label}
                                    value={count}
                                />
                            ),
                        )}
                    </div>

                    <h2 className="mt-10 font-display text-xl font-bold text-ink">
                        Latest requests
                    </h2>
                    <Card className="mt-4 overflow-x-auto p-0 sm:p-0">
                        {d.recent_requests.length === 0 && (
                            <p className="p-6 text-gray-600">
                                No requests yet.
                            </p>
                        )}
                        {d.recent_requests.length > 0 && (
                            <table className="w-full text-left text-sm">
                                <tbody className="divide-y divide-gray-100">
                                    {d.recent_requests.map((request) => (
                                        <tr key={request.id}>
                                            <td className="px-4 py-3">
                                                <p className="font-semibold text-ink">
                                                    {request.title}
                                                </p>
                                                <p className="text-gray-500">
                                                    {CATEGORY[request.category]}{" "}
                                                    · {request.tenant.name} ·{" "}
                                                    {date(request.created_at)}
                                                </p>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    style={
                                                        PRIORITY[
                                                            request.priority
                                                        ]
                                                    }
                                                />
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge
                                                    style={
                                                        MAINTENANCE_STATUS[
                                                            request.status
                                                        ]
                                                    }
                                                />
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </Card>
                </>
            )}
        </div>
    );
}
