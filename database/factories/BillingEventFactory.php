<?php

namespace Database\Factories;

use App\Models\BillingEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BillingEvent> */
class BillingEventFactory extends Factory
{
    public function definition(): array
    {
        return ['id' => 'WH-'.fake()->uuid(), 'type' => 'BILLING.SUBSCRIPTION.ACTIVATED', 'subscription_id' => null, 'status' => 'processed'];
    }
}
