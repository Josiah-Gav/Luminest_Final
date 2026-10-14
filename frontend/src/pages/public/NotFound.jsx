import { Link } from "react-router-dom";
import { homeFor } from "../../auth/roles";
import { useAuth } from "../../auth/useAuth";
import Logo from "../../components/layout/Logo";
import Button from "../../components/ui/Button";

export default function NotFound() {
    const { user } = useAuth();

    return (
        <main className="flex min-h-screen flex-col items-center justify-center px-4 text-center">
            <Logo className="mb-12" />
            <p className="font-display text-7xl font-bold text-brand-red">
                404
            </p>
            <h1 className="mt-4 font-display text-3xl font-bold text-ink">
                Page not found
            </h1>
            <p className="mt-3 text-gray-600">
                The page you're looking for doesn't exist or has moved.
            </p>
            <Button as={Link} to={user ? homeFor(user) : "/"} className="mt-8">
                {user ? "Back to my dashboard" : "Back to the home page"}
            </Button>
        </main>
    );
}
