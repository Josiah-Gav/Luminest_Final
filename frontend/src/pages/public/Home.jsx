import {
    BanknotesIcon,
    HomeModernIcon,
    WrenchScrewdriverIcon,
} from "@heroicons/react/24/outline";
import { homeFor } from "../../auth/roles";
import { useAuth } from "../../auth/useAuth";
import Logo from "../../components/layout/Logo";
import Button from "../../components/ui/Button";
import { Link } from "react-router-dom";

const features = [
    {
        icon: HomeModernIcon,
        title: "Four house models",
        text: "From the compact Aimee Rowhouse to the spacious Armina Duplex.",
    },
    {
        icon: BanknotesIcon,
        title: "Reserve with ₱5,000",
        text: "Hold your unit with a reservation fee and pay the rest in instalments.",
    },
    {
        icon: WrenchScrewdriverIcon,
        title: "Maintenance made easy",
        text: "Tenants file requests online and follow them until they're resolved.",
    },
];

// Temporary public home page; the real landing page with the house catalog comes in Phase 3.
export default function Home() {
    const { user } = useAuth();

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

            <main className="mx-auto max-w-6xl px-4 pt-12 pb-20 sm:px-6 lg:pt-24">
                <div className="max-w-3xl">
                    <p className="text-sm font-semibold tracking-wide text-brand-red uppercase">
                        Luminest Subdivision
                    </p>
                    <h1 className="mt-3 font-display text-4xl leading-tight font-bold text-ink sm:text-6xl">
                        Find the home where your story continues.
                    </h1>
                    <p className="mt-6 text-lg text-gray-600">
                        Choose a unit, reserve it online, pay at your own pace
                        and get help whenever something needs fixing, all in one
                        place.
                    </p>
                    {!user && (
                        <div className="mt-10 flex flex-wrap gap-3">
                            <Button
                                as={Link}
                                to="/register"
                                variant="accent"
                                className="px-6 py-3 text-base"
                            >
                                Get started
                            </Button>
                            <Button
                                as={Link}
                                to="/login"
                                variant="secondary"
                                className="px-6 py-3 text-base"
                            >
                                I already have an account
                            </Button>
                        </div>
                    )}
                </div>

                <ul className="mt-20 grid gap-6 sm:grid-cols-3">
                    {features.map(({ icon: Icon, title, text }) => (
                        <li
                            key={title}
                            className="rounded-2xl bg-gray-50 p-6 ring-1 ring-gray-200"
                        >
                            <Icon
                                className="size-8 text-brand-blue"
                                aria-hidden="true"
                            />
                            <h2 className="mt-4 font-display text-xl font-bold text-ink">
                                {title}
                            </h2>
                            <p className="mt-2 text-gray-600">{text}</p>
                        </li>
                    ))}
                </ul>
            </main>
        </div>
    );
}
