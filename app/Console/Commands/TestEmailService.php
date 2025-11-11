<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\EmailService;
use Illuminate\Support\Facades\Log;

class TestEmailService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email-service';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test EmailService configuration and functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing EmailService...');

        try {
            // Test configuration loading
            $this->info('Mail config values:');
            $this->info('Host: ' . config('mail.mailers.smtp.host'));
            $this->info('Username: ' . (config('mail.mailers.smtp.username') ? 'SET' : 'NOT SET'));
            $this->info('Password: ' . (config('mail.mailers.smtp.password') ? 'SET' : 'NOT SET'));
            $this->info('Port: ' . config('mail.mailers.smtp.port'));
            $this->info('From Address: ' . config('mail.from.address'));
            $this->info('From Name: ' . config('mail.from.name'));

            // Test EmailService instantiation
            $this->info('Creating EmailService instance...');
            $emailService = app(EmailService::class);
            $this->info('✅ EmailService created successfully');

            // Test route generation
            $this->info('Testing route generation...');
            $testToken = 'test-token-123';
            $verificationUrl = route('email.verify', ['token' => $testToken]);
            $this->info('Verification URL: ' . $verificationUrl);

            $this->info('✅ All tests passed!');

        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            Log::error('EmailService test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return 0;
    }
}
