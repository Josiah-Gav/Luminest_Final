import { keepPreviousData, useQuery } from "@tanstack/react-query";
import clsx from "clsx";
import { useState } from "react";
import { getHouseTypes } from "../../api/houseTypes";
import { getHouses } from "../../api/manager";
import Button from "../../components/ui/Button";
import Card from "../../components/ui/Card";
import Spinner from "../../components/ui/Spinner";
import { date } from "../../utils/format";

const STATUS = {
    available: "bg-green-100 text-green-800",
    reserved: "bg-amber-100 text-amber-800",
    sold: "bg-gray-100 text-gray-700",
};

const filterClass =
    "rounded-lg border border-gray-300 bg-white px-3 py-2 focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 focus:outline-none";

// Property manager, route /manager/houses. Read-only: a unit's status follows its reservations (R7).
export default function Houses() {
    const [search, setSearch] = useState("");
    const [status, setStatus] = useState("");
    const [houseType, setHouseType] = useState("");
    const [page, setPage] = useState(1);

    // GET /api/manager/houses?search=&status=&house_type=&page= → PropertyManager\HouseController@index → ManagerHouseResource
    const query = useQuery({
        queryKey: ["manager-houses", { search, status, houseType, page }],
        queryFn: () =>
            getHouses({ search, status, house_type: houseType, page }),
        placeholderData: keepPreviousData,
    });
    // The 4 models for the filter (the public catalog endpoint).
    const types = useQuery({
        queryKey: ["house-types"],
        queryFn: getHouseTypes,
    });

    // Changing a filter goes back to page 1.
    const filter = (setter) => (e) => {
        setter(e.target.value);
        setPage(1);
    };
    const meta = query.data?.meta;

    return (
        <div>
            <h1 className="font-display text-3xl font-bold text-ink">Houses</h1>

            <div className="mt-6 flex flex-wrap gap-3">
                <input
                    type="search"
                    aria-label="Search houses"
                    placeholder="Search block, lot, model or owner"
                    value={search}
                    onChange={filter(setSearch)}
                    className={clsx(filterClass, "w-full max-w-xs")}
                />
                <select
                    aria-label="Filter by status"
                    value={status}
                    onChange={filter(setStatus)}
                    className={filterClass}
                >
                    <option value="">All statuses</option>
                    <option value="available">Available</option>
                    <option value="reserved">Reserved</option>
                    <option value="sold">Sold</option>
                </select>
                <select
                    aria-label="Filter by model"
                    value={houseType}
                    onChange={filter(setHouseType)}
                    className={filterClass}
                >
                    <option value="">All models</option>
                    {types.data?.map((type) => (
                        <option key={type.slug} value={type.slug}>
                            {type.name}
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
                        Couldn't load the houses.
                    </p>
                )}
                {query.data?.data.length === 0 && (
                    <p className="p-6 text-gray-600">No houses match.</p>
                )}
                {query.data?.data.length > 0 && (
                    <table className="w-full text-left text-sm">
                        <thead className="bg-gray-50 text-gray-500">
                            <tr>
                                <th className="px-4 py-3 font-semibold">
                                    Block / lot
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    Model
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    Status
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    Owner
                                </th>
                                <th className="px-4 py-3 font-semibold">
                                    Purchased
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {query.data.data.map((house) => (
                                <tr key={house.id}>
                                    <td className="px-4 py-3 whitespace-nowrap">
                                        Block {house.block} · Lot {house.lot}
                                    </td>
                                    <td className="px-4 py-3">
                                        {house.house_type}
                                    </td>
                                    <td className="px-4 py-3">
                                        <span
                                            className={clsx(
                                                "rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize",
                                                STATUS[house.status],
                                            )}
                                        >
                                            {house.status}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3">
                                        {house.owner?.name ?? "—"}
                                    </td>
                                    <td className="px-4 py-3 whitespace-nowrap">
                                        {house.purchased_at
                                            ? date(house.purchased_at)
                                            : "—"}
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
