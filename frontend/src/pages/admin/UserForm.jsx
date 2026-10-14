import { useQuery, useQueryClient } from "@tanstack/react-query";
import { useForm, useWatch } from "react-hook-form";
import toast from "react-hot-toast";
import { Link, useNavigate, useParams } from "react-router-dom";
import { createUser, getUser, updateUser } from "../../api/admin";
import { ROLE_LABELS } from "../../auth/roles";
import Button from "../../components/ui/Button";
import Card from "../../components/ui/Card";
import FormError from "../../components/ui/FormError";
import FullPageSpinner from "../../components/ui/FullPageSpinner";
import Input from "../../components/ui/Input";
import Select from "../../components/ui/Select";
import { applyServerErrors } from "../../utils/serverErrors";
import { CATEGORY } from "../../utils/status";

const blank = {
    name: "",
    email: "",
    phone_number: "",
    password: "",
    role: "prospect",
    expertise: "",
    is_active: true,
};

// Admin, routes /admin/users/new and /admin/users/:id/edit (one form for both).
export default function UserForm() {
    const { id } = useParams(); // undefined when creating
    const isEdit = Boolean(id);
    const navigate = useNavigate();
    const queryClient = useQueryClient();

    // GET /api/admin/users/{id} → Admin\UserController@show → UserResource (edit only)
    const existing = useQuery({
        queryKey: ["admin-user", id],
        queryFn: () => getUser(id),
        enabled: isEdit,
    });

    const form = useForm({
        defaultValues: blank,
        // Fills the form once the user has loaded.
        values: existing.data && {
            ...blank,
            ...existing.data,
            phone_number: existing.data.phone_number ?? "",
            expertise: existing.data.expertise ?? "",
        },
    });
    const {
        register,
        handleSubmit,
        formState: { errors, isSubmitting },
    } = form;

    const role = useWatch({ control: form.control, name: "role" });

    // POST /api/admin/users or PUT /api/admin/users/{id} → UserController → UserService
    async function onSubmit(values) {
        const payload = {
            name: values.name,
            email: values.email,
            phone_number: values.phone_number || null,
            role: values.role,
            // Expertise only means something for staff; the API clears it for other roles anyway.
            expertise:
                values.role === "maintenance_staff"
                    ? values.expertise || null
                    : null,
            is_active: values.is_active,
        };
        if (values.password) payload.password = values.password;

        try {
            if (isEdit) {
                await updateUser(id, payload);
            } else {
                await createUser(payload);
            }
            queryClient.invalidateQueries({ queryKey: ["admin-users"] });
            queryClient.invalidateQueries({ queryKey: ["admin-user", id] });
            toast.success(isEdit ? "User saved." : "User created.");
            navigate("/admin/users");
        } catch (error) {
            applyServerErrors(error, form);
        }
    }

    if (isEdit && existing.isPending) return <FullPageSpinner />;
    if (isEdit && existing.isError) {
        return <p className="text-brand-red">Couldn't load this user.</p>;
    }

    return (
        <div className="max-w-2xl">
            <Link
                to="/admin/users"
                className="text-sm font-semibold text-brand-blue hover:underline"
            >
                ← All users
            </Link>
            <h1 className="mt-2 font-display text-3xl font-bold text-ink">
                {isEdit ? "Edit user" : "New user"}
            </h1>

            <Card className="mt-6">
                <form
                    onSubmit={handleSubmit(onSubmit)}
                    noValidate
                    className="space-y-5"
                >
                    <FormError message={errors.root?.server?.message} />
                    <Input
                        label="Full name"
                        error={errors.name?.message}
                        {...register("name", { required: "Enter a name." })}
                    />
                    <Input
                        label="Email"
                        type="email"
                        error={errors.email?.message}
                        {...register("email", { required: "Enter an email." })}
                    />
                    <Input
                        label="Mobile number"
                        type="tel"
                        placeholder="0917 123 4567"
                        error={errors.phone_number?.message}
                        {...register("phone_number")}
                    />
                    <Input
                        label="Password"
                        type="password"
                        autoComplete="new-password"
                        hint={
                            isEdit
                                ? "Leave blank to keep the current password."
                                : "At least 8 characters."
                        }
                        error={errors.password?.message}
                        {...register("password", {
                            required: isEdit ? false : "Choose a password.",
                        })}
                    />
                    <Select
                        label="Role"
                        error={errors.role?.message}
                        {...register("role")}
                    >
                        {Object.entries(ROLE_LABELS).map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </Select>
                    {role === "maintenance_staff" && (
                        <Select
                            label="Expertise"
                            error={errors.expertise?.message}
                            {...register("expertise")}
                        >
                            <option value="">Choose an expertise…</option>
                            {Object.entries(CATEGORY).map(([value, label]) => (
                                <option key={value} value={value}>
                                    {label}
                                </option>
                            ))}
                        </Select>
                    )}
                    <label className="flex items-center gap-2 text-sm font-semibold text-ink">
                        <input
                            type="checkbox"
                            className="size-4 rounded border-gray-300"
                            {...register("is_active")}
                        />
                        Active (can log in)
                    </label>
                    {errors.is_active && (
                        <p className="text-sm text-red-600">
                            {errors.is_active.message}
                        </p>
                    )}

                    <div className="flex gap-3">
                        <Button type="submit" loading={isSubmitting}>
                            {isSubmitting ? "Saving…" : "Save"}
                        </Button>
                        <Button as={Link} to="/admin/users" variant="secondary">
                            Cancel
                        </Button>
                    </div>
                </form>
            </Card>
        </div>
    );
}
