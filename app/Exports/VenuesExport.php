<?php
namespace App\Exports;

use App\Models\Venue;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VenuesExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Venue::with('building')->get()->map(function ($venue) {
            return [
                'Name' => $venue->name,
                'Longform' => $venue->longform,
                'Building' => optional($venue->building)->name,
                'Capacity' => $venue->capacity,
                'Type' => $venue->type,
                'Latitude' => $venue->lat,
                'Longitude' => $venue->lng,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Name',
            'Longform',
            'Building',
            'Capacity',
            'Type',
            'Latitude',
            'Longitude',
        ];
    }
}
