<?php

namespace App\Console\Commands;

use App\Mail\OtpMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestMailCommand extends Command
{
    protected $signature = 'mail:test {email : Recipient email address}';

    protected $description = 'Send a test OTP email to verify SMTP configuration';

    public function handle(): int
    {
        $email = $this->argument('email');

        $this->info('Mail configuration:');
        $this->line('  MAIL_MAILER  : ' . config('mail.default'));
        $this->line('  MAIL_HOST    : ' . config('mail.mailers.smtp.host'));
        $this->line('  MAIL_PORT    : ' . config('mail.mailers.smtp.port'));
        $this->line('  MAIL_SCHEME  : ' . (config('mail.mailers.smtp.scheme') ?: '(empty — correct for port 587 STARTTLS)'));
        $this->line('  MAIL_USERNAME: ' . config('mail.mailers.smtp.username'));
        $this->line('  MAIL_FROM    : ' . config('mail.from.address'));
        $this->newLine();

        $username = config('mail.mailers.smtp.username', '');
        if (empty($username) || str_contains(strtolower($username), 'your_gmail')) {
            $this->error('MAIL_USERNAME is still a placeholder. Edit .env and set your real Gmail address.');
            $this->line('  See .env for setup instructions.');
            return self::FAILURE;
        }

        $this->info("Sending test OTP email to: {$email}");

        try {
            Mail::to($email)->send(new OtpMail('Test User', '123456', 5, 'registration'));
            $this->info('✓ Email sent successfully! Check your inbox (and spam folder).');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('✗ Email send failed: ' . $e->getMessage());
            $this->newLine();
            $this->line('Common fixes:');
            $this->line('  1. Verify MAIL_USERNAME and MAIL_PASSWORD are correct in .env');
            $this->line('  2. MAIL_SCHEME must be empty for port 587 (not "tls" or "ssl")');
            $this->line('  3. Gmail requires 2FA + App Password (not your regular password)');
            $this->line('  4. App Password: https://myaccount.google.com/apppasswords');
            return self::FAILURE;
        }
    }
}
