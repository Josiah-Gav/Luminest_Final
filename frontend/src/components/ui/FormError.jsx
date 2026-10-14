import { ExclamationTriangleIcon } from "@heroicons/react/24/outline";

/**
 * The form-level error that doesn't belong to one field (server down,
 * session expired, …). Show it with <FormError message={errors.root?.server?.message} />;
 * applyServerErrors() in utils/serverErrors.js fills it.
 */
export default function FormError({ message }) {
    if (!message) return null;

    return (
        <div
            role="alert"
            className="flex gap-3 rounded-lg bg-red-50 p-3 text-sm text-red-700 ring-1 ring-red-200 ring-inset"
        >
            <ExclamationTriangleIcon
                className="size-5 shrink-0"
                aria-hidden="true"
            />
            <p>{message}</p>
        </div>
    );
}
