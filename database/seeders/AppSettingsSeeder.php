<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Services\AppSettingsService;
use Illuminate\Database\Seeder;

class AppSettingsSeeder extends Seeder
{
    public function run(): void
    {
        AppSetting::firstOrCreate(['id' => 'application'], ['data' => app(AppSettingsService::class)->defaults(), 'revision' => 0]);
    }
}
