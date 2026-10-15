import { useForm } from "react-hook-form";
import toast from "react-hot-toast";
import { Link, useSearchParams } from "react-router-dom";
import { useAuth } from "../../auth/useAuth";
import AuthLayout from "../../components/layout/AuthLayout";
import Button from "../../components/ui/Button";
import FormError from "../../components/ui/FormError";
import GoogleButton from "../../components/ui/GoogleButton";
import Input from "../../components/ui/Input";
import { applyServerErrors } from "../../utils/serverErrors";

// ?error=<code> comes from the Google callback (GoogleAuthController).
const GOOGLE_ERRORS = {
    inactive:
        "This account has been deactivated. Please contact the administrator.",
    cancelled: "Google sign-in was cancelled.",
    failed: "Google sign-in didn't work. Please try again.",
};

export default function Login() {
    const [searchParams] = useSearchParams();
    const googleError = GOOGLE_ERRORS[searchParams.get("error")];
    const { login } = useAuth();
    const form = useForm({ defaultValues: { email: "", password: "" } });
    const {
        register,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = form;

    // On success GuestRoute sees the user and redirects to their dashboard.
    async function onSubmit(values) {
        try {
            const user = await login(values);
            toast.success(`Welcome back, ${user.name}!`);
        } catch (error) {
            applyServerErrors(error, form);
        }
    }

    return (
        <AuthLayout
            title="Welcome back"
            subtitle="Log in to manage your home, payments and requests."
            footer={
                <>
                    New to Luminest?{" "}
                    <Link
                        to="/register"
                        className="font-semibold text-brand-blue hover:underline"
                    >
                        Create an account
                    </Link>
                </>
            }
        >
            <form
                onSubmit={handleSubmit(onSubmit)}
                noValidate
                className="space-y-5"
            >
                <FormError
                    message={errors.root?.server?.message ?? googleError}
                />
                <Input
                    label="Email"
                    type="email"
                    autoComplete="email"
                    autoFocus
                    error={errors.email?.message}
                    {...register("email", { required: "Enter your email." })}
                />
                <Input
                    label="Password"
                    type="password"
                    autoComplete="current-password"
                    error={errors.password?.message}
                    {...register("password", {
                        required: "Enter your password.",
                    })}
                />
                <Button type="submit" loading={isSubmitting} className="w-full">
                    {isSubmitting ? "Logging in…" : "Log in"}
                </Button>
            </form>
            <GoogleButton />
        </AuthLayout>
    );
}
