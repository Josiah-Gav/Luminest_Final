import { HomeModernIcon } from "@heroicons/react/24/solid";
import clsx from "clsx";
import { Link } from "react-router-dom";

export default function Logo({ to = "/", light = false, className }) {
    return (
        <Link
            to={to}
            className={clsx(
                "inline-flex items-center gap-2.5 rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-blue",
                className,
            )}
        >
            <span className="flex size-9 items-center justify-center rounded-lg bg-brand-red text-white shadow-sm">
                <HomeModernIcon className="size-5" aria-hidden="true" />
            </span>
            <span
                className={clsx(
                    "font-display text-2xl font-bold tracking-tight",
                    light ? "text-white" : "text-ink",
                )}
            >
                Luminest
            </span>
        </Link>
    );
}
