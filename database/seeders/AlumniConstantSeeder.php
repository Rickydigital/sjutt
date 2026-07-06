<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\EmploymentSector;
use App\Models\EmploymentState;
use App\Models\EmploymentYear;
use App\Models\GraduationYear;
use App\Models\SocialPlatform;
use Illuminate\Database\Seeder;

class AlumniConstantSeeder extends Seeder
{
    public function run(): void
    {
        foreach (range((int) date('Y'), 1960) as $year) {
            GraduationYear::firstOrCreate(['year' => $year]);
            EmploymentYear::firstOrCreate(['year' => $year]);
        }

        foreach ([
            'Employed',
            'Self Employed',
            'Unemployed',
            'Student',
            'Retired',
            'Not Stated',
        ] as $name) {
            EmploymentState::firstOrCreate(['name' => $name]);
        }

        foreach ([
            'Government',
            'Private',
            'NGO',
            'Faith Based Organization',
            'International Organization',
            'Self Employment',
            'Education',
            'Health',
            'Agriculture',
            'Business',
            'Other',
            'NIL',
        ] as $name) {
            EmploymentSector::firstOrCreate(['name' => $name]);
        }

        foreach ([
            ['name' => 'Tanzania', 'code' => 'TZ'],
            ['name' => 'Kenya', 'code' => 'KE'],
            ['name' => 'Uganda', 'code' => 'UG'],
            ['name' => 'Rwanda', 'code' => 'RW'],
            ['name' => 'Burundi', 'code' => 'BI'],
            ['name' => 'South Sudan', 'code' => 'SS'],
            ['name' => 'Democratic Republic of the Congo', 'code' => 'CD'],
            ['name' => 'Zambia', 'code' => 'ZM'],
            ['name' => 'Malawi', 'code' => 'MW'],
            ['name' => 'Mozambique', 'code' => 'MZ'],
            ['name' => 'South Africa', 'code' => 'ZA'],
            ['name' => 'United States', 'code' => 'US'],
            ['name' => 'United Kingdom', 'code' => 'GB'],
            ['name' => 'Canada', 'code' => 'CA'],
            ['name' => 'Other', 'code' => 'OTHER'],
        ] as $country) {
            Country::firstOrCreate(['code' => $country['code']], ['name' => $country['name']]);
        }

        foreach (['WhatsApp', 'Telegram', 'Facebook', 'LinkedIn', 'Instagram', 'X/Twitter'] as $name) {
            SocialPlatform::firstOrCreate(['name' => $name]);
        }
    }
}
