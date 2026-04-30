<?php

namespace App\Imports;

use App\Imports\Sheets\CourseFacultyCountsImportSheet;
use App\Imports\Sheets\CoursesImportSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CoursesMultiSheetImport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Courses' => new CoursesImportSheet(),
            'Faculty Student Counts' => new CourseFacultyCountsImportSheet(),
        ];
    }
}