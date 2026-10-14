import { useQuery } from "@tanstack/react-query";
import clsx from "clsx";
import { Link, useParams } from "react-router-dom";
import { getUser } from "../../api/admin";
import { ROLE_LABELS } from "../../auth/roles";
import Button from "../../components/ui/Button";
import Card from "../../components/ui/Card";
import FullPageSpinner from "../../components/ui/FullPageSpinner";
import { date } from "../../utils/format";
import { CATEGORY } from "../../utils/status";

// Admin, route /admin/users/:id (view).
export default function UserDetail() {
    const { id } = useParams();

    // GET /api/admin/users/{id} → Admin\UserController@show → UserResource
    const query = useQuery({
        queryKey: ["admin-user", id],
        queryFn: () => getUser(id),
    });

    if (query.isPending) return <FullPageSpinner />;
    if (query.isError)
        return <p className="text-brand-red">Couldn't load this user.</p>;

    const user = query.data;
    const rows = [
        ["Email", user.email],
        ["Mobile number", user.phone_number ?? "—"],
        ["Role", ROLE_LABELS[user.role]],
        ...(user.expertise ? [["Expertise", CATEGORY[user.expertise]]] : []),
        ["Joined", date(user.created_at)],
    ];

    return (
        <div className="max-w-2xl">
            <Link
                to="/admin/users"
                className="text-sm font-semibold text-brand-blue hover:underline"
            >
                ← All users
            </Link>
            <Card className="mt-4">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <h1 className="font-display text-3xl font-bold text-ink">
                        {user.name}
                    </h1>
                    <span
                        className={clsx(
                            "rounded-full px-3 py-1 text-sm font-semibold",
                            user.is_active
                                ? "bg-green-100 text-green-800"
                                : "bg-gray-100 text-gray-700",
                        )}
                    >
                        {user.is_active ? "Active" : "Inactive"}
                    </span>
                </div>
                <dl className="mt-6 divide-y divide-gray-100">
                    {rows.map(([label, value]) => (
                        <div
                            key={label}
                            className="flex justify-between gap-4 py-3"
                        >
                            <dt className="text-gray-500">{label}</dt>
                            <dd className="font-semibold text-ink">{value}</dd>
                        </div>
                    ))}
                </dl>
                <Button
                    as={Link}
                    to={`/admin/users/${user.id}/edit`}
                    className="mt-6"
                >
                    Edit user
                </Button>
            </Card>
        </div>
    );
}
