<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Mralston\Payment\Models\PaymentStatus;

class PaymentStatusesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PaymentStatus::create([
            'identifier' => 'new',
            'name' => 'New'
        ]);

        // Tandem statuses

        PaymentStatus::create([
            'identifier' => 'declined',
            'name' => 'Declined'
        ]);

        PaymentStatus::create([
            'identifier' => 'pending',
            'name' => 'Pending'
        ]);

        PaymentStatus::create([
            'identifier' => 'referred',
            'name' => 'Referred'
        ]);

        PaymentStatus::create([
            'identifier' => 'conditional_accept',
            'name' => 'Conditional Accept'
        ]);

        PaymentStatus::create([
            'identifier' => 'accepted',
            'name' => 'Accepted'
        ]);

        PaymentStatus::create([
            'identifier' => 'documents_received',
            'name' => 'Documents Received'
        ]);

        PaymentStatus::create([
            'identifier' => 'snagged',
            'name' => 'Snagged'
        ]);

        PaymentStatus::create([
            'identifier' => 'parked',
            'name' => 'Parked'
        ]);

        PaymentStatus::create([
            'identifier' => 'customer_cancelled',
            'name' => 'Customer Cancelled'
        ]);

        PaymentStatus::create([
            'identifier' => 'payout_requested',
            'name' => 'Payout Requested'
        ]);

        PaymentStatus::create([
            'identifier' => 'active',
            'name' => 'Active'
        ]);

        PaymentStatus::create([
            'identifier' => 'live',
            'name' => 'Live'
        ]);

        PaymentStatus::create([
            'identifier' => 'expired',
            'name' => 'Expired'
        ]);

        PaymentStatus::create([
            'identifier' => 'NotFound',
            'name' => 'Not Found'
        ]);

        PaymentStatus::create([
            'identifier' => 'error',
            'name' => 'Error'
        ]);

        PaymentStatus::create([
            'identifier' => 'cancelled',
            'name' => 'Cancelled'
        ]);

        // Hometree statuses

        PaymentStatus::create([
            'identifier' => 'pending-applicants',
            'name' => 'Pending applicants'
        ]);

        PaymentStatus::create([
            'identifier' => 'pending-customer-choice',
            'name' => 'Pending customer choice'
        ]);

        PaymentStatus::create([
            'identifier' => 'pending-underwriting-review',
            'name' => 'Pending underwriting review'
        ]);

        PaymentStatus::create([
            'identifier' => 'pending-customer-data',
            'name' => 'Pending customer data'
        ]);

        PaymentStatus::create([
            'identifier' => 'pending-customer-agreement',
            'name' => 'Pending customer agreement'
        ]);

        PaymentStatus::create([
            'identifier' => 'pending-documentation-review',
            'name' => 'Pending documentation review'
        ]);

        PaymentStatus::create([
            'identifier' => 'pending-installation',
            'name' => 'Pending installation'
        ]);

        PaymentStatus::create([
            'identifier' => 'pending-quote-review',
            'name' => 'Pending quote review'
        ]);

        PaymentStatus::create([
            'identifier' => 'processing',
            'name' => 'Processing'
        ]);

        PaymentStatus::create([
            'identifier' => 'active-installed',
            'name' => 'Active installed'
        ]);

        PaymentStatus::create([
            'identifier' => 'active-completed',
            'name' => 'Active completed'
        ]);

        PaymentStatus::create([
            'identifier' => 'final-declined',
            'name' => 'Final declined'
        ]);

        PaymentStatus::create([
            'identifier' => 'final-cancelled',
            'name' => 'Final cancelled'
        ]);

        PaymentStatus::create([
            'identifier' => 'final-ended',
            'name' => 'Final ended'
        ]);

        PaymentStatus::create([
            'identifier' => 'final-abandoned',
            'name' => 'Final abandoned'
        ]);

        PaymentStatus::create([
            'identifier' => 'final-archived',
            'name' => 'Final archived'
        ]);

    }
}
