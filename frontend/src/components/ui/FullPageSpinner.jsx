import Spinner from "./Spinner";

export default function FullPageSpinner() {
    return (
        <div
            className="flex min-h-screen items-center justify-center text-brand-blue"
            role="status"
        >
            <Spinner className="size-8" />
            <span className="sr-only">Loading…</span>
        </div>
    );
}
