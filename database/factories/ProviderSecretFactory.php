<?php

namespace Database\Factories;

use App\Models\ProviderSecret;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProviderSecret> */
class ProviderSecretFactory extends Factory
{
    public function definition(): array
    {
        return ['provider' => 'openai', 'ciphertext' => 'fixture-only-never-a-real-key'];
    }
}
