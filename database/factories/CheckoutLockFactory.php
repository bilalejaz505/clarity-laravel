<?php

namespace Database\Factories;

use App\Models\CheckoutLock;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CheckoutLock> */
class CheckoutLockFactory extends Factory
{
    public function definition(): array
    {
        return ['user_id' => User::factory(), 'expires_at' => now()->addMinutes(2)];
    }
}
