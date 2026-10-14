import toast from "react-hot-toast";

/**
 * Puts a failed API call's errors on a react-hook-form form.
 * - Laravel validation (422, also the 429 login lockout): each errors.{field}
 *   message appears under that field's input.
 * - Anything else: a toast plus errors.root.server, shown by <FormError>.
 */
export function applyServerErrors(error, form) {
    const response = error.response;
    const fieldErrors = response?.data?.errors;
    const fields = Object.keys(form.getValues());

    if (fieldErrors) {
        const others = [];
        for (const [field, messages] of Object.entries(fieldErrors)) {
            if (fields.includes(field)) {
                form.setError(field, { type: "server", message: messages[0] });
            } else {
                others.push(messages[0]);
            }
        }
        if (others.length === 0) return;
        return showFormError(form, others.join(" "));
    }

    showFormError(form, messageFor(response));
}

function showFormError(form, message) {
    form.setError("root.server", { type: "server", message });
    toast.error(message);
}

function messageFor(response) {
    if (!response)
        return "Can't reach the server. Check your connection and try again.";
    if (response.status === 419)
        return "Your session expired. Refresh the page and try again.";
    return response.data?.message || "Something went wrong. Please try again.";
}
