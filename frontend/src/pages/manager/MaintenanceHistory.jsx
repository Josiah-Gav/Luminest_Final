import { useQuery } from "@tanstack/react-query";
import { getQueue } from "../../api/maintenance";
import Card from "../../components/ui/Card";
import Spinner from "../../components/ui/Spinner";
import { date } from "../../utils/format";
import { CATEGORY } from "../../utils/status";

// Property manager, route /manager/maintenance/history: resolved requests, read-only.
export default function MaintenanceHistory() {
    // GET /api/manager/maintenance-requests?scope=closed → PropertyManager\MaintenanceRequestController@index
    const query = useQuery({
        queryKey: ["manager-maintenance", "closed"],
        queryFn: () => getQueue("closed"),
    });

    return (
        <div>
            <h1 className="font-display text-3xl font-bold text-ink">
                Maintenance history
            </h1>
            <p className="mt-2 text-gray-600">
                Requests the tenant has confirmed as resolved.
            </p>

            <Card className="mt-8 overflow-x-auto p-0 sm:p-0">
                {query.isPending && (
                    <Spinner className="m-6 size-8 text-brand-blue" />
                )}
                {query.isError && (
                    <p className="p-6 text-brand-red">
                        Couldn't load the history.
                    </p>
                )}
                {query.data?.length === 0 && (
                    <p className="p-6 text-gray-600">Nothing resolved yet.</p>
                )}
                {query.data?.length > 0 && (
                    <table className="w-full text-left text-sm">
                        <thead className="bg-gray-50 text-gray-500">
                            <tr>
                                <th className="px-4 py-3 font-semibold">
                                    Request
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    Tenant / unit
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    Staff
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    Notes
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    Resolved
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {query.data.map((request) => (
                                <tr key={request.id}>
                                    <td className="px-4 py-3">
                                        <p className="font-semibold text-ink">
                                            {request.title}
                                        </p>
                                        <p className="text-gray-500">
                                            {CATEGORY[request.category]}
                                        </p>
                                    </td>
                                    <td className="px-4 py-3 whitespace-nowrap">
                                        {request.tenant.name}
                                        <br />
                                        <span className="text-gray-500">
                                            Block {request.house.block} · Lot{" "}
                                            {request.house.lot}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3">
                                        {request.staff?.name}
                                    </td>
                                    <td className="px-4 py-3 text-gray-700">
                                        {request.resolution_notes}
                                    </td>
                                    <td className="px-4 py-3 whitespace-nowrap">
                                        {date(request.resolved_at)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </Card>
        </div>
    );
}
