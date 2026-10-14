import { useQuery, useQueryClient } from "@tanstack/react-query";
import clsx from "clsx";
import { useState } from "react";
import toast from "react-hot-toast";
import { Link } from "react-router-dom";
import { cancel, getMyReservations, receiptUrl } from "../../api/reservations";
import PayDialog from "../../components/reservations/PayDialog";
import Button from "../../components/ui/Button";
import Card from "../../components/ui/Card";
import Spinner from "../../components/ui/Spinner";
import { date, peso } from "../../utils/format";
import { PAYMENT_TYPE, RESERVATION_STATUS } from "../../utils/status";

// Route /reservations, for prospects ("My reservations") and tenants ("My purchase").
export default function MyReservations() {
    const queryClient = useQueryClient();
    const [paying, setPaying] = useState(null); // the reservation in the Pay modal

    // GET /api/reservations → ReservationController@index → ReservationResource (with payments)
    const query = useQuery({
        queryKey: ["reservations"],
        queryFn: getMyReservations,
    });

    // POST /api/reservations/{id}/cancel → ReservationController@cancel → ReservationService::cancel()
    async function handleCancel(reservation) {
        const ok = window.confirm(
            `Cancel your reservation of Block ${reservation.house.block} Lot ${reservation.house.lot}? ` +
                `The ${peso(reservation.total_paid)} you paid is non-refundable.`,
        );
        if (!ok) return;

        try {
            await cancel(reservation.id);
            queryClient.invalidateQueries({ queryKey: ["reservations"] });
            queryClient.invalidateQueries({ queryKey: ["house-types"] });
            toast.success(
                "Reservation cancelled. The unit is available again.",
            );
        } catch (error) {
            toast.error(error.response?.data?.message ?? "Cancelling failed.");
        }
    }

    if (query.isPending) return <Spinner className="size-8 text-brand-blue" />;
    if (query.isError) {
        return (
            <p className="text-brand-red">
                Couldn't load your reservations. Please refresh the page.
            </p>
        );
    }

    return (
        <div className="max-w-5xl">
            <h1 className="font-display text-3xl font-bold text-ink">
                My reservations
            </h1>

            {query.data.length === 0 && (
                <Card className="mt-8 text-center">
                    <p className="text-gray-600">
                        You have no reservations yet.
                    </p>
                    <Button as={Link} to="/" className="mt-4">
                        Browse house models
                    </Button>
                </Card>
            )}

            <div className="mt-8 space-y-6">
                {query.data.map((reservation) => {
                    const status = RESERVATION_STATUS[reservation.status];
                    const percent = Math.round(
                        (reservation.total_paid / reservation.total_price) *
                            100,
                    );

                    return (
                        <Card key={reservation.id}>
                            <div className="flex flex-wrap items-start justify-between gap-4">
                                <div>
                                    <h2 className="font-display text-2xl font-bold text-ink">
                                        {reservation.house.house_type}
                                    </h2>
                                    <p className="text-gray-600">
                                        Block {reservation.house.block} · Lot{" "}
                                        {reservation.house.lot} · reserved{" "}
                                        {date(reservation.created_at)}
                                    </p>
                                </div>
                                <span
                                    className={clsx(
                                        "rounded-full px-3 py-1 text-sm font-semibold",
                                        status.className,
                                    )}
                                >
                                    {status.label}
                                </span>
                            </div>

                            <dl className="mt-6 grid grid-cols-3 gap-4">
                                <div>
                                    <dt className="text-sm text-gray-500">
                                        Total
                                    </dt>
                                    <dd className="font-semibold">
                                        {peso(reservation.total_price)}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-gray-500">
                                        Paid
                                    </dt>
                                    <dd className="font-semibold text-green-700">
                                        {peso(reservation.total_paid)}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-gray-500">
                                        Balance
                                    </dt>
                                    <dd className="font-semibold">
                                        {peso(reservation.balance)}
                                    </dd>
                                </div>
                            </dl>
                            <div className="mt-3 h-2 overflow-hidden rounded-full bg-gray-100">
                                <div
                                    className="h-full rounded-full bg-brand-blue"
                                    style={{ width: `${percent}%` }}
                                />
                            </div>

                            {reservation.status === "reserved" && (
                                <div className="mt-6 flex flex-wrap gap-3">
                                    <Button
                                        onClick={() => setPaying(reservation)}
                                    >
                                        Pay
                                    </Button>
                                    <Button
                                        variant="secondary"
                                        onClick={() =>
                                            handleCancel(reservation)
                                        }
                                    >
                                        Cancel reservation
                                    </Button>
                                </div>
                            )}

                            <h3 className="mt-8 font-semibold text-ink">
                                Payments
                            </h3>
                            <div className="mt-2 overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="text-gray-500">
                                        <tr>
                                            <th className="py-2 pr-4 font-semibold">
                                                Date
                                            </th>
                                            <th className="py-2 pr-4 font-semibold">
                                                Receipt no.
                                            </th>
                                            <th className="py-2 pr-4 font-semibold">
                                                Type
                                            </th>
                                            <th className="py-2 pr-4 text-right font-semibold">
                                                Amount
                                            </th>
                                            <th className="py-2 pr-4 text-right font-semibold">
                                                Balance after
                                            </th>
                                            <th className="py-2" />
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-100">
                                        {reservation.payments.map((payment) => (
                                            <tr key={payment.id}>
                                                <td className="py-2 pr-4 whitespace-nowrap">
                                                    {date(payment.paid_at)}
                                                </td>
                                                <td className="py-2 pr-4 whitespace-nowrap">
                                                    {payment.receipt_number}
                                                </td>
                                                <td className="py-2 pr-4">
                                                    {PAYMENT_TYPE[payment.type]}
                                                </td>
                                                <td className="py-2 pr-4 text-right">
                                                    {peso(payment.amount)}
                                                </td>
                                                <td className="py-2 pr-4 text-right">
                                                    {peso(
                                                        payment.balance_after,
                                                    )}
                                                </td>
                                                <td className="py-2 text-right whitespace-nowrap">
                                                    <a
                                                        href={receiptUrl(
                                                            payment.id,
                                                        )}
                                                        className="font-semibold text-brand-blue hover:underline"
                                                    >
                                                        Download receipt
                                                    </a>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </Card>
                    );
                })}
            </div>

            {paying && (
                <PayDialog
                    reservation={paying}
                    onClose={() => setPaying(null)}
                />
            )}
        </div>
    );
}
