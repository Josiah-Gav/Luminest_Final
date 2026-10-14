import clsx from "clsx";

// A small coloured label. `style` is one entry of MAINTENANCE_STATUS or PRIORITY in utils/status.js.
export default function Badge({ style }) {
    return (
        <span
            className={clsx(
                "rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap",
                style.className,
            )}
        >
            {style.label}
        </span>
    );
}
