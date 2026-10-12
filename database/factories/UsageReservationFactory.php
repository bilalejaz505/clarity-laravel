<?php

namespace Database\Factories;

use App\Models\UsageReservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UsageReservation> */
class UsageReservationFactory extends Factory
{
    public function definition(): array
    {
        return ['id' => fake()->uuid(), 'user_id' => User::factory(), 'counter_id' => hash('sha256', fake()->uuid()), 'product' => 'chat', 'kind' => 'ask', 'messages' => 1, 'status' => 'reserved'];
    }
}
