import { useQuery } from "@tanstack/react-query";
import { Link } from "react-router-dom";
import { getHouseTypes, houseImageUrl } from "../../api/houseTypes";
import { homeFor } from "../../auth/roles";
import { useAuth } from "../../auth/useAuth";
import Logo from "../../components/layout/Logo";
import Button from "../../components/ui/Button";
import Spinner from "../../components/ui/Spinner";
import { peso } from "../../utils/format";

// Public landing page (M2).
export default function Home() {
    const { user } = useAuth();
    // GET /api/house-types → HouseTypeController@index → HouseTypeResource
    const houseTypes = useQuery({
        queryKey: ["house-types"],
        queryFn: getHouseTypes,
    });

    return (
        <div className="min-h-screen bg-white">
            <header className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-5 sm:px-6">
                <Logo />
                <nav className="flex items-center gap-2">
                    {user ? (
                        <Button as={Link} to={homeFor(user)}>
                            My dashboard
                        </Button>
                    ) : (
                        <>
                            <Button as={Link} to="/login" variant="ghost">
                                Log in
                            </Button>
                            <Button as={Link} to="/register">
                                Register
                            </Button>
                        </>
                    )}
                </nav>
            </header>

            <main className="mx-auto max-w-6xl px-4 pt-10 pb-20 sm:px-6 lg:pt-16">
                <div className="max-w-3xl">
                    <p className="text-sm font-semibold tracking-wide text-brand-red uppercase">
                        Luminest Subdivision
                    </p>
                    <h1 className="mt-3 font-display text-4xl leading-tight font-bold text-ink sm:text-5xl">
                        Find the home where your story continues.
                    </h1>
                    <p className="mt-5 text-lg text-gray-600">
                        Pick a house model, reserve a unit with a ₱5,000 fee and
                        pay the rest in instalments.
                    </p>
                </div>

                <h2 className="mt-14 font-display text-2xl font-bold text-ink">
                    Our house models
                </h2>

                {houseTypes.isPending && (
                    <Spinner className="mt-8 size-8 text-brand-blue" />
                )}
                {houseTypes.isError && (
                    <p className="mt-6 text-brand-red">
                        Couldn't load the house models. Please refresh the page.
                    </p>
                )}

                <ul className="mt-6 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    {houseTypes.data?.map((type) => (
                        <li
                            key={type.id}
                            className="flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-200"
                        >
                            <img
                                src={houseImageUrl(type)}
                                alt={type.name}
                                className="aspect-[3/2] w-full object-cover"
                            />
                            <div className="flex flex-1 flex-col p-5">
                                <p className="text-xs font-semibold text-brand-red uppercase">
                                    {type.badge}
                                </p>
                                <h3 className="mt-1 font-display text-xl font-bold text-ink">
                                    {type.name}
                                </h3>
                                <p className="text-sm text-gray-500">
                                    {type.bedrooms} bed · {type.bathrooms} bath
                                    · {type.available_units_count} units left
                                </p>
                                <p className="mt-3 text-lg font-semibold text-ink">
                                    {peso(type.price)}
                                </p>
                                <Button
                                    as={Link}
                                    to={`/houses/${type.slug}`}
                                    variant="secondary"
                                    className="mt-4"
                                >
                                    View
                                </Button>
                            </div>
                        </li>
                    ))}
                </ul>
            </main>
        </div>
    );
}
