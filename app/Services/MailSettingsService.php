<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;

class MailSettingsService
{
    public function __construct(private AppSettingsService $settings) {}

    public function apply(?array $config = null): void
    {
        $config ??= $this->settings->get();
        $mailer = $config['smtpEnabled'] ? 'smtp' : 'log';

        config()->set([
            'mail.default' => $mailer,
            'mail.mailers.smtp' => [
                'transport' => 'smtp',
                'scheme' => $config['smtpEncryption'] === 'ssl'
                    ? 'smtps'
                    : null,
                'url' => null,
                'host' => $config['smtpHost'],
                'port' => $config['smtpPort'],
                'username' => $config['smtpUsername'] ?: null,
                'password' => $this->settings->secret('smtp') ?: null,
                'timeout' => 20,
                'local_domain' => parse_url(config('app.url'), PHP_URL_HOST),
            ],
            'mail.from.address' => $config['smtpFromAddress'],
            'mail.from.name' => $config['smtpFromName'] ?: $config['appName'],
        ]);
        Mail::purge('smtp');
    }
}
