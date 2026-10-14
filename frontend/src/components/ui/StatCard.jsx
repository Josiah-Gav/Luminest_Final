import Card from "./Card";

// One number on a dashboard.
export default function StatCard({ label, value, hint }) {
    return (
        <Card className="p-5 sm:p-5">
            <p className="text-sm font-semibold text-gray-500">{label}</p>
            <p className="mt-2 font-display text-3xl font-bold text-ink">
                {value}
            </p>
            {hint && <p className="mt-1 text-sm text-gray-500">{hint}</p>}
        </Card>
    );
}
