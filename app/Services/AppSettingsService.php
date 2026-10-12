<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\ProviderSecret;
use Illuminate\Support\Str;

class AppSettingsService
{
    public function defaults(): array
    {
        return json_decode(file_get_contents(resource_path('data/defaults.json')), true, 512, JSON_THROW_ON_ERROR);
    }

    public function get(): array
    {
        return array_replace($this->defaults(), AppSetting::find('application')?->data ?? []);
    }

    public function revision(): int
    {
        return AppSetting::find('application')?->revision ?? 0;
    }

    public function secret(string $id): string
    {
        return ProviderSecret::find($id)?->ciphertext ?? '';
    }

    public function saveSecret(string $id, string $value): void
    {
        ProviderSecret::updateOrCreate(['id' => $id], ['ciphertext' => $value]);
    }

    public function audit(string $actor, string $action, string $detail): void
    {
        AuditLog::create(['id' => (string) Str::uuid(), 'actor' => $actor, 'action' => $action, 'detail' => mb_substr($detail, 0, 1000)]);
    }
}
