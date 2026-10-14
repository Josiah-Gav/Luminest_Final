import { ROLE_LABELS } from "../../auth/roles";
import { useAuth } from "../../auth/useAuth";
import Card from "../../components/ui/Card";

// Used by all five dashboard routes until each role's phase builds its real dashboard.
export default function DashboardPlaceholder() {
    const { user } = useAuth();

    return (
        <Card className="max-w-2xl">
            <span className="inline-flex rounded-full bg-brand-blue/10 px-3 py-1 text-xs font-semibold text-brand-blue">
                {ROLE_LABELS[user.role]}
            </span>
            <h1 className="mt-4 font-display text-3xl font-bold text-ink">
                Welcome, {user.name}!
            </h1>
            <p className="mt-3 text-gray-600">
                This is your {ROLE_LABELS[user.role].toLowerCase()} dashboard.
                Its tools are on the way; the items marked “Soon” in the menu
                show what's coming.
            </p>
        </Card>
    );
}
