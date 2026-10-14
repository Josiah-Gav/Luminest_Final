import { useQuery, useQueryClient } from "@tanstack/react-query";
import toast from "react-hot-toast";
import { getStaff } from "../../api/maintenance";
import { demoteStaff } from "../../api/manager";
import Button from "../../components/ui/Button";
import Card from "../../components/ui/Card";
import Spinner from "../../components/ui/Spinner";
import { CATEGORY } from "../../utils/status";

// Property manager, route /manager/staff.
export default function Staff() {
    const queryClient = useQueryClient();

    // GET /api/manager/staff → PropertyManager\StaffController@index → StaffResource (with request counts)
    const query = useQuery({ queryKey: ["manager-staff"], queryFn: getStaff });

    // DELETE /api/manager/staff/{id} → StaffController@destroy → StaffService::demote()
    async function handleDemote(member) {
        const ok = window.confirm(
            `Remove ${member.name} as maintenance staff? They become a prospect, and their ${member.active_requests_count} open request(s) go back to the queue.`,
        );
        if (!ok) return;

        try {
            await demoteStaff(member.id);
            queryClient.invalidateQueries({ queryKey: ["manager-staff"] });
            queryClient.invalidateQueries({
                queryKey: ["manager-maintenance"],
            });
            toast.success(`${member.name} is no longer maintenance staff.`);
        } catch (error) {
            toast.error(
                error.response?.data?.message ?? "Something went wrong.",
            );
        }
    }

    return (
        <div>
            <h1 className="font-display text-3xl font-bold text-ink">
                Maintenance staff
            </h1>

            <Card className="mt-6 overflow-x-auto p-0 sm:p-0">
                {query.isPending && (
                    <Spinner className="m-6 size-8 text-brand-blue" />
                )}
                {query.isError && (
                    <p className="p-6 text-brand-red">
                        Couldn't load the staff.
                    </p>
                )}
                {query.data?.length === 0 && (
                    <p className="p-6 text-gray-600">No active staff.</p>
                )}
                {query.data?.length > 0 && (
                    <table className="w-full text-left text-sm">
                        <thead className="bg-gray-50 text-gray-500">
                            <tr>
                                <th className="px-4 py-3 font-semibold">
                                    Name
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    Expertise
                                </th>
                                <th className="px-4 py-3 text-right font-semibold">
                                    Active requests
                                </th>
                                <th className="px-4 py-3 text-right font-semibold">
                                    Completed
                                </th>
                                <th className="px-4 py-3">
                                    <span className="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {query.data.map((member) => (
                                <tr key={member.id}>
                                    <td className="px-4 py-3 font-semibold text-ink">
                                        {member.name}
                                    </td>
                                    <td className="px-4 py-3">
                                        {CATEGORY[member.expertise]}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {member.active_requests_count}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {member.completed_requests_count}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <Button
                                            variant="secondary"
                                            onClick={() => handleDemote(member)}
                                        >
                                            Demote
                                        </Button>
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
