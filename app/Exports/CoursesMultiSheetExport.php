<?php

namespace App\Exports;

use App\Exports\Sheets\CourseFacultyCountsSheet;
use App\Exports\Sheets\CoursesSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CoursesMultiSheetExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            'Courses' => new CoursesSheet(),
            'Faculty Student Counts' => new CourseFacultyCountsSheet(),
        ];
    }
}