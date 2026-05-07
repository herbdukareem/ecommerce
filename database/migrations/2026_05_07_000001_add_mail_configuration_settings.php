<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'mail_mode' => 'auto',
            'mail_sandbox_mailer' => 'log',
            'mail_sandbox_host' => 'sandbox.smtp.mailtrap.io',
            'mail_sandbox_port' => '2525',
            'mail_sandbox_username' => '',
            'mail_sandbox_encryption' => 'tls',
            'mail_sandbox_from_address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
            'mail_sandbox_from_name' => env('MAIL_FROM_NAME', config('app.name')),
            'mail_live_mailer' => env('MAIL_MAILER', 'smtp'),
            'mail_live_host' => env('MAIL_HOST', 'live.smtp.mailtrap.io'),
            'mail_live_port' => (string) env('MAIL_PORT', 587),
            'mail_live_username' => env('MAIL_USERNAME', ''),
            'mail_live_encryption' => env('MAIL_ENCRYPTION', 'tls'),
            'mail_live_from_address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
            'mail_live_from_name' => env('MAIL_FROM_NAME', config('app.name')),
        ];

        foreach ($defaults as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'created_at' => now(), 'updated_at' => now()]
            );
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->whereIn('key', [
                'mail_mode',
                'mail_sandbox_mailer',
                'mail_sandbox_host',
                'mail_sandbox_port',
                'mail_sandbox_username',
                'mail_sandbox_encryption',
                'mail_sandbox_from_address',
                'mail_sandbox_from_name',
                'mail_live_mailer',
                'mail_live_host',
                'mail_live_port',
                'mail_live_username',
                'mail_live_encryption',
                'mail_live_from_address',
                'mail_live_from_name',
            ])
            ->delete();
    }
};
