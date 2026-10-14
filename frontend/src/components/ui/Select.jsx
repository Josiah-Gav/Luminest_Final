import clsx from "clsx";
import { useId } from "react";

/**
 * A labelled <select> with its error message, the twin of Input.
 * <Select label="Role" {...register("role")} error={errors.role?.message}><option>…</option></Select>
 */
export default function Select({
    label,
    error,
    id,
    className,
    children,
    ref,
    ...props
}) {
    const generatedId = useId();
    const selectId = id ?? generatedId;
    const messageId = `${selectId}-message`;

    return (
        <div className={className}>
            <label
                htmlFor={selectId}
                className="block text-sm font-semibold text-ink"
            >
                {label}
            </label>
            <select
                ref={ref}
                id={selectId}
                aria-invalid={error ? true : undefined}
                aria-describedby={error ? messageId : undefined}
                className={clsx(
                    "mt-1.5 block w-full rounded-lg border bg-white px-3.5 py-2.5 text-ink shadow-xs focus:ring-2 focus:outline-none",
                    error
                        ? "border-red-500 focus:ring-red-500/30"
                        : "border-gray-300 focus:border-brand-blue focus:ring-brand-blue/20",
                )}
                {...props}
            >
                {children}
            </select>
            {error && (
                <p id={messageId} className="mt-1.5 text-sm text-red-600">
                    {error}
                </p>
            )}
        </div>
    );
}
