<?php

namespace App\Imports;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class UserImport implements ToModel, WithHeadingRow
{
    protected $validRoles = [
        'User',
        'Admin',
        'Administrator',
        'Dean Of Students',
        'Director',
        'Timetable Officer',
        'Lecturer'
    ];

    public function model(array $row)
    {
        if (empty($row['name']) || empty($row['email'])) {
            Log::warning('Skipping row due to missing name or email', $row);
            return null;
        }

        $roleName = trim($row['role'] ?? 'Timetable Officer');
        if (!in_array($roleName, $this->validRoles)) {
            Log::warning("Invalid role '{$roleName}' for user {$row['email']}, defaulting to Timetable Officer", $row);
            $roleName = 'Timetable Officer';
        }

        try {
            $user = User::updateOrCreate(
                ['email' => $row['email']],
                [
                    'name' => $row['name'],
                    'phone' => $row['phone'] ?? null,
                    'gender' => in_array($row['gender'] ?? null, ['Male', 'Female', 'Other']) ? $row['gender'] : null,
                    'password' => Hash::make('12345678'), // Default password
                    // For random password: 'password' => Hash::make(Str::random(8)),
                    'status' => 'active',
                ]
            );

            $role = Role::where('name', $roleName)->firstOrFail();
            $user->syncRoles([$role]);

            Log::info("Imported user {$row['email']} with role {$roleName}");
            return $user;
        } catch (\Exception $e) {
            Log::error("Failed to import user {$row['email']}: " . $e->getMessage(), $row);
            return null;
        }
    }
}