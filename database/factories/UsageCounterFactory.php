<?php

namespace Database\Factories;

use App\Models\UsageCounter;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UsageCounter> */
class UsageCounterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'bucket_key' => hash('sha256', fake()->uuid()),
            'user_id' => User::factory(),
            'messages' => 0,
            'requests' => 0,
        ];
    }
}
