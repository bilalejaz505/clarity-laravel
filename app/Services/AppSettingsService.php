<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\ProviderSecret;

class AppSettingsService
{
    public function defaults(): array
    {
        return json_decode(
            file_get_contents(resource_path('data/defaults.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    public function get(): array
    {
        return array_replace(
            $this->defaults(),
            AppSetting::where('key', 'application')->first()?->data ?? [],
        );
    }

    public function revision(): int
    {
        return AppSetting::where('key', 'application')->first()?->revision ?? 0;
    }

    public function secret(string $id): string
    {
        return ProviderSecret::where('provider', $id)->first()?->ciphertext ??
            '';
    }

    public function saveSecret(string $id, string $value): void
    {
        ProviderSecret::updateOrCreate(
            ['provider' => $id],
            ['ciphertext' => $value],
        );
    }

    public function audit(
        int|string $actor,
        string $action,
        string $detail,
    ): void {
        AuditLog::create([
            'actor' => (string) $actor,
            'action' => $action,
            'detail' => mb_substr($detail, 0, 1000),
        ]);
    }
}
