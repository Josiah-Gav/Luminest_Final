import { useQuery, useQueryClient } from "@tanstack/react-query";
import { useForm } from "react-hook-form";
import toast from "react-hot-toast";
import { Link, useLocation, useNavigate, useParams } from "react-router-dom";
import { getHouseType, houseImageUrl } from "../../api/houseTypes";
import { reserve } from "../../api/reservations";
import { useAuth } from "../../auth/useAuth";
import Logo from "../../components/layout/Logo";
import Button from "../../components/ui/Button";
import Card from "../../components/ui/Card";
import FormError from "../../components/ui/FormError";
import FullPageSpinner from "../../components/ui/FullPageSpinner";
import Input from "../../components/ui/Input";
import { peso } from "../../utils/format";
import { applyServerErrors } from "../../utils/serverErrors";
import NotFound from "./NotFound";

// House model details + reserve form (M2 + M3), route /houses/:slug.
export default function HouseTypeDetail() {
    const { slug } = useParams();
    const { user, refreshUser } = useAuth();
    const navigate = useNavigate();
    const location = useLocation();
    const queryClient = useQueryClient();

    // GET /api/house-types/{slug} → HouseTypeController@show → HouseTypeResource (with available_units)
    const query = useQuery({
        queryKey: ["house-types", slug],
        queryFn: () => getHouseType(slug),
        retry: false,
    });

    const form = useForm({ defaultValues: { house_id: "", fee: 5000 } });
    const {
        register,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = form;

    if (query.isPending) return <FullPageSpinner />;
    if (query.isError) return <NotFound />;

    const type = query.data;

    // POST /api/reservations → ReservationController@store → ReservationService::reserve()
    async function onSubmit(values) {
        const unit = type.available_units.find(
            (u) => u.id === Number(values.house_id),
        );
        const ok = window.confirm(
            `Reserve Block ${unit.block} Lot ${unit.lot} and pay ${peso(values.fee)} now? The fee is non-refundable.`,
        );
        if (!ok) return;

        try {
            const reservation = await reserve({
                house_id: Number(values.house_id),
                fee: Number(values.fee),
            });
            queryClient.invalidateQueries({ queryKey: ["house-types"] });

            if (reservation.status === "paid") {
                // The fee was the full price: the house is sold and the user is now a tenant.
                await refreshUser();
                toast.success("Fully paid. Welcome home!");
                navigate("/tenant");
            } else {
                toast.success("Unit reserved. Your receipt is ready.");
                navigate("/reservations");
            }
        } catch (error) {
            applyServerErrors(error, form);
        }
    }

    return (
        <div className="min-h-screen bg-gray-50">
            <header className="mx-auto flex max-w-6xl items-center justify-between px-4 py-5 sm:px-6">
                <Logo />
                <Link
                    to="/"
                    className="text-sm font-semibold text-brand-blue hover:underline"
                >
                    ← All house models
                </Link>
            </header>

            <main className="mx-auto grid max-w-6xl gap-8 px-4 pb-20 sm:px-6 lg:grid-cols-5">
                <section className="lg:col-span-3">
                    <img
                        src={houseImageUrl(type)}
                        alt={type.name}
                        className="aspect-[3/2] w-full rounded-2xl object-cover shadow-sm"
                    />
                    <p className="mt-6 text-sm font-semibold text-brand-red uppercase">
                        {type.category}
                    </p>
                    <h1 className="mt-1 font-display text-4xl font-bold text-ink">
                        {type.name}
                    </h1>
                    <p className="mt-2 text-2xl font-semibold text-ink">
                        {peso(type.price)}
                    </p>
                    <p className="mt-2 text-gray-500">
                        {type.bedrooms} bedroom(s) · {type.bathrooms}{" "}
                        bathroom(s) · {type.carports} carport(s)
                    </p>
                    <p className="mt-6 leading-relaxed text-gray-700">
                        {type.description}
                    </p>
                </section>

                <Card className="h-fit lg:col-span-2">
                    <h2 className="font-display text-2xl font-bold text-ink">
                        Reserve a unit
                    </h2>
                    <p className="mt-1 text-sm text-gray-600">
                        {type.available_units.length} unit(s) available.
                    </p>

                    {!user && (
                        <Button
                            as={Link}
                            to="/login"
                            state={{ from: location }}
                            className="mt-6 w-full"
                        >
                            Log in to reserve
                        </Button>
                    )}

                    {user && user.role !== "prospect" && (
                        <p className="mt-6 rounded-lg bg-gray-100 p-3 text-sm text-gray-700">
                            Only prospect accounts can reserve a unit.
                        </p>
                    )}

                    {user?.role === "prospect" && (
                        <form
                            onSubmit={handleSubmit(onSubmit)}
                            noValidate
                            className="mt-6 space-y-5"
                        >
                            <FormError message={errors.root?.server?.message} />

                            <div>
                                <label
                                    htmlFor="house_id"
                                    className="block text-sm font-semibold text-ink"
                                >
                                    Unit
                                </label>
                                <select
                                    id="house_id"
                                    aria-invalid={
                                        errors.house_id ? true : undefined
                                    }
                                    className="mt-1.5 block w-full rounded-lg border border-gray-300 bg-white px-3.5 py-2.5 text-ink focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 focus:outline-none"
                                    {...register("house_id", {
                                        required: "Choose a unit.",
                                    })}
                                >
                                    <option value="">
                                        Choose a block and lot…
                                    </option>
                                    {type.available_units.map((unit) => (
                                        <option key={unit.id} value={unit.id}>
                                            Block {unit.block} · Lot {unit.lot}
                                        </option>
                                    ))}
                                </select>
                                {errors.house_id && (
                                    <p className="mt-1.5 text-sm text-red-600">
                                        {errors.house_id.message}
                                    </p>
                                )}
                            </div>

                            <Input
                                label="Reservation fee (₱)"
                                type="number"
                                min="5000"
                                step="1"
                                hint={`At least ₱5,000, up to the full price of ${peso(type.price)}.`}
                                error={errors.fee?.message}
                                {...register("fee", {
                                    required: "Enter the fee.",
                                })}
                            />

                            <Button
                                type="submit"
                                variant="accent"
                                loading={isSubmitting}
                                className="w-full"
                            >
                                {isSubmitting
                                    ? "Reserving…"
                                    : "Reserve and pay fee"}
                            </Button>
                            <p className="text-xs text-gray-500">
                                Simulated payment. The fee is non-refundable if
                                you cancel.
                            </p>
                        </form>
                    )}
                </Card>
            </main>
        </div>
    );
}
