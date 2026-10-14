import { Dialog, DialogBackdrop, DialogPanel } from "@headlessui/react";
import {
    ArrowRightStartOnRectangleIcon,
    Bars3Icon,
    XMarkIcon,
} from "@heroicons/react/24/outline";
import { useState } from "react";
import toast from "react-hot-toast";
import { Outlet, useNavigate } from "react-router-dom";
import { ROLE_LABELS } from "../../auth/roles";
import { useAuth } from "../../auth/useAuth";
import Button from "../ui/Button";
import Sidebar from "./Sidebar";

// The shell for every logged-in page: sidebar (a drawer on phones), top bar, page.
export default function AppLayout() {
    const { user, logout } = useAuth();
    const navigate = useNavigate();
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [loggingOut, setLoggingOut] = useState(false);

    async function handleLogout() {
        setLoggingOut(true);
        try {
            await logout();
            // A plain /login, so the next person isn't sent back to this user's last page.
            navigate("/login", { replace: true });
            toast.success("You're logged out.");
        } catch {
            toast.error("Logging out failed. Please try again.");
            setLoggingOut(false);
        }
    }

    return (
        <div className="min-h-screen bg-gray-50">
            <Dialog
                open={drawerOpen}
                onClose={setDrawerOpen}
                className="relative z-50 lg:hidden"
            >
                <DialogBackdrop
                    transition
                    className="fixed inset-0 bg-ink/50 transition-opacity duration-300 data-closed:opacity-0"
                />
                <div className="fixed inset-0 flex">
                    <DialogPanel
                        transition
                        className="relative flex w-full max-w-xs transition duration-300 data-closed:-translate-x-full"
                    >
                        <button
                            type="button"
                            onClick={() => setDrawerOpen(false)}
                            className="absolute top-5 -right-12 rounded-lg p-2 text-white focus-visible:outline-2 focus-visible:outline-white"
                        >
                            <XMarkIcon className="size-6" aria-hidden="true" />
                            <span className="sr-only">Close menu</span>
                        </button>
                        <div className="w-full">
                            <Sidebar onNavigate={() => setDrawerOpen(false)} />
                        </div>
                    </DialogPanel>
                </div>
            </Dialog>

            <aside className="hidden lg:fixed lg:inset-y-0 lg:block lg:w-64">
                <Sidebar />
            </aside>

            <div className="lg:pl-64">
                <header className="sticky top-0 z-40 flex h-16 items-center gap-3 border-b border-gray-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
                    <button
                        type="button"
                        onClick={() => setDrawerOpen(true)}
                        className="-ml-2 rounded-lg p-2 text-gray-700 hover:bg-gray-100 focus-visible:outline-2 focus-visible:outline-brand-blue lg:hidden"
                    >
                        <Bars3Icon className="size-6" aria-hidden="true" />
                        <span className="sr-only">Open menu</span>
                    </button>

                    <div className="ml-auto flex min-w-0 items-center gap-3">
                        <div className="min-w-0 text-right">
                            <p className="truncate text-sm font-semibold text-ink">
                                {user.name}
                            </p>
                            <p className="text-xs text-gray-500">
                                {ROLE_LABELS[user.role]}
                            </p>
                        </div>
                        <Button
                            variant="ghost"
                            onClick={handleLogout}
                            loading={loggingOut}
                            className="px-3"
                        >
                            {!loggingOut && (
                                <ArrowRightStartOnRectangleIcon
                                    className="size-5"
                                    aria-hidden="true"
                                />
                            )}
                            <span className="hidden sm:inline">Log out</span>
                            <span className="sr-only sm:hidden">Log out</span>
                        </Button>
                    </div>
                </header>

                <main className="p-4 sm:p-6 lg:p-8">
                    <Outlet />
                </main>
            </div>
        </div>
    );
}
