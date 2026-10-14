import clsx from "clsx";
import Spinner from "./Spinner";

const variants = {
    primary:
        "bg-brand-blue text-white shadow-sm hover:bg-brand-blue/90 focus-visible:outline-brand-blue",
    accent: "bg-brand-red text-white shadow-sm hover:bg-brand-red/90 focus-visible:outline-brand-red",
    secondary:
        "bg-white text-ink shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50 focus-visible:outline-brand-blue",
    ghost: "text-gray-600 hover:bg-gray-100 hover:text-ink focus-visible:outline-brand-blue",
};

/**
 * A button, or any element passed as `as` (e.g. as={Link} to="/login").
 * `loading` shows a spinner and blocks double submits.
 */
export default function Button({
    as: Component = "button",
    variant = "primary",
    loading = false,
    disabled = false,
    className,
    children,
    ...props
}) {
    const isButton = Component === "button";

    return (
        <Component
            type={isButton ? "button" : undefined}
            disabled={isButton ? disabled || loading : undefined}
            aria-busy={loading || undefined}
            className={clsx(
                "inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-60",
                variants[variant],
                className,
            )}
            {...props}
        >
            {loading && <Spinner className="size-4" />}
            {children}
        </Component>
    );
}
