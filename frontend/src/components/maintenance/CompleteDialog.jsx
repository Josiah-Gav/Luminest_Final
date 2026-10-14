import { Dialog, DialogPanel, DialogTitle } from "@headlessui/react";
import { useQueryClient } from "@tanstack/react-query";
import { useForm } from "react-hook-form";
import toast from "react-hot-toast";
import { updateProgress } from "../../api/maintenance";
import { applyServerErrors } from "../../utils/serverErrors";
import Button from "../ui/Button";
import FormError from "../ui/FormError";

// Staff "Complete" modal with the optional resolution notes. Rendered only while open.
export default function CompleteDialog({ request, onClose }) {
    const queryClient = useQueryClient();
    const form = useForm({ defaultValues: { resolution_notes: "" } });
    const {
        register,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = form;

    // PATCH /api/staff/maintenance-requests/{id} → Staff\MaintenanceRequestController@update → MaintenanceService::updateProgress()
    async function onSubmit(values) {
        try {
            await updateProgress(request.id, {
                status: "completed",
                resolution_notes: values.resolution_notes || null,
            });
            queryClient.invalidateQueries({ queryKey: ["staff-requests"] });
            toast.success("Marked as completed. The tenant will confirm.");
            onClose();
        } catch (error) {
            applyServerErrors(error, form);
        }
    }

    return (
        <Dialog open onClose={onClose} className="relative z-50">
            <div className="fixed inset-0 bg-ink/50" aria-hidden="true" />
            <div className="fixed inset-0 flex items-center justify-center p-4">
                <DialogPanel className="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
                    <DialogTitle className="font-display text-2xl font-bold text-ink">
                        Complete this request
                    </DialogTitle>
                    <p className="mt-1 text-sm text-gray-600">
                        {request.title}
                    </p>

                    <form
                        onSubmit={handleSubmit(onSubmit)}
                        noValidate
                        className="mt-6 space-y-5"
                    >
                        <FormError message={errors.root?.server?.message} />
                        <div>
                            <label
                                htmlFor="resolution_notes"
                                className="block text-sm font-semibold text-ink"
                            >
                                Notes for the tenant (optional)
                            </label>
                            <textarea
                                id="resolution_notes"
                                rows={4}
                                autoFocus
                                className="mt-1.5 block w-full rounded-lg border border-gray-300 px-3.5 py-2.5 text-ink focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 focus:outline-none"
                                {...register("resolution_notes")}
                            />
                            {errors.resolution_notes && (
                                <p className="mt-1.5 text-sm text-red-600">
                                    {errors.resolution_notes.message}
                                </p>
                            )}
                        </div>
                        <div className="flex justify-end gap-3">
                            <Button variant="secondary" onClick={onClose}>
                                Close
                            </Button>
                            <Button type="submit" loading={isSubmitting}>
                                {isSubmitting ? "Saving…" : "Mark completed"}
                            </Button>
                        </div>
                    </form>
                </DialogPanel>
            </div>
        </Dialog>
    );
}
