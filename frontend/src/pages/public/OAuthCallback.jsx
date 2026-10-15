import { useQueryClient } from "@tanstack/react-query";
import { useEffect } from "react";
import { useNavigate } from "react-router-dom";
import { USER_QUERY_KEY } from "../../auth/AuthContext";
import { homeFor } from "../../auth/roles";
import { useAuth } from "../../auth/useAuth";
import FullPageSpinner from "../../components/ui/FullPageSpinner";

// Google login ends here: the API already set the session cookie, so reload "who am I?"
// and go to the user's dashboard.
export default function OAuthCallback() {
    const { refreshUser } = useAuth();
    const queryClient = useQueryClient();
    const navigate = useNavigate();

    useEffect(() => {
        let cancelled = false;
        (async () => {
            await refreshUser();
            if (cancelled) return;
            // `user` from useAuth() is stale in this render, so read the refetched value from the cache.
            const user = queryClient.getQueryData(USER_QUERY_KEY);
            navigate(user ? homeFor(user) : "/login?error=failed", {
                replace: true,
            });
        })();
        return () => {
            cancelled = true;
        };
    }, [refreshUser, queryClient, navigate]);

    return <FullPageSpinner />;
}
