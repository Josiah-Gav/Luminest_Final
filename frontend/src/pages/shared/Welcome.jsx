import { Link } from "react-router-dom";
import { ROLE_LABELS } from "../../auth/roles";
import { useAuth } from "../../auth/useAuth";
import Card from "../../components/ui/Card";

// Where the buyer-side roles can go next.
const LINKS = {
    prospect: [
        ["Browse house models", "/", "Pick a unit and reserve it with ₱5,000."],
        [
            "My reservations",
            "/reservations",
            "Pay instalments, download receipts or cancel.",
        ],
    ],
    tenant: [
        [
            "Maintenance requests",
            "/tenant/maintenance",
            "Report a problem or follow a repair.",
        ],
        ["My purchase", "/reservations", "Your payments and receipts."],
    ],
};

// Dashboard for prospects (/prospect) and tenants (/tenant): a welcome and the next steps.
export default function Welcome() {
    const { user } = useAuth();

    return (
        <div className="max-w-3xl">
            <span className="inline-flex rounded-full bg-brand-blue/10 px-3 py-1 text-xs font-semibold text-brand-blue">
                {ROLE_LABELS[user.role]}
            </span>
            <h1 className="mt-4 font-display text-3xl font-bold text-ink">
                Welcome, {user.name}!
            </h1>

            <div className="mt-8 grid gap-4 sm:grid-cols-2">
                {LINKS[user.role].map(([title, to, text]) => (
                    <Link key={to + title} to={to} className="group">
                        <Card className="h-full p-5 transition group-hover:ring-brand-blue sm:p-5">
                            <h2 className="font-display text-xl font-bold text-ink">
                                {title}
                            </h2>
                            <p className="mt-1 text-gray-600">{text}</p>
                        </Card>
                    </Link>
                ))}
            </div>
        </div>
    );
}
