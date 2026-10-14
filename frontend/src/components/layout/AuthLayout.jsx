import {
    BanknotesIcon,
    ShieldCheckIcon,
    WrenchScrewdriverIcon,
} from "@heroicons/react/24/outline";
import Logo from "./Logo";

const highlights = [
    {
        icon: BanknotesIcon,
        text: "Reserve a unit with a ₱5,000 fee and pay the rest in instalments.",
    },
    {
        icon: WrenchScrewdriverIcon,
        text: "Request repairs and follow them until they're fixed.",
    },
    {
        icon: ShieldCheckIcon,
        text: "Every payment comes with a receipt you can download.",
    },
];

/**
 * Login and Register: a brand panel on large screens, the form on the right.
 * On phones only the form shows, under the logo.
 */
export default function AuthLayout({ title, subtitle, children, footer }) {
    return (
        <div className="flex min-h-screen bg-white">
            <aside className="relative hidden w-[45%] flex-col justify-between overflow-hidden bg-ink p-12 text-white lg:flex">
                <div
                    className="absolute -top-24 -left-24 size-96 rounded-full bg-brand-blue/40 blur-3xl"
                    aria-hidden="true"
                />
                <div
                    className="absolute -right-24 -bottom-32 size-96 rounded-full bg-brand-red/40 blur-3xl"
                    aria-hidden="true"
                />

                <Logo light className="relative" />

                <div className="relative max-w-md">
                    <h2 className="font-display text-4xl leading-tight font-bold">
                        A home in Luminest starts here.
                    </h2>
                    <ul className="mt-8 space-y-4">
                        {highlights.map(({ icon: Icon, text }) => (
                            <li key={text} className="flex gap-3 text-white/80">
                                <Icon
                                    className="size-6 shrink-0 text-white"
                                    aria-hidden="true"
                                />
                                <span>{text}</span>
                            </li>
                        ))}
                    </ul>
                </div>

                <p className="relative text-sm text-white/50">
                    Luminest Subdivision · 15 blocks · 850 homes
                </p>
            </aside>

            <main className="flex flex-1 items-center justify-center px-4 py-12 sm:px-8">
                <div className="w-full max-w-md">
                    <Logo className="mb-10 lg:hidden" />
                    <h1 className="font-display text-3xl font-bold text-ink">
                        {title}
                    </h1>
                    <p className="mt-2 text-gray-600">{subtitle}</p>
                    <div className="mt-8">{children}</div>
                    {footer && (
                        <p className="mt-8 text-center text-sm text-gray-600">
                            {footer}
                        </p>
                    )}
                </div>
            </main>
        </div>
    );
}
