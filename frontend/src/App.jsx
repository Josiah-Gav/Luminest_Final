import { useQuery } from "@tanstack/react-query";
import clsx from "clsx";
import { getUser } from "./api/auth";

// Phase 0 connection check. Replaced by the real routes in Phase 2.
function statusOf(query) {
    if (query.isPending)
        return { code: "…", ok: true, text: "Calling the API…" };
    if (query.isSuccess)
        return {
            code: query.data.status,
            ok: true,
            text: "Connected and logged in.",
        };

    const code = query.error.response?.status;
    if (code === 401) {
        return {
            code,
            ok: true,
            text: "Connected. 401 is correct here: nobody is logged in yet.",
        };
    }
    if (code)
        return {
            code,
            ok: false,
            text: "The API answered, but with an unexpected status.",
        };

    return {
        code: "—",
        ok: false,
        text: "No response. Is `php artisan serve` running? A CORS error also looks like this; check the console.",
    };
}

export default function App() {
    const query = useQuery({
        queryKey: ["user"],
        queryFn: getUser,
        retry: false,
    });
    const status = statusOf(query);

    return (
        <main className="flex min-h-screen items-center justify-center bg-gray-50 p-4 text-ink">
            <section className="w-full max-w-md rounded-xl border-t-4 border-brand-red bg-white p-8 shadow-md">
                <h1 className="font-display text-3xl font-bold">
                    Luminest API check
                </h1>
                <p className="mt-1 text-sm text-gray-500">
                    GET {import.meta.env.VITE_API_URL}/api/user
                </p>

                <p
                    className={clsx(
                        "mt-6 font-display text-6xl font-bold",
                        status.ok ? "text-green-600" : "text-brand-red",
                    )}
                >
                    {status.code}
                </p>
                <p className="mt-2">{status.text}</p>

                <button
                    type="button"
                    onClick={() => query.refetch()}
                    className="mt-6 rounded-lg bg-brand-blue px-4 py-2 font-semibold text-white hover:bg-brand-blue/90"
                >
                    Check again
                </button>
            </section>
        </main>
    );
}
