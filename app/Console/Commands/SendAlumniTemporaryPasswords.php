<?php

namespace App\Console\Commands;

use App\Models\Alumni;
use App\Notifications\AlumniTemporaryPasswordNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SendAlumniTemporaryPasswords extends Command
{
    protected $signature = 'alumni:send-temporary-passwords
                            {--limit=0 : Limit number of emails to send}
                            {--force : Send even if email was previously sent}';

    protected $description = 'Send temporary password emails to imported alumni';

    public function handle(): int
    {
        $query = Alumni::query()
            ->where('status', 'pending')
            ->where('is_active', false);

        if (!$this->option('force')) {
            $query->whereNull('temporary_password_sent_at');
        }

        $limit = (int) $this->option('limit');

        if ($limit > 0) {
            $query->limit($limit);
        }

        $alumni = $query->get();

        if ($alumni->isEmpty()) {
            $this->warn('No alumni found requiring temporary password emails.');
            return self::SUCCESS;
        }

        $this->info("Sending emails to {$alumni->count()} alumni...");
        $this->newLine();

        $bar = $this->output->createProgressBar($alumni->count());
        $bar->start();

        foreach ($alumni as $alumnus) {

            try {

                // generate new temporary password
                $temporaryPassword = Str::random(10);

                $alumnus->password = bcrypt($temporaryPassword);
                $alumnus->save();

                $alumnus->notify(
                    new AlumniTemporaryPasswordNotification($temporaryPassword)
                );

                $alumnus->update([
                    'temporary_password_sent_at' => now(),
                ]);

                $this->newLine();

                $this->info("✓ {$alumnus->email}");

            } catch (\Throwable $e) {

                $this->newLine();

                $this->error("✗ {$alumnus->email}");

                $this->line($e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();

        $this->newLine(2);

        $this->info("================================");
        $this->info("Completed Successfully");
        $this->info("================================");

        return self::SUCCESS;
    }
}