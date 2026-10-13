<?php

namespace Database\Factories;

use App\Models\UsageCounter;
use App\Models\UsageReservation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UsageReservation> */
class UsageReservationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'counter_id' => UsageCounter::factory(),
            'user_id' => fn (array $attributes) => UsageCounter::findOrFail(
                $attributes['counter_id'],
            )->user_id,
            'product' => 'chat',
            'kind' => 'ask',
            'messages' => 1,
            'status' => 'reserved',
        ];
    }
}
