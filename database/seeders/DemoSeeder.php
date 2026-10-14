<?php

namespace Database\Seeders;

use App\Enums\HouseStatus;
use App\Enums\MaintenanceCategory;
use App\Enums\MaintenanceStatus;
use App\Enums\PaymentType;
use App\Enums\Priority;
use App\Enums\ReservationStatus;
use App\Enums\Role;
use App\Models\House;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Optional demo data, run after the base seed with
 * `php artisan db:seed --class=DemoSeeder`. It follows the plan §1.2 rules:
 * a paid purchase (tenant@), a cancelled reservation with a forfeited fee and
 * an active reservation with instalments (prospect@), plus maintenance
 * requests in every status. Model events must stay on: Payment sets its
 * receipt number in its created event.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Running it twice would book units that are already sold or reserved.
        if (Reservation::exists()) {
            $this->command?->warn('Demo data skipped: reservations already exist. Run migrate:fresh --seed first.');

            return;
        }

        $tenant = User::where('email', 'tenant@luminest.test')->firstOrFail();
        $prospect = User::where('email', 'prospect@luminest.test')->firstOrFail();

        // Aimee Rowhouse, ₱1,500,000: paid off, so the house is sold to the tenant.
        $home = $this->unit(9, 1);
        $this->reserve($tenant, $home, [
            [50_000, $this->daysAgo(60)],
            [450_000, $this->daysAgo(40)],
            [1_000_000, $this->daysAgo(30)],
        ]);

        // Armina Single, ₱3,500,000: cancelled, the ₱5,000 fee is not refunded (R6).
        $this->reserve($prospect, $this->unit(2, 3), [
            [5_000, $this->daysAgo(20)],
        ], cancelledAt: $this->daysAgo(18));

        // Angelique Duplex, ₱2,500,000: still reserved, balance ₱2,145,000.
        $this->reserve($prospect, $this->unit(1, 5), [
            [5_000, $this->daysAgo(10)],
            [100_000, $this->daysAgo(6)],
            [250_000, $this->daysAgo(1)],
        ]);

        $this->maintenanceRequests($tenant, $home->refresh());
    }

    /**
     * Records the payments in order and applies the end state the rules give:
     * paid when the balance reaches ₱0 (R5), cancelled when $cancelledAt is set (R6),
     * otherwise reserved (R1).
     *
     * @param  list<array{0: int, 1: Carbon}>  $payments  [amount, paid at], oldest first
     */
    private function reserve(User $buyer, House $house, array $payments, ?Carbon $cancelledAt = null): void
    {
        $reservation = $house->reservations()->forceCreate([
            'user_id' => $buyer->id,
            'status' => ReservationStatus::Reserved,
            'total_price' => $house->houseType->price,
            'created_at' => $payments[0][1],
        ]);

        $balance = $reservation->total_price;

        foreach ($payments as $i => [$amount, $paidAt]) {
            $balance -= $amount;

            $reservation->payments()->create([
                'type' => match (true) {
                    $i === 0 => PaymentType::ReservationFee,
                    $balance === 0 => PaymentType::Final,
                    default => PaymentType::Instalment,
                },
                'amount' => $amount,
                'balance_after' => $balance,
                'paid_at' => $paidAt,
            ]);
        }

        $lastPaidAt = end($payments)[1];

        if ($balance === 0) {
            $reservation->update(['status' => ReservationStatus::Paid, 'paid_at' => $lastPaidAt]);
            $house->update(['status' => HouseStatus::Sold, 'owner_id' => $buyer->id, 'purchased_at' => $lastPaidAt]);
            $buyer->update(['role' => Role::Tenant]);
        } elseif ($cancelledAt) {
            $reservation->update(['status' => ReservationStatus::Cancelled, 'cancelled_at' => $cancelledAt]);
        } else {
            $house->update(['status' => HouseStatus::Reserved]);
        }
    }

    /**
     * One request per status. Assigned ones use plumbing or electrical, the
     * expertise of the two seeded staff.
     */
    private function maintenanceRequests(User $tenant, House $home): void
    {
        $plumber = User::where('email', 'staff.plumbing@luminest.test')->firstOrFail();
        $electrician = User::where('email', 'staff.electrical@luminest.test')->firstOrFail();

        $requests = [
            [
                'title' => 'Kitchen cabinet door is loose',
                'description' => 'The left cabinet door above the sink hangs off one hinge.',
                'category' => MaintenanceCategory::Carpentry,
                'priority' => Priority::Low,
                'status' => MaintenanceStatus::Pending,
                'created_at' => $this->daysAgo(1),
            ],
            [
                'title' => 'Outlet sparks when plugging in',
                'description' => 'The living room outlet near the TV sparks every time something is plugged in.',
                'category' => MaintenanceCategory::Electrical,
                'priority' => Priority::Urgent,
                'status' => MaintenanceStatus::Accepted,
                'assigned_staff_id' => $electrician->id,
                'created_at' => $this->daysAgo(2),
            ],
            [
                'title' => 'Kitchen sink is leaking',
                'description' => 'Water drips from the pipe under the kitchen sink.',
                'category' => MaintenanceCategory::Plumbing,
                'priority' => Priority::High,
                'status' => MaintenanceStatus::InProgress,
                'assigned_staff_id' => $plumber->id,
                'created_at' => $this->daysAgo(5),
            ],
            [
                'title' => 'Porch light does not turn on',
                'description' => 'The porch light stopped working even after changing the bulb.',
                'category' => MaintenanceCategory::Electrical,
                'priority' => Priority::Medium,
                'status' => MaintenanceStatus::Completed,
                'assigned_staff_id' => $electrician->id,
                'resolution_notes' => 'Replaced the faulty wall switch.',
                'completed_at' => $this->daysAgo(3),
                'created_at' => $this->daysAgo(8),
            ],
            [
                'title' => 'Low water pressure in the shower',
                'description' => 'The shower only gives a weak stream in the mornings.',
                'category' => MaintenanceCategory::Plumbing,
                'priority' => Priority::Medium,
                'status' => MaintenanceStatus::Resolved,
                'assigned_staff_id' => $plumber->id,
                'resolution_notes' => 'Cleaned the clogged shower head and aerator.',
                'completed_at' => $this->daysAgo(20),
                'resolved_at' => $this->daysAgo(19),
                'created_at' => $this->daysAgo(25),
            ],
        ];

        foreach ($requests as $request) {
            $tenant->maintenanceRequests()->forceCreate($request + ['house_id' => $home->id]);
        }
    }

    private function unit(int $block, int $lot): House
    {
        return House::where(['block' => $block, 'lot' => $lot])->firstOrFail();
    }

    private function daysAgo(int $days): Carbon
    {
        return now()->subDays($days)->setTime(10, 0);
    }
}
