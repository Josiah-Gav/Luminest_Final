import clsx from "clsx";
import { NavLink } from "react-router-dom";
import { homeFor } from "../../auth/roles";
import { useAuth } from "../../auth/useAuth";
import Logo from "./Logo";
import { MENU } from "./menu";

const itemClass =
    "group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-semibold";

// The menu is built from the user's role. `onNavigate` closes the mobile drawer.
export default function Sidebar({ onNavigate }) {
    const { user } = useAuth();

    return (
        <div className="flex h-full flex-col gap-8 overflow-y-auto border-r border-gray-200 bg-white px-4 py-6">
            <Logo to={homeFor(user)} className="px-2" />

            <nav aria-label="Main menu">
                <ul className="space-y-1">
                    {MENU[user.role].map(({ label, to, icon: Icon, soon }) => (
                        <li key={to}>
                            {soon ? (
                                <span
                                    className={clsx(
                                        itemClass,
                                        "cursor-default text-gray-400",
                                    )}
                                    aria-disabled="true"
                                >
                                    <Icon
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                    {label}
                                    <span className="ml-auto rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-500">
                                        Soon
                                    </span>
                                </span>
                            ) : (
                                <NavLink
                                    to={to}
                                    end
                                    onClick={onNavigate}
                                    className={({ isActive }) =>
                                        clsx(
                                            itemClass,
                                            "focus-visible:outline-2 focus-visible:outline-brand-blue",
                                            isActive
                                                ? "bg-brand-blue/10 text-brand-blue"
                                                : "text-gray-700 hover:bg-gray-100 hover:text-ink",
                                        )
                                    }
                                >
                                    <Icon
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                    {label}
                                </NavLink>
                            )}
                        </li>
                    ))}
                </ul>
            </nav>
        </div>
    );
}
