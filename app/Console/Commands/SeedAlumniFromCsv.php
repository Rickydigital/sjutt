<?php

namespace App\Console\Commands;

use App\Imports\AlumniImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class SeedAlumniFromCsv extends Command
{
    protected $signature = 'alumni:seed-csv {file=database/seeders/data/alumni_import_ready_exact.csv}';

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
        Excel::import(new AlumniImport(), $file);

        $this->newLine();
        $this->info('=====================================');
        $this->info(' Alumni import completed successfully');
        $this->info('=====================================');

        return self::SUCCESS;

    } catch (\Throwable $e) {

        $this->newLine();
        $this->error('Import failed.');
        $this->error($e->getMessage());

        return self::FAILURE;
    }
}
}