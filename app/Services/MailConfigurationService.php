<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MailConfigurationService
{
    public function apply(): void
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }

            $settings = $this->settings();
            $mode = $this->resolveMode((string) ($settings['mail_mode'] ?? 'auto'));
            $prefix = $mode === 'live' ? 'mail_live_' : 'mail_sandbox_';

            $mailer = (string) ($settings[$prefix . 'mailer'] ?? env('MAIL_MAILER', 'log'));
            $host = (string) ($settings[$prefix . 'host'] ?? env('MAIL_HOST', '127.0.0.1'));
            $port = (int) ($settings[$prefix . 'port'] ?? env('MAIL_PORT', 2525));
            $username = $settings[$prefix . 'username'] ?? env('MAIL_USERNAME');
            $password = $this->password($settings[$prefix . 'password'] ?? null, env('MAIL_PASSWORD'));
            $encryption = $settings[$prefix . 'encryption'] ?? env('MAIL_ENCRYPTION');
            $fromAddress = (string) ($settings[$prefix . 'from_address'] ?? env('MAIL_FROM_ADDRESS', 'hello@example.com'));
            $fromName = (string) ($settings[$prefix . 'from_name'] ?? env('MAIL_FROM_NAME', config('app.name')));

            Config::set('mail.default', $mailer);
            Config::set('mail.mailers.smtp.host', $host);
            Config::set('mail.mailers.smtp.port', $port);
            Config::set('mail.mailers.smtp.username', $this->normalizeNullable($username));
            Config::set('mail.mailers.smtp.password', $this->normalizeNullable($password));
            Config::set('mail.mailers.smtp.encryption', $this->normalizeNullable($encryption));
            Config::set('mail.mailers.smtp.scheme', $this->smtpScheme($encryption));
            Config::set('mail.from.address', $fromAddress);
            Config::set('mail.from.name', $fromName);
        } catch (\Throwable) {
            // Keep the app bootable during install, deploy, or database maintenance.
        }
    }

    public function publicSettings(): array
    {
        $settings = $this->settings();

        return [
            'mail_mode' => (string) ($settings['mail_mode'] ?? 'auto'),
            'active_mail_mode' => $this->resolveMode((string) ($settings['mail_mode'] ?? 'auto')),
            'mail_sandbox_mailer' => (string) ($settings['mail_sandbox_mailer'] ?? 'log'),
            'mail_sandbox_host' => (string) ($settings['mail_sandbox_host'] ?? 'sandbox.smtp.mailtrap.io'),
            'mail_sandbox_port' => (string) ($settings['mail_sandbox_port'] ?? '2525'),
            'mail_sandbox_username' => (string) ($settings['mail_sandbox_username'] ?? ''),
            'mail_sandbox_encryption' => (string) ($settings['mail_sandbox_encryption'] ?? 'tls'),
            'mail_sandbox_from_address' => (string) ($settings['mail_sandbox_from_address'] ?? env('MAIL_FROM_ADDRESS', 'hello@example.com')),
            'mail_sandbox_from_name' => (string) ($settings['mail_sandbox_from_name'] ?? env('MAIL_FROM_NAME', config('app.name'))),
            'mail_sandbox_password_configured' => !empty($settings['mail_sandbox_password']),
            'mail_live_mailer' => (string) ($settings['mail_live_mailer'] ?? 'smtp'),
            'mail_live_host' => (string) ($settings['mail_live_host'] ?? env('MAIL_HOST', 'live.smtp.mailtrap.io')),
            'mail_live_port' => (string) ($settings['mail_live_port'] ?? env('MAIL_PORT', 587)),
            'mail_live_username' => (string) ($settings['mail_live_username'] ?? env('MAIL_USERNAME', '')),
            'mail_live_encryption' => (string) ($settings['mail_live_encryption'] ?? env('MAIL_ENCRYPTION', 'tls')),
            'mail_live_from_address' => (string) ($settings['mail_live_from_address'] ?? env('MAIL_FROM_ADDRESS', 'hello@example.com')),
            'mail_live_from_name' => (string) ($settings['mail_live_from_name'] ?? env('MAIL_FROM_NAME', config('app.name'))),
            'mail_live_password_configured' => !empty($settings['mail_live_password']),
        ];
    }

    public function forgetCache(): void
    {
        Cache::forget('site_settings');
    }

    protected function settings(): array
    {
        return Cache::remember('site_settings', 3600, function () {
            try {
                if (!Schema::hasTable('settings')) {
                    return [];
                }

                return DB::table('settings')->pluck('value', 'key')->toArray();
            } catch (\Throwable) {
                return [];
            }
        });
    }

    protected function resolveMode(string $mode): string
    {
        if ($mode === 'live') {
            return 'live';
        }

        if ($mode === 'sandbox') {
            return 'sandbox';
        }

        return app()->environment('production') ? 'live' : 'sandbox';
    }

    protected function password(?string $storedPassword, mixed $fallback = null): mixed
    {
        if (!$storedPassword) {
            return $fallback;
        }

        try {
            return Crypt::decryptString($storedPassword);
        } catch (\Throwable) {
            return $storedPassword;
        }
    }

    protected function normalizeNullable(mixed $value): mixed
    {
        return in_array($value, ['', 'null', 'none', null], true) ? null : $value;
    }

    protected function smtpScheme(mixed $encryption): ?string
    {
        return $encryption === 'ssl' ? 'smtps' : null;
    }
}
