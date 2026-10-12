<?php

namespace Database\Factories;

use App\Models\LegacyImport;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LegacyImport> */
class LegacyImportFactory extends Factory
{
    public function definition(): array
    {
        return ['id' => hash('sha256', fake()->uuid()), 'summary' => ['users' => 0], 'created_at' => now()];
    }
}
