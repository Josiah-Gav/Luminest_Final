import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { StrictMode } from "react";
import { createRoot } from "react-dom/client";
import { Toaster } from "react-hot-toast";
import { BrowserRouter } from "react-router-dom";
import AuthProvider from "./auth/AuthProvider";
import "./index.css";
import AppRoutes from "./routes";

// Caches server data fetched with useQuery and shares it between pages.
const queryClient = new QueryClient();

createRoot(document.getElementById("root")).render(
    <StrictMode>
        <QueryClientProvider client={queryClient}>
            <BrowserRouter>
                <AuthProvider>
                    <AppRoutes />
                </AuthProvider>
                <Toaster
                    position="top-right"
                    toastOptions={{
                        className: "font-sans! text-sm! text-ink!",
                        success: {
                            iconTheme: {
                                primary: "#16a34a",
                                secondary: "#fff",
                            },
                        },
                        error: {
                            iconTheme: {
                                primary: "#c1121f",
                                secondary: "#fff",
                            },
                        },
                    }}
                />
            </BrowserRouter>
        </QueryClientProvider>
    </StrictMode>,
);
