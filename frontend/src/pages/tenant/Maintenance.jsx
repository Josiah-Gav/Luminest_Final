import { useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import toast from "react-hot-toast";
import { getMyRequests, resolveRequest } from "../../api/maintenance";
import Badge from "../../components/maintenance/Badge";
import NewRequestDialog from "../../components/maintenance/NewRequestDialog";
import Button from "../../components/ui/Button";
import Card from "../../components/ui/Card";
import Spinner from "../../components/ui/Spinner";
import { date } from "../../utils/format";
import { CATEGORY, MAINTENANCE_STATUS, PRIORITY } from "../../utils/status";

// Tenant, route /tenant/maintenance.
export default function Maintenance() {
    const queryClient = useQueryClient();
    const [creating, setCreating] = useState(false);

    // GET /api/tenant/maintenance-requests → Tenant\MaintenanceRequestController@index → MaintenanceRequestResource
    const query = useQuery({
        queryKey: ["tenant-requests"],
        queryFn: getMyRequests,
    });

    // POST /api/tenant/maintenance-requests/{id}/resolve → ...@resolve → MaintenanceService::resolve()
    async function handleResolve(request) {
        if (!window.confirm(`Confirm that "${request.title}" is fixed?`))
            return;

        try {
            await resolveRequest(request.id);
            queryClient.invalidateQueries({ queryKey: ["tenant-requests"] });
            toast.success("Marked as resolved. Thank you!");
        } catch (error) {
            toast.error(
                error.response?.data?.message ?? "Something went wrong.",
            );
        }
    }

    return (
        <div className="max-w-4xl">
            <div className="flex flex-wrap items-center justify-between gap-4">
                <h1 className="font-display text-3xl font-bold text-ink">
                    Maintenance requests
                </h1>
                <Button onClick={() => setCreating(true)}>New request</Button>
            </div>

            {query.isPending && (
                <Spinner className="mt-8 size-8 text-brand-blue" />
            )}
            {query.isError && (
                <p className="mt-8 text-brand-red">
                    Couldn't load your requests. Please refresh the page.
                </p>
            )}
            {query.data?.length === 0 && (
                <Card className="mt-8 text-center text-gray-600">
                    You haven't sent any requests yet.
                </Card>
            )}

            <div className="mt-8 space-y-4">
                {query.data?.map((request) => (
                    <Card key={request.id} className="sm:p-6">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h2 className="text-lg font-semibold text-ink">
                                    {request.title}
                                </h2>
                                <p className="text-sm text-gray-500">
                                    {CATEGORY[request.category]} ·{" "}
                                    {request.house.house_type}, Block{" "}
                                    {request.house.block} Lot{" "}
                                    {request.house.lot} ·{" "}
                                    {date(request.created_at)}
                                </p>
                            </div>
                            <div className="flex gap-2">
                                <Badge style={PRIORITY[request.priority]} />
                                <Badge
                                    style={MAINTENANCE_STATUS[request.status]}
                                />
                            </div>
                        </div>
                        <p className="mt-3 text-gray-700">
                            {request.description}
                        </p>
                        {request.staff && (
                            <p className="mt-3 text-sm text-gray-500">
                                Assigned to {request.staff.name}
                            </p>
                        )}
                        {request.resolution_notes && (
                            <p className="mt-3 rounded-lg bg-gray-50 p-3 text-sm text-gray-700">
                                <strong>Staff notes:</strong>{" "}
                                {request.resolution_notes}
                            </p>
                        )}
                        {request.status === "completed" && (
                            <Button
                                className="mt-4"
                                onClick={() => handleResolve(request)}
                            >
                                Confirm resolved
                            </Button>
                        )}
                    </Card>
                ))}
            </div>

            {creating && (
                <NewRequestDialog onClose={() => setCreating(false)} />
            )}
        </div>
    );
}
