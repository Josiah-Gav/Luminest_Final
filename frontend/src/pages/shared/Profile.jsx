import ChangePasswordForm from "../../components/profile/ChangePasswordForm";
import ProfileDetailsForm from "../../components/profile/ProfileDetailsForm";

// Shared by every role (route /profile).
export default function Profile() {
    return (
        <div className="max-w-5xl">
            <h1 className="font-display text-3xl font-bold text-ink">
                My profile
            </h1>
            <p className="mt-2 text-gray-600">
                Keep your contact details and password up to date.
            </p>
            <div className="mt-8 grid gap-6 lg:grid-cols-2">
                <ProfileDetailsForm />
                <ChangePasswordForm />
            </div>
        </div>
    );
}
