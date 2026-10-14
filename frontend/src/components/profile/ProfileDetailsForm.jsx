import { useForm } from "react-hook-form";
import toast from "react-hot-toast";
import { useAuth } from "../../auth/useAuth";
import Button from "../ui/Button";
import Card from "../ui/Card";
import FormError from "../ui/FormError";
import Input from "../ui/Input";
import { applyServerErrors } from "../../utils/serverErrors";

export default function ProfileDetailsForm() {
    const { user, updateProfile } = useAuth();
    const form = useForm({
        defaultValues: {
            name: user.name,
            email: user.email,
            phone_number: user.phone_number ?? "",
        },
    });
    const {
        register,
        handleSubmit,
        reset,
        formState: { errors, isSubmitting, isDirty },
    } = form;

    async function onSubmit(values) {
        try {
            const saved = await updateProfile(values);
            // Show what the server stored (e.g. the normalized phone number).
            reset({
                name: saved.name,
                email: saved.email,
                phone_number: saved.phone_number ?? "",
            });
            toast.success("Profile saved.");
        } catch (error) {
            applyServerErrors(error, form);
        }
    }

    return (
        <Card>
            <h2 className="font-display text-xl font-bold text-ink">
                Personal details
            </h2>
            <form
                onSubmit={handleSubmit(onSubmit)}
                noValidate
                className="mt-6 space-y-5"
            >
                <FormError message={errors.root?.server?.message} />
                <Input
                    label="Full name"
                    autoComplete="name"
                    error={errors.name?.message}
                    {...register("name", { required: "Enter your name." })}
                />
                <Input
                    label="Email"
                    type="email"
                    autoComplete="email"
                    error={errors.email?.message}
                    {...register("email", { required: "Enter your email." })}
                />
                <Input
                    label="Mobile number"
                    type="tel"
                    autoComplete="tel"
                    placeholder="0917 123 4567"
                    error={errors.phone_number?.message}
                    {...register("phone_number", {
                        required: "Enter your mobile number.",
                    })}
                />
                <Button
                    type="submit"
                    loading={isSubmitting}
                    disabled={!isDirty}
                >
                    {isSubmitting ? "Saving…" : "Save changes"}
                </Button>
            </form>
        </Card>
    );
}
