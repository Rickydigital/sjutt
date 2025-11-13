<?php

namespace App\Imports;

use App\Models\Building;
use App\Models\Venue;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class BuildingsVenuesImport implements ToModel, WithHeadingRow
{
    protected $venueTypes = [
        'lecture_theatre',
        'seminar_room',
        'computer_lab',
        'physics_lab',
        'chemistry_lab',
        'medical_lab',
        'nursing_demo',
        'pharmacy_lab',
        'other'
    ];

    public function model(array $row)
    {
        if (empty($row['venue_name'])) {
            Log::warning('Skipping row due to missing venue_name', $row);
            return null;
        }

        // Handle building (nullable)
        $building_id = null;
        if (!empty($row['building_name'])) {
            $building = Building::firstOrCreate(
                ['name' => $row['building_name']],
                ['description' => $row['building_description'] ?? null]
            );
            $building_id = $building->id;
        }

        // Determine venue type
        $venueType = $row['venue_type'] ?? $this->determineVenueType($row['venue_name']);

        // Create or update venue
        $venue = Venue::updateOrCreate(
            ['name' => $row['venue_name']],
            [
                'longform' => $row['venue_longform'] ?? $this->generateLongform($row['venue_name']),
                'lat' => $row['lat'] ?? null,
                'lng' => $row['lng'] ?? null,
                'building_id' => $building_id,
                'capacity' => $row['capacity'] ?? 50, // Default capacity
                'type' => in_array($venueType, $this->venueTypes) ? $venueType : 'other',
            ]
        );

        return $venue;
    }

    protected function determineVenueType($name)
    {
        $prefix = strtoupper(substr($name, 0, 2));
        $venueTypes = [
            'lecture_theatre' => ['LT'],
            'computer_lab' => ['CL'],
            'physics_lab' => ['PH'],
            'chemistry_lab' => ['CH'],
            'medical_lab' => ['ML'],
            'nursing_demo' => ['NU'],
            'seminar_room' => ['SR'], // Assuming Bishop_H is a seminar room
        ];

        foreach ($venueTypes as $type => $prefixes) {
            if (in_array($prefix, $prefixes)) {
                return $type;
            }
        }

        return 'other';
    }

    protected function generateLongform($name)
    {
        $prefixMap = [
            'LT' => 'Lecture Theatre',
            'CL' => 'Computer Lab',
            'BI' => 'Bishop Hall',
        ];

        $prefix = strtoupper(substr($name, 0, 2));
        $suffix = substr($name, 2);

        return isset($prefixMap[$prefix]) ? "{$prefixMap[$prefix]} {$suffix}" : $name;
    }
}