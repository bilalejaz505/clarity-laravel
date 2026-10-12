<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use App\Services\AppSettingsService;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Subscription> */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        return ['id' => 'I-'.fake()->unique()->numerify('############'), 'user_id' => User::factory(), 'provider_custom_id' => null, 'plan_key' => 'chat', 'status' => 'APPROVAL_PENDING', 'plan_snapshot' => app(AppSettingsService::class)->defaults()['plans'][0]];
    }
}
