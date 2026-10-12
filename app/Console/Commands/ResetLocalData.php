<?php

namespace App\Console\Commands;

use Database\Seeders\LocalWorkspaceSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ResetLocalData extends Command
{
    protected $signature = 'clarity:reset-local-data {--reset : Replace local account/workspace records} {--credentials= : Private file for generated login details}';

    protected $description = 'Initialize two local accounts and coherent starter data without fabricating provider payments.';

    public function handle(): int
    {
        if (! app()->environment('local') || ! $this->option('reset')) {
            $this->error('This command requires APP_ENV=local and an explicit --reset. Back up MySQL first.');

            return self::FAILURE;
        }
        $path = $this->option('credentials') ?: storage_path('app/private/local-access.txt');
        if (file_exists($path) || ! is_dir(dirname($path))) {
            $this->error('Choose a new credentials file in an existing private directory.');

            return self::FAILURE;
        }
        $adminPassword = Str::password(22, symbols: false);
        $userPassword = Str::password(22, symbols: false);
        $text = 'Clarity local access — generated on '.now()->format('Y-m-d')."\n\nAdministrator: admin@clarity.com\nPassword: $adminPassword\nLogin: ".url('/admin/login')."\n\nMember: user@clarity.com\nPassword: $userPassword\nLogin: ".url('/login')."\n\nLocal setup credentials. Change them before any public deployment. Keep this file private.\n";
        if (file_put_contents($path, $text, LOCK_EX) === false) {
            $this->error('Unable to save the private credentials file. No database changes made.');

            return self::FAILURE;
        }
        (new LocalWorkspaceSeeder($adminPassword, $userPassword))->run();
        $this->info('Initialized 1 admin, 1 member, 3 plans, 2 independent workspaces, and an active member trial.');
        $this->info('Passwords saved privately to: '.$path);

        return self::SUCCESS;
    }
}
