<?php

namespace App\Console\Commands;

use App\Imports\StudentsImport;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportStudentsCommand extends Command
{
    protected $signature = 'students:import {file : Path to Excel/CSV file}';
    protected $description = 'Import 12,000+ students safely (no timeout, skips duplicates)';

    public function handle()
    {
        $path = $this->argument('file');

        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return 1;
        }

        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', 0);

        $this->info("Starting import from:");
        $this->warn($path);
        $this->newLine();
        $this->info("Processing 12,000+ students... Please wait (30–60 seconds)");

        $import = new StudentsImport();
        $start = microtime(true);

        Excel::import($import, $path);

        $time = round(microtime(true) - $start, 1);
        $total = $import->imported + $import->duplicates;

        $this->newLine(2);
        $this->components->info("IMPORT COMPLETED SUCCESSFULLY!");
        $this->table(
            ['Status', 'Count'],   
            [
                ['Total rows processed', $total],
                ['Successfully imported', $import->imported],
                ['Duplicates skipped', $import->duplicates],
                ['Time taken', "{$time} seconds"],
            ]
        );

        $this->info("All passwords are securely hashed with Bcrypt.");
        $this->info("Students can now login using Email + Reg No as password.");

        return 0;
    }
}