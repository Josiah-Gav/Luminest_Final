import { useQuery, useQueryClient } from "@tanstack/react-query";
import toast from "react-hot-toast";
import { assignStaff, getQueue, getStaff } from "../../api/maintenance";
import Badge from "../../components/maintenance/Badge";
import Card from "../../components/ui/Card";
import Spinner from "../../components/ui/Spinner";
import { date } from "../../utils/format";
import { CATEGORY, MAINTENANCE_STATUS, PRIORITY } from "../../utils/status";

// Property manager, route /manager/maintenance: the open queue with the assign dropdown.
export default function Maintenance() {
    const queryClient = useQueryClient();

    // GET /api/manager/maintenance-requests?scope=open → PropertyManager\MaintenanceRequestController@index
    const queue = useQuery({
        queryKey: ["manager-maintenance", "open"],
        queryFn: () => getQueue("open"),
    });
    // GET /api/manager/staff → PropertyManager\StaffController@index → StaffResource (id, name, expertise)
    const staff = useQuery({ queryKey: ["manager-staff"], queryFn: getStaff });

    // PATCH /api/manager/maintenance-requests/{id}/assignment → ...@assign → MaintenanceService::assign()
    async function handleAssign(request, value) {
        try {
            await assignStaff(request.id, value ? Number(value) : null);
            queryClient.invalidateQueries({
                queryKey: ["manager-maintenance"],
            });
            toast.success(value ? "Staff assigned." : "Request unassigned.");
        } catch (error) {
            toast.error(
                error.response?.data?.errors?.staff_id?.[0] ??
                    "Something went wrong.",
            );
        }
    }

    // Only staff whose expertise fits the category (or generalists) are offered. The API checks it again.
    const staffFor = (request) =>
        (staff.data ?? []).filter(
            (s) =>
                s.expertise === request.category || s.expertise === "general",
        );

    return (
        <div>
            <h1 className="font-display text-3xl font-bold text-ink">
                Maintenance
            </h1>
            <p className="mt-2 text-gray-600">
                Open requests. Staff can be changed until they start working on
                it.
            </p>

            <Card className="mt-8 overflow-x-auto p-0 sm:p-0">
                {queue.isPending && (
                    <Spinner className="m-6 size-8 text-brand-blue" />
                )}
                {queue.isError && (
                    <p className="p-6 text-brand-red">
                        Couldn't load the requests.
                    </p>
                )}
                {queue.data?.length === 0 && (
                    <p className="p-6 text-gray-600">No open requests.</p>
                )}
                {queue.data?.length > 0 && (
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
                                    Priority
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    Status
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    Assigned to
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {queue.data.map((request) => {
                                const canAssign =
                                    request.status === "pending" ||
                                    request.status === "accepted";
                                return (
                                    <tr key={request.id}>
                                        <td className="px-4 py-3">
                                            <p className="font-semibold text-ink">
                                                {request.title}
                                            </p>
                                            <p className="text-gray-500">
                                                {CATEGORY[request.category]} ·{" "}
                                                {date(request.created_at)}
                                            </p>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            {request.tenant.name}
                                            <br />
                                            <span className="text-gray-500">
                                                Block {request.house.block} ·
                                                Lot {request.house.lot}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            <Badge
                                                style={
                                                    PRIORITY[request.priority]
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
                                        <td className="px-4 py-3">
                                            {canAssign ? (
                                                <select
                                                    aria-label={`Assign staff to ${request.title}`}
                                                    value={
                                                        request.staff?.id ?? ""
                                                    }
                                                    onChange={(e) =>
                                                        handleAssign(
                                                            request,
                                                            e.target.value,
                                                        )
                                                    }
                                                    className="rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 focus:outline-none"
                                                >
                                                    <option value="">
                                                        Unassigned
                                                    </option>
                                                    {staffFor(request).map(
                                                        (s) => (
                                                            <option
                                                                key={s.id}
                                                                value={s.id}
                                                            >
                                                                {s.name} (
                                                                {
                                                                    CATEGORY[
                                                                        s
                                                                            .expertise
                                                                    ]
                                                                }
                                                                )
                                                            </option>
                                                        ),
                                                    )}
                                                </select>
                                            ) : (
                                                request.staff?.name
                                            )}
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                )}
            </Card>
        </div>
    );
}
