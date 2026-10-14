import { Dialog, DialogPanel, DialogTitle } from "@headlessui/react";
import { useQuery, useQueryClient } from "@tanstack/react-query";
import { useForm } from "react-hook-form";
import toast from "react-hot-toast";
import { getMyHouses, submitRequest } from "../../api/maintenance";
import { CATEGORY, PRIORITY } from "../../utils/status";
import { applyServerErrors } from "../../utils/serverErrors";
import Button from "../ui/Button";
import FormError from "../ui/FormError";
import Input from "../ui/Input";

const selectClass =
    "mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-ink focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 focus:outline-none";

// "New request" modal on the tenant's Maintenance page. Rendered only while open.
export default function NewRequestDialog({ onClose }) {
    const queryClient = useQueryClient();

    // GET /api/tenant/houses → Tenant\HouseController@index → HouseResource (only houses I own)
    const houses = useQuery({
        queryKey: ["tenant-houses"],
        queryFn: getMyHouses,
    });

    const form = useForm({
        defaultValues: {
            house_id: "",
            title: "",
            category: "general",
            priority: "medium",
            description: "",
        },
    });
    const {
        register,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = form;

    // A tenant with one house doesn't have to pick it.
    const onlyHouse = houses.data?.length === 1 ? houses.data[0].id : "";

    // POST /api/tenant/maintenance-requests → Tenant\MaintenanceRequestController@store → MaintenanceService::submit()
    async function onSubmit(values) {
        try {
            await submitRequest({
                ...values,
                house_id: Number(values.house_id || onlyHouse),
            });
            queryClient.invalidateQueries({ queryKey: ["tenant-requests"] });
            toast.success("Request sent. We'll assign someone soon.");
            onClose();
        } catch (error) {
            applyServerErrors(error, form);
        }
    }

    return (
        <Dialog open onClose={onClose} className="relative z-50">
            <div className="fixed inset-0 bg-ink/50" aria-hidden="true" />
            <div className="fixed inset-0 flex items-center justify-center overflow-y-auto p-4">
                <DialogPanel className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-xl">
                    <DialogTitle className="font-display text-2xl font-bold text-ink">
                        New maintenance request
                    </DialogTitle>

                    <form
                        onSubmit={handleSubmit(onSubmit)}
                        noValidate
                        className="mt-6 space-y-5"
                    >
                        <FormError message={errors.root?.server?.message} />

                        {houses.data?.length > 1 && (
                            <div>
                                <label
                                    htmlFor="house_id"
                                    className="block text-sm font-semibold text-ink"
                                >
                                    House
                                </label>
                                <select
                                    id="house_id"
                                    className={selectClass}
                                    {...register("house_id", {
                                        required: "Choose a house.",
                                    })}
                                >
                                    <option value="">Choose a house…</option>
                                    {houses.data.map((house) => (
                                        <option key={house.id} value={house.id}>
                                            {house.house_type}, Block{" "}
                                            {house.block} Lot {house.lot}
                                        </option>
                                    ))}
                                </select>
                                {errors.house_id && (
                                    <p className="mt-1.5 text-sm text-red-600">
                                        {errors.house_id.message}
                                    </p>
                                )}
                            </div>
                        )}
                        {houses.data?.length === 1 && (
                            <p className="text-sm text-gray-600">
                                For {houses.data[0].house_type}, Block{" "}
                                {houses.data[0].block} Lot {houses.data[0].lot}
                            </p>
                        )}
                        {errors.house_id && houses.data?.length <= 1 && (
                            <p className="text-sm text-red-600">
                                {errors.house_id.message}
                            </p>
                        )}

                        <Input
                            label="Title"
                            autoFocus
                            error={errors.title?.message}
                            {...register("title", {
                                required: "Enter a short title.",
                            })}
                        />

                        <div className="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label
                                    htmlFor="category"
                                    className="block text-sm font-semibold text-ink"
                                >
                                    Category
                                </label>
                                <select
                                    id="category"
                                    className={selectClass}
                                    {...register("category")}
                                >
                                    {Object.entries(CATEGORY).map(
                                        ([value, label]) => (
                                            <option key={value} value={value}>
                                                {label}
                                            </option>
                                        ),
                                    )}
                                </select>
                                {errors.category && (
                                    <p className="mt-1.5 text-sm text-red-600">
                                        {errors.category.message}
                                    </p>
                                )}
                            </div>
                            <div>
                                <label
                                    htmlFor="priority"
                                    className="block text-sm font-semibold text-ink"
                                >
                                    Priority
                                </label>
                                <select
                                    id="priority"
                                    className={selectClass}
                                    {...register("priority")}
                                >
                                    {Object.entries(PRIORITY).map(
                                        ([value, { label }]) => (
                                            <option key={value} value={value}>
                                                {label}
                                            </option>
                                        ),
                                    )}
                                </select>
                                {errors.priority && (
                                    <p className="mt-1.5 text-sm text-red-600">
                                        {errors.priority.message}
                                    </p>
                                )}
                            </div>
                        </div>

                        <div>
                            <label
                                htmlFor="description"
                                className="block text-sm font-semibold text-ink"
                            >
                                What's wrong?
                            </label>
                            <textarea
                                id="description"
                                rows={4}
                                aria-invalid={
                                    errors.description ? true : undefined
                                }
                                className={selectClass}
                                {...register("description", {
                                    required: "Describe the problem.",
                                })}
                            />
                            {errors.description && (
                                <p className="mt-1.5 text-sm text-red-600">
                                    {errors.description.message}
                                </p>
                            )}
                        </div>

                        <div className="flex justify-end gap-3">
                            <Button variant="secondary" onClick={onClose}>
                                Close
                            </Button>
                            <Button
                                type="submit"
                                loading={isSubmitting}
                                disabled={
                                    houses.isPending ||
                                    houses.data?.length === 0
                                }
                            >
                                {isSubmitting ? "Sending…" : "Send request"}
                            </Button>
                        </div>
                    </form>
                </DialogPanel>
            </div>
        </Dialog>
    );
}
