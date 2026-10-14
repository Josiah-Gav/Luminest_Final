import { useQuery } from "@tanstack/react-query";
import clsx from "clsx";
import { getAllReservations, receiptUrl } from "../../api/reservations";
import Card from "../../components/ui/Card";
import Spinner from "../../components/ui/Spinner";
import { date, peso } from "../../utils/format";
import { RESERVATION_STATUS } from "../../utils/status";

// Property manager, route /manager/reservations. Read-only (R7): no buttons that change anything.
export default function Reservations() {
    // GET /api/manager/reservations → PropertyManager\ReservationController@index → ReservationResource
    const query = useQuery({
        queryKey: ["manager-reservations"],
        queryFn: getAllReservations,
    });

    return (
        <div>
            <h1 className="font-display text-3xl font-bold text-ink">
                Reservations
            </h1>
            <p className="mt-2 text-gray-600">
                Every reservation and its payments. View only: buyers reserve,
                pay and cancel themselves.
            </p>

            <Card className="mt-8 overflow-x-auto p-0 sm:p-0">
                {query.isPending && (
                    <Spinner className="m-6 size-8 text-brand-blue" />
                )}
                {query.isError && (
                    <p className="p-6 text-brand-red">
                        Couldn't load the reservations.
                    </p>
                )}
                {query.data?.length === 0 && (
                    <p className="p-6 text-gray-600">No reservations yet.</p>
                )}
                {query.data?.length > 0 && (
                    <table className="w-full text-left text-sm">
                        <thead className="bg-gray-50 text-gray-500">
                            <tr>
                                <th className="px-4 py-3 font-semibold">
                                    Buyer
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    Unit
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    Status
                                </th>
                                <th className="px-4 py-3 text-right font-semibold">
                                    Total
                                </th>
                                <th className="px-4 py-3 text-right font-semibold">
                                    Paid
                                </th>
                                <th className="px-4 py-3 text-right font-semibold">
                                    Balance
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    Reserved
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    Receipts
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {query.data.map((reservation) => {
                                const status =
                                    RESERVATION_STATUS[reservation.status];
                                return (
                                    <tr key={reservation.id}>
                                        <td className="px-4 py-3">
                                            <p className="font-semibold text-ink">
                                                {reservation.user.name}
                                            </p>
                                            <p className="text-gray-500">
                                                {reservation.user.email}
                                            </p>
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            {reservation.house.house_type}
                                            <br />
                                            <span className="text-gray-500">
                                                Block {reservation.house.block}{" "}
                                                · Lot {reservation.house.lot}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            <span
                                                className={clsx(
                                                    "rounded-full px-2.5 py-0.5 text-xs font-semibold",
                                                    status.className,
                                                )}
                                            >
                                                {status.label}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-right whitespace-nowrap">
                                            {peso(reservation.total_price)}
                                        </td>
                                        <td className="px-4 py-3 text-right whitespace-nowrap">
                                            {peso(reservation.total_paid)}
                                        </td>
                                        <td className="px-4 py-3 text-right whitespace-nowrap">
                                            {peso(reservation.balance)}
                                        </td>
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            {date(reservation.created_at)}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex flex-wrap gap-2">
                                                {reservation.payments.map(
                                                    (payment, i) => (
                                                        <a
                                                            key={payment.id}
                                                            href={receiptUrl(
                                                                payment.id,
                                                            )}
                                                            title={`${payment.receipt_number}: ${peso(payment.amount)}`}
                                                            className="font-semibold text-brand-blue hover:underline"
                                                        >
                                                            #{i + 1}
                                                        </a>
                                                    ),
                                                )}
                                            </div>
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
