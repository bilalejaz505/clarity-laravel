<?php

namespace Database\Factories;

use App\Models\AppSetting;
use App\Services\AppSettingsService;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AppSetting> */
class AppSettingFactory extends Factory
{
    public function definition(): array
    {
        return ['key' => 'application', 'data' => app(AppSettingsService::class)->defaults(), 'revision' => 0];
    }
}
