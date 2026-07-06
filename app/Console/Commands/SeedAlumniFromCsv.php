<?php

namespace App\Console\Commands;

use App\Imports\AlumniImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class SeedAlumniFromCsv extends Command
{
    protected $signature = 'alumni:seed-csv {file=database/seeders/data/alumni_import_ready_exact.csv} {--no-mail}';

    protected $description = 'Seed/import alumni from CSV file using AlumniImport';

    public function handle(): int
    {
        $file = base_path($this->argument('file'));

        if (! file_exists($file)) {
            $this->error("File not found: {$file}");
            return self::FAILURE;
        }

        $this->info("Starting Alumni import...");
        $this->line("Source: {$file}");

        try {
            $import = new AlumniImport(
                command: $this,
                sendMail: ! $this->option('no-mail')
            );

            Excel::import($import, $file);

            $this->newLine();
            $this->info('=====================================');
            $this->info(' Alumni import completed successfully');
            $this->info('=====================================');
            $this->line("Rows read: {$import->rowsRead}");
            $this->line("Created: {$import->created}");
            $this->line("Updated: {$import->updated}");
            $this->line("Skipped: {$import->skipped}");
            $this->line("Emails sent: {$import->emailsSent}");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->newLine();
            $this->error('Import failed.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}