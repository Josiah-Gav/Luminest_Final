import { useForm } from "react-hook-form";
import toast from "react-hot-toast";
import { Link } from "react-router-dom";
import { useAuth } from "../../auth/useAuth";
import AuthLayout from "../../components/layout/AuthLayout";
import Button from "../../components/ui/Button";
import FormError from "../../components/ui/FormError";
import Input from "../../components/ui/Input";
import { applyServerErrors } from "../../utils/serverErrors";

export default function Register() {
    const { register: createAccount } = useAuth();
    const form = useForm({
        defaultValues: {
            name: "",
            email: "",
            phone_number: "",
            password: "",
            password_confirmation: "",
        },
    });
    const {
        register,
        handleSubmit,
        getValues,
        formState: { errors, isSubmitting },
    } = form;

    // The API logs the new prospect in; GuestRoute then redirects to /prospect.
    async function onSubmit(values) {
        try {
            await createAccount(values);
            toast.success("Your account is ready. Welcome to Luminest!");
        } catch (error) {
            applyServerErrors(error, form);
        }
    }

    return (
        <AuthLayout
            title="Create your account"
            subtitle="Browse our house models and reserve the one you love."
            footer={
                <>
                    Already have an account?{" "}
                    <Link
                        to="/login"
                        className="font-semibold text-brand-blue hover:underline"
                    >
                        Log in
                    </Link>
                </>
            }
        >
            <form
                onSubmit={handleSubmit(onSubmit)}
                noValidate
                className="space-y-5"
            >
                <FormError message={errors.root?.server?.message} />
                <Input
                    label="Full name"
                    autoComplete="name"
                    autoFocus
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
                <div className="grid gap-5 sm:grid-cols-2">
                    <Input
                        label="Password"
                        type="password"
                        autoComplete="new-password"
                        hint="At least 8 characters."
                        error={errors.password?.message}
                        {...register("password", {
                            required: "Choose a password.",
                        })}
                    />
                    <Input
                        label="Confirm password"
                        type="password"
                        autoComplete="new-password"
                        error={errors.password_confirmation?.message}
                        {...register("password_confirmation", {
                            validate: (value) =>
                                value === getValues("password") ||
                                "The passwords don't match.",
                        })}
                    />
                </div>
                <Button type="submit" loading={isSubmitting} className="w-full">
                    {isSubmitting ? "Creating account…" : "Create account"}
                </Button>
            </form>
        </AuthLayout>
    );
}
