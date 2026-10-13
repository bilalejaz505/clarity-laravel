<?php

namespace App\Console\Commands;

use Database\Seeders\LocalWorkspaceSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ResetLocalData extends Command
{
    protected $signature = 'clarity:reset-local-data {--reset : Replace local account/workspace records} {--credentials= : Private file for generated login details}';

    protected $description = 'Initialize an administrator and three package-scoped local accounts.';

    public function handle(): int
    {
        if (! app()->environment('local') || ! $this->option('reset')) {
            $this->error(
                'This command requires APP_ENV=local and an explicit --reset. Back up MySQL first.',
            );

            return self::FAILURE;
        }
        $path =
            $this->option('credentials') ?:
            storage_path('app/private/local-access.txt');
        if (file_exists($path) || ! is_dir(dirname($path))) {
            $this->error(
                'Choose a new credentials file in an existing private directory.',
            );

            return self::FAILURE;
        }
        $passwords = [
            'admin' => Str::password(22, symbols: false),
            'chat' => Str::password(22, symbols: false),
            'freelance' => Str::password(22, symbols: false),
            'bundle' => Str::password(22, symbols: false),
        ];
        $text = $this->credentialsText($passwords);
        if (file_put_contents($path, $text, LOCK_EX) === false) {
            $this->error(
                'Unable to save the private credentials file. No database changes made.',
            );

            return self::FAILURE;
        }
        (new LocalWorkspaceSeeder($passwords))->run();
        $this->info(
            'Initialized 1 administrator and 3 members with chat, contracts, and complete package access.',
        );
        $this->info('Passwords saved privately to: '.$path);

        return self::SUCCESS;
    }

    private function credentialsText(array $passwords): string
    {
        $memberLogin = url('/login');

        return 'Clarity local access — generated on '.
            now()->format('Y-m-d').
            "\n\n".
            "Administrator\nEmail: admin@clarity.com\nPassword: {$passwords['admin']}\nAccess: Admin, Chat Insights, Contracts\nLogin: ".
            url('/admin/login').
            "\n\n".
            "Chat member\nEmail: chat@clarity.com\nPassword: {$passwords['chat']}\nAccess: Chat Insights only\nLogin: $memberLogin\n\n".
            "Contracts member\nEmail: contracts@clarity.com\nPassword: {$passwords['freelance']}\nAccess: Contracts only\nLogin: $memberLogin\n\n".
            "Complete member\nEmail: complete@clarity.com\nPassword: {$passwords['bundle']}\nAccess: Chat Insights and Contracts\nLogin: $memberLogin\n\n".
            "Local setup credentials. Change them before any public deployment. Keep this file private.\n";
    }
}
