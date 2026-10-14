import clsx from "clsx";
import { useId } from "react";

/**
 * A labelled input with its error message underneath. Works with
 * react-hook-form: <Input label="Email" {...register("email")} error={errors.email?.message} />
 */
export default function Input({
    label,
    error,
    hint,
    id,
    type = "text",
    className,
    ref,
    ...props
}) {
    const generatedId = useId();
    const inputId = id ?? generatedId;
    const messageId = `${inputId}-message`;
    const message = error ?? hint;

    return (
        <div className={className}>
            <label
                htmlFor={inputId}
                className="block text-sm font-semibold text-ink"
            >
                {label}
            </label>
            <input
                ref={ref}
                id={inputId}
                type={type}
                aria-invalid={error ? true : undefined}
                aria-describedby={message ? messageId : undefined}
                className={clsx(
                    "mt-1.5 block w-full rounded-lg border bg-white px-3.5 py-2.5 text-ink shadow-xs transition placeholder:text-gray-400 focus:ring-2 focus:outline-none",
                    error
                        ? "border-red-500 focus:ring-red-500/30"
                        : "border-gray-300 focus:border-brand-blue focus:ring-brand-blue/20",
                )}
                {...props}
            />
            {message && (
                <p
                    id={messageId}
                    className={clsx(
                        "mt-1.5 text-sm",
                        error ? "text-red-600" : "text-gray-500",
                    )}
                >
                    {message}
                </p>
            )}
        </div>
    );
}
