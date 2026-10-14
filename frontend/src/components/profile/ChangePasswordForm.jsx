import { useForm } from "react-hook-form";
import toast from "react-hot-toast";
import { changePassword } from "../../api/auth";
import Button from "../ui/Button";
import Card from "../ui/Card";
import FormError from "../ui/FormError";
import Input from "../ui/Input";
import { applyServerErrors } from "../../utils/serverErrors";

const empty = { current_password: "", password: "", password_confirmation: "" };

export default function ChangePasswordForm() {
    const form = useForm({ defaultValues: empty });
    const {
        register,
        handleSubmit,
        reset,
        getValues,
        formState: { errors, isSubmitting },
    } = form;

    async function onSubmit(values) {
        try {
            await changePassword(values);
            reset(empty);
            toast.success("Password changed.");
        } catch (error) {
            applyServerErrors(error, form);
        }
    }

    return (
        <Card>
            <h2 className="font-display text-xl font-bold text-ink">
                Change password
            </h2>
            <form
                onSubmit={handleSubmit(onSubmit)}
                noValidate
                className="mt-6 space-y-5"
            >
                <FormError message={errors.root?.server?.message} />
                <Input
                    label="Current password"
                    type="password"
                    autoComplete="current-password"
                    error={errors.current_password?.message}
                    {...register("current_password", {
                        required: "Enter your current password.",
                    })}
                />
                <Input
                    label="New password"
                    type="password"
                    autoComplete="new-password"
                    hint="At least 8 characters."
                    error={errors.password?.message}
                    {...register("password", {
                        required: "Choose a new password.",
                    })}
                />
                <Input
                    label="Confirm new password"
                    type="password"
                    autoComplete="new-password"
                    error={errors.password_confirmation?.message}
                    {...register("password_confirmation", {
                        validate: (value) =>
                            value === getValues("password") ||
                            "The passwords don't match.",
                    })}
                />
                <Button type="submit" loading={isSubmitting}>
                    {isSubmitting ? "Changing…" : "Change password"}
                </Button>
            </form>
        </Card>
    );
}
