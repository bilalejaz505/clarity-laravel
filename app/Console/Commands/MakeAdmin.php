<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AppSettingsService;
use Illuminate\Console\Command;

class MakeAdmin extends Command
{
    protected $signature = 'clarity:admin {email} {--name=Administrator}';

    protected $description = 'Create or promote an administrator using a trusted console; passwords never appear in arguments.';

    public function handle(): int
    {
        $email = strtolower($this->argument('email'));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Enter a valid email address.');

            return self::FAILURE;
        }
        $user = User::where('email', $email)->first();
        if (! $user) {
            $password = $this->secret(
                'Choose an admin password (at least 10 characters)',
            );
            if (strlen((string) $password) < 10) {
                $this->error('Password is too short.');

                return self::FAILURE;
            }
            $user = User::create([
                'name' => $this->option('name'),
                'email' => $email,
                'password' => $password,
            ]);
        }
        $user
            ->forceFill([
                'is_admin' => true,
                'status' => 'active',
                'email_verified_at' => now(),
            ])
            ->save();
        app(AppSettingsService::class)->audit(
            'console',
            'admin.granted',
            $user->id,
        );
        $this->info('Administrator access enabled. Log in using this account.');

        return self::SUCCESS;
    }
}
