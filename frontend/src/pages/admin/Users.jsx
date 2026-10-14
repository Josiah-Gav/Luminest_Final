import {
    keepPreviousData,
    useQuery,
    useQueryClient,
} from "@tanstack/react-query";
import clsx from "clsx";
import { useState } from "react";
import toast from "react-hot-toast";
import { Link } from "react-router-dom";
import { deleteUser, getUsers, updateUser } from "../../api/admin";
import { ROLE_LABELS } from "../../auth/roles";
import { useAuth } from "../../auth/useAuth";
import Button from "../../components/ui/Button";
import Card from "../../components/ui/Card";
import Spinner from "../../components/ui/Spinner";

// Admin, route /admin/users: search, role filter, activate/deactivate, delete.
export default function Users() {
    const { user: me } = useAuth();
    const queryClient = useQueryClient();
    const [search, setSearch] = useState("");
    const [role, setRole] = useState("");
    const [page, setPage] = useState(1);

    // GET /api/admin/users?search=&role=&page= → Admin\UserController@index → UserResource (paginated)
    const query = useQuery({
        queryKey: ["admin-users", { search, role, page }],
        queryFn: () => getUsers({ search, role, page }),
        placeholderData: keepPreviousData,
    });

    const refresh = () =>
        queryClient.invalidateQueries({ queryKey: ["admin-users"] });

    // PUT /api/admin/users/{id} {is_active} → UserController@update → UserService::update()
    // (deactivating also logs the user out and releases a staff member's open requests)
    async function handleToggle(user) {
        if (
            user.is_active &&
            !window.confirm(
                `Deactivate ${user.name}? They are logged out immediately.`,
            )
        ) {
            return;
        }
        try {
            await updateUser(user.id, { is_active: !user.is_active });
            refresh();
            toast.success(
                user.is_active ? "User deactivated." : "User activated.",
            );
        } catch (error) {
            toast.error(
                error.response?.data?.message ?? "Something went wrong.",
            );
        }
    }

    // DELETE /api/admin/users/{id} → UserController@destroy → UserService::delete()
    async function handleDelete(user) {
        if (!window.confirm(`Delete ${user.name}? This cannot be undone.`))
            return;
        try {
            await deleteUser(user.id);
            refresh();
            toast.success("User deleted.");
        } catch (error) {
            toast.error(
                error.response?.data?.message ?? "Something went wrong.",
            );
        }
    }

    const meta = query.data?.meta;

    return (
        <div>
            <div className="flex flex-wrap items-center justify-between gap-4">
                <h1 className="font-display text-3xl font-bold text-ink">
                    Users
                </h1>
                <Button as={Link} to="/admin/users/new">
                    New user
                </Button>
            </div>

            <div className="mt-6 flex flex-wrap gap-3">
                <input
                    type="search"
                    aria-label="Search users"
                    placeholder="Search name, email or phone"
                    value={search}
                    onChange={(e) => {
                        setSearch(e.target.value);
                        setPage(1);
                    }}
                    className="w-full max-w-xs rounded-lg border border-gray-300 bg-white px-3.5 py-2 focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 focus:outline-none"
                />
                <select
                    aria-label="Filter by role"
                    value={role}
                    onChange={(e) => {
                        setRole(e.target.value);
                        setPage(1);
                    }}
                    className="rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 focus:outline-none"
                >
                    <option value="">All roles</option>
                    {Object.entries(ROLE_LABELS).map(([value, label]) => (
                        <option key={value} value={value}>
                            {label}
                        </option>
                    ))}
                </select>
            </div>

            <Card className="mt-6 overflow-x-auto p-0 sm:p-0">
                {query.isPending && (
                    <Spinner className="m-6 size-8 text-brand-blue" />
                )}
                {query.isError && (
                    <p className="p-6 text-brand-red">
                        Couldn't load the users.
                    </p>
                )}
                {query.data?.data.length === 0 && (
                    <p className="p-6 text-gray-600">No users found.</p>
                )}
                {query.data?.data.length > 0 && (
                    <table className="w-full text-left text-sm">
                        <thead className="bg-gray-50 text-gray-500">
                            <tr>
                                <th className="px-4 py-3 font-semibold">
                                    User
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    Role
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    Status
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    <span className="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {query.data.data.map((user) => (
                                <tr key={user.id}>
                                    <td className="px-4 py-3">
                                        <p className="font-semibold text-ink">
                                            {user.name}
                                        </p>
                                        <p className="text-gray-500">
                                            {user.email}
                                        </p>
                                    </td>
                                    <td className="px-4 py-3 whitespace-nowrap">
                                        {ROLE_LABELS[user.role]}
                                        {user.expertise && (
                                            <span className="text-gray-500">
                                                {" "}
                                                ({user.expertise})
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        <span
                                            className={clsx(
                                                "rounded-full px-2.5 py-0.5 text-xs font-semibold",
                                                user.is_active
                                                    ? "bg-green-100 text-green-800"
                                                    : "bg-gray-100 text-gray-700",
                                            )}
                                        >
                                            {user.is_active
                                                ? "Active"
                                                : "Inactive"}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex flex-wrap justify-end gap-3 font-semibold">
                                            <Link
                                                to={`/admin/users/${user.id}`}
                                                className="text-brand-blue hover:underline"
                                            >
                                                View
                                            </Link>
                                            <Link
                                                to={`/admin/users/${user.id}/edit`}
                                                className="text-brand-blue hover:underline"
                                            >
                                                Edit
                                            </Link>
                                            {/* The API refuses these for your own account; hiding them is just friendlier. */}
                                            {user.id !== me.id && (
                                                <>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            handleToggle(user)
                                                        }
                                                        className="text-gray-700 hover:underline"
                                                    >
                                                        {user.is_active
                                                            ? "Deactivate"
                                                            : "Activate"}
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() =>
                                                            handleDelete(user)
                                                        }
                                                        className="text-brand-red hover:underline"
                                                    >
                                                        Delete
                                                    </button>
                                                </>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </Card>

            {meta && meta.last_page > 1 && (
                <div className="mt-4 flex items-center justify-between text-sm text-gray-600">
                    <Button
                        variant="secondary"
                        disabled={meta.current_page === 1}
                        onClick={() => setPage(meta.current_page - 1)}
                    >
                        Previous
                    </Button>
                    <span>
                        Page {meta.current_page} of {meta.last_page}
                    </span>
                    <Button
                        variant="secondary"
                        disabled={meta.current_page === meta.last_page}
                        onClick={() => setPage(meta.current_page + 1)}
                    >
                        Next
                    </Button>
                </div>
            )}
        </div>
    );
}
