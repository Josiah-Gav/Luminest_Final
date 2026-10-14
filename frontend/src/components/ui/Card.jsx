import clsx from "clsx";

export default function Card({ className, children, ...props }) {
    return (
        <div
            className={clsx(
                "rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8",
                className,
            )}
            {...props}
        >
            {children}
        </div>
    );
}
