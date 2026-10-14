import { keepPreviousData, useQuery } from "@tanstack/react-query";
import { useState } from "react";
import { getTenants } from "../../api/manager";
import Card from "../../components/ui/Card";
import Spinner from "../../components/ui/Spinner";

// Property manager, route /manager/tenants.
export default function Tenants() {
    const [search, setSearch] = useState("");

    // GET /api/manager/tenants?search= → PropertyManager\TenantController@index → TenantResource (with houses)
    const query = useQuery({
        queryKey: ["manager-tenants", search],
        queryFn: () => getTenants(search),
        placeholderData: keepPreviousData,
    });

    return (
        <div>
            <h1 className="font-display text-3xl font-bold text-ink">
                Tenants
            </h1>

            <input
                type="search"
                aria-label="Search tenants"
                placeholder="Search name, email or phone"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                className="mt-6 w-full max-w-xs rounded-lg border border-gray-300 bg-white px-3.5 py-2 focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 focus:outline-none"
            />

            <Card className="mt-6 overflow-x-auto p-0 sm:p-0">
                {query.isPending && (
                    <Spinner className="m-6 size-8 text-brand-blue" />
                )}
                {query.isError && (
                    <p className="p-6 text-brand-red">
                        Couldn't load the tenants.
                    </p>
                )}
                {query.data?.length === 0 && (
                    <p className="p-6 text-gray-600">No tenants found.</p>
                )}
                {query.data?.length > 0 && (
                    <table className="w-full text-left text-sm">
                        <thead className="bg-gray-50 text-gray-500">
                            <tr>
                                <th className="px-4 py-3 font-semibold">
                                    Tenant
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    Phone
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    Houses
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {query.data.map((tenant) => (
                                <tr key={tenant.id}>
                                    <td className="px-4 py-3">
                                        <p className="font-semibold text-ink">
                                            {tenant.name}
                                        </p>
                                        <p className="text-gray-500">
                                            {tenant.email}
                                        </p>
                                    </td>
                                    <td className="px-4 py-3 whitespace-nowrap">
                                        {tenant.phone_number ?? "—"}
                                    </td>
                                    <td className="px-4 py-3">
                                        {tenant.houses.length === 0 && "—"}
                                        {tenant.houses.map((house) => (
                                            <p key={house.id}>
                                                {house.house_type}, Block{" "}
                                                {house.block} Lot {house.lot}
                                            </p>
                                        ))}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </Card>
        </div>
    );
}
