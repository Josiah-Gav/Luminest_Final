import { Dialog, DialogPanel, DialogTitle } from "@headlessui/react";
import { useQueryClient } from "@tanstack/react-query";
import { useForm } from "react-hook-form";
import toast from "react-hot-toast";
import { useNavigate } from "react-router-dom";
import { pay } from "../../api/reservations";
import { useAuth } from "../../auth/useAuth";
import { peso } from "../../utils/format";
import { applyServerErrors } from "../../utils/serverErrors";
import Button from "../ui/Button";
import FormError from "../ui/FormError";
import Input from "../ui/Input";

// "Pay" modal on My Reservations. Rendered only while open, so the form starts fresh each time.
export default function PayDialog({ reservation, onClose }) {
    const { refreshUser } = useAuth();
    const queryClient = useQueryClient();
    const navigate = useNavigate();
    const balance = reservation.balance;

    const form = useForm({
        defaultValues: { amount: Math.min(5000, balance) },
    });
    const {
        register,
        handleSubmit,
        setValue,
        formState: { errors, isSubmitting },
    } = form;

    // POST /api/reservations/{id}/payments → ReservationController@pay → ReservationService::pay()
    async function onSubmit(values) {
        try {
            const updated = await pay(reservation.id, Number(values.amount));
            queryClient.invalidateQueries({ queryKey: ["reservations"] });

            if (updated.status === "paid") {
                // R5: the house is sold and the prospect is now a tenant.
                await refreshUser();
                toast.success("Fully paid. Welcome home!");
                navigate("/tenant");
            } else {
                toast.success("Payment received. Your receipt is ready.");
                onClose();
            }
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
                        Make a payment
                    </DialogTitle>
                    <p className="mt-1 text-sm text-gray-600">
                        {reservation.house.house_type}, Block{" "}
                        {reservation.house.block} Lot {reservation.house.lot}.
                        Balance: <strong>{peso(balance)}</strong>
                    </p>

                    <form
                        onSubmit={handleSubmit(onSubmit)}
                        noValidate
                        className="mt-6 space-y-5"
                    >
                        <FormError message={errors.root?.server?.message} />
                        <Input
                            label="Amount (₱)"
                            type="number"
                            min="1"
                            step="1"
                            autoFocus
                            hint={
                                balance < 5000
                                    ? `Less than ₱5,000 is left, so pay exactly ${peso(balance)}.`
                                    : `At least ₱5,000, at most ${peso(balance)}.`
                            }
                            error={errors.amount?.message}
                            {...register("amount", {
                                required: "Enter an amount.",
                            })}
                        />
                        <button
                            type="button"
                            onClick={() => setValue("amount", balance)}
                            className="text-sm font-semibold text-brand-blue hover:underline"
                        >
                            Pay the full balance ({peso(balance)})
                        </button>

                        <div className="flex justify-end gap-3">
                            <Button variant="secondary" onClick={onClose}>
                                Close
                            </Button>
                            <Button type="submit" loading={isSubmitting}>
                                {isSubmitting ? "Paying…" : "Pay now"}
                            </Button>
                        </div>
                    </form>
                </DialogPanel>
            </div>
        </Dialog>
    );
}
