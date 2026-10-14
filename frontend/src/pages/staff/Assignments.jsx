import { useQuery, useQueryClient } from "@tanstack/react-query";
import { useState } from "react";
import toast from "react-hot-toast";
import { getAssignments, updateProgress } from "../../api/maintenance";
import Badge from "../../components/maintenance/Badge";
import CompleteDialog from "../../components/maintenance/CompleteDialog";
import Button from "../../components/ui/Button";
import Card from "../../components/ui/Card";
import Spinner from "../../components/ui/Spinner";
import { date } from "../../utils/format";
import { CATEGORY, MAINTENANCE_STATUS, PRIORITY } from "../../utils/status";

// Maintenance staff, route /staff/requests: my active assignments and my history.
export default function Assignments() {
    const queryClient = useQueryClient();
    const [completing, setCompleting] = useState(null); // the request in the Complete modal

    // GET /api/staff/maintenance-requests?scope=active|history → Staff\MaintenanceRequestController@index
    // → MaintenanceRequestResource (the API only returns requests assigned to me)
    const active = useQuery({
        queryKey: ["staff-requests", "active"],
        queryFn: () => getAssignments("active"),
    });
    const history = useQuery({
        queryKey: ["staff-requests", "history"],
        queryFn: () => getAssignments("history"),
    });

    // PATCH /api/staff/maintenance-requests/{id} {status: "in_progress"} → ...@update → MaintenanceService::updateProgress()
    async function handleStart(request) {
        try {
            await updateProgress(request.id, { status: "in_progress" });
            queryClient.invalidateQueries({ queryKey: ["staff-requests"] });
            toast.success("Started. Good luck!");
        } catch (error) {
            toast.error(
                error.response?.data?.message ?? "Something went wrong.",
            );
        }
    }

    return (
        <div className="max-w-4xl">
            <h1 className="font-display text-3xl font-bold text-ink">
                Assigned requests
            </h1>

            <h2 className="mt-8 font-display text-xl font-bold text-ink">
                Active
            </h2>
            {active.isPending && (
                <Spinner className="mt-4 size-8 text-brand-blue" />
            )}
            {active.isError && (
                <p className="mt-4 text-brand-red">
                    Couldn't load your assignments.
                </p>
            )}
            {active.data?.length === 0 && (
                <Card className="mt-4 text-center text-gray-600">
                    Nothing assigned to you right now.
                </Card>
            )}
            <div className="mt-4 space-y-4">
                {active.data?.map((request) => (
                    <Card key={request.id} className="sm:p-6">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h3 className="text-lg font-semibold text-ink">
                                    {request.title}
                                </h3>
                                <p className="text-sm text-gray-500">
                                    {CATEGORY[request.category]} ·{" "}
                                    {request.house.house_type}, Block{" "}
                                    {request.house.block} Lot{" "}
                                    {request.house.lot} · for{" "}
                                    {request.tenant.name}
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
                        <div className="mt-4">
                            {request.status === "accepted" && (
                                <Button onClick={() => handleStart(request)}>
                                    Start
                                </Button>
                            )}
                            {request.status === "in_progress" && (
                                <Button onClick={() => setCompleting(request)}>
                                    Complete
                                </Button>
                            )}
                        </div>
                    </Card>
                ))}
            </div>

            <h2 className="mt-12 font-display text-xl font-bold text-ink">
                History
            </h2>
            {history.isPending && (
                <Spinner className="mt-4 size-8 text-brand-blue" />
            )}
            {history.data?.length === 0 && (
                <Card className="mt-4 text-center text-gray-600">
                    No finished requests yet.
                </Card>
            )}
            <div className="mt-4 space-y-3">
                {history.data?.map((request) => (
                    <Card key={request.id} className="sm:p-5">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h3 className="font-semibold text-ink">
                                    {request.title}
                                </h3>
                                <p className="text-sm text-gray-500">
                                    {request.house.house_type}, Block{" "}
                                    {request.house.block} Lot{" "}
                                    {request.house.lot} · completed{" "}
                                    {date(request.completed_at)}
                                </p>
                            </div>
                            <Badge style={MAINTENANCE_STATUS[request.status]} />
                        </div>
                        {request.resolution_notes && (
                            <p className="mt-2 text-sm text-gray-700">
                                {request.resolution_notes}
                            </p>
                        )}
                    </Card>
                ))}
            </div>

            {completing && (
                <CompleteDialog
                    request={completing}
                    onClose={() => setCompleting(null)}
                />
            )}
        </div>
    );
}
