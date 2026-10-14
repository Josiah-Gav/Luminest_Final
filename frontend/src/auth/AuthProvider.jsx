import { useQuery, useQueryClient } from "@tanstack/react-query";
import { useMemo } from "react";
import * as api from "../api/auth";
import Button from "../components/ui/Button";
import FullPageSpinner from "../components/ui/FullPageSpinner";
import { AuthContext, USER_QUERY_KEY } from "./AuthContext";

// The SPA keeps no token: the session cookie (HttpOnly, unreadable by JS) is the login.
// So "who am I?" is simply GET /api/user: 200 = logged in, 401 = logged out.
async function fetchCurrentUser() {
    try {
        return await api.getUser();
    } catch (error) {
        if (error.response?.status === 401) return null;
        throw error;
    }
}

export default function AuthProvider({ children }) {
    const queryClient = useQueryClient();
    const query = useQuery({
        queryKey: USER_QUERY_KEY,
        queryFn: fetchCurrentUser,
        retry: false,
        staleTime: Infinity, // only changes when we log in, log out or save the profile
    });

    const value = useMemo(() => {
        const setUser = (user) =>
            queryClient.setQueryData(USER_QUERY_KEY, user);

        return {
            user: query.data ?? null,
            isLoading: query.isPending,
            login: async (credentials) => setUser(await api.login(credentials)),
            register: async (details) => setUser(await api.register(details)),
            logout: async () => {
                await api.logout();
                // Drop every cached response, so nothing of this user is shown to the next one.
                queryClient.removeQueries();
                setUser(null);
            },
            updateProfile: async (details) =>
                setUser(await api.updateProfile(details)),
        };
    }, [query.data, query.isPending, queryClient]);

    if (query.isError) {
        return (
            <div className="flex min-h-screen flex-col items-center justify-center gap-4 p-4 text-center">
                <p className="font-display text-2xl text-ink">
                    Can&apos;t reach the Luminest server.
                </p>
                <p className="text-gray-600">
                    Check that the API is running, then try again.
                </p>
                <Button onClick={() => query.refetch()}>Try again</Button>
            </div>
        );
    }

    if (query.isPending) return <FullPageSpinner />;

    return (
        <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
    );
}
