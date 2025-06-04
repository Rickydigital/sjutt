<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        // Define permissions
        $permissions = [
            // Timetable
            'view timetables',
            'create timetables',
            'edit timetables',
            'delete timetables',
            'export timetables',
            'import timetables',
            // Examination Timetable
            'view examination timetables',
            'create examination timetables',
            'edit examination timetables',
            'delete examination timetables',
            'export examination timetables',
            'import examination timetables',
            // Calendar
            'view calendar',
            'create calendar',
            'edit calendar',
            'delete calendar',
            'export calendar',
            'import calendar',
            // News
            'view news',
            'create news',
            'edit news',
            'delete news',
            // Events
            'view events',
            'create events',
            'edit events',
            'delete events',
            // Gallery
            'view gallery',
            'create gallery',
            'edit gallery',
            'delete gallery',
            // FAQs
            'view faqs',
            'create faqs',
            'edit faqs',
            'delete faqs',
            // About
            'view about',
            'create about',
            'edit about',
            'delete about',
            // Fee Structures
            'view fee structures',
            'create fee structures',
            'edit fee structures',
            'delete fee structures',
            'export fee structures',
            'import fee structures',
            // Buildings
            'view buildings',
            'create buildings',
            'edit buildings',
            'delete buildings',
            // Courses
            'view courses',
            'create courses',
            'edit courses',
            'delete courses',
            'export courses',
            'import courses',
            // Faculties
            'view faculties',
            'create faculties',
            'edit faculties',
            'delete faculties',
            'export faculties',
            'import faculties',
            // Programs
            'view programs',
            'create programs',
            'edit programs',
            'delete programs',
            'export programs',
            'import programs',
            // Years
            'view years',
            'create years',
            'edit years',
            'delete years',
            // Venues
            'view venues',
            'create venues',
            'edit venues',
            'delete venues',
            'export venues',
            'import venues',
            // Users
            'view users',
            'create users',
            'edit users',
            'delete users',
            'activate users',
            'deactivate users',
            // Profile
            'view own profile',
            'edit own profile',
            'delete own profile',
            // Suggestions
            'view suggestions',
            'reply suggestions',
            // Queries
            'view queries',
            'respond queries',
            // Students
            'view students',
            'create students',
            'edit students',
            'delete students',
            'export students',
            'import students',
            // Attendance
            'view attendance',
            'create attendance',
            'edit attendance',
            'delete attendance',
            'export attendance',
            'import attendance',
            // Talents
            'view talents',
            'create talents',
            'edit talents',
            'delete talents',
            // Enrolled Courses (Future)
            'view enrolled courses',
            'enroll courses',
            'drop enrolled courses',
            // IPT (Future)
            'view ipt',
            'create ipt',
            'edit ipt',
            'delete ipt',
            'submit ipt reports',
            'view ipt evaluations',
            // Examinations (Future)
            'view examinations',
            'create examinations',
            'edit examinations',
            'delete examinations',
            'submit examination results',
            'view examination results',
            // Library (Future)
            'view library resources',
            'borrow library resources',
            'return library resources',
            'manage library resources',
            // Finance (Future)
            'view payments',
            'process payments',
            'view financial reports',
            // Alumni (Future)
            'view alumni',
            'manage alumni',
            // Research (Future)
            'view research',
            'submit research',
            'manage research',
            // Notifications (Future)
            'view notifications',
            'send notifications'
        ];

        
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        
        $roles = [
            'User',
            'Admin',
            'Administrator',
            'Dean Of Students',
            'Director',
            'Timetable Officer',
            'Lecturer',
            'Student',
            'Registrar',
            'IT Admin'
        ];

        // Create roles and assign permissions
        foreach ($roles as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            switch ($roleName) {
                case 'Admin':
                    $role->syncPermissions($permissions);
                    break;
                case 'Administrator':
                    $role->syncPermissions([
                        'view faculties', 'create faculties', 'edit faculties', 'delete faculties', 'export faculties', 'import faculties',
                        'view courses', 'create courses', 'edit courses', 'delete courses', 'export courses', 'import courses',
                        'view programs', 'create programs', 'edit programs', 'delete programs', 'export programs', 'import programs',
                        'view timetables', 'view examination timetables', 'view calendar', 'view news', 'view events', 'view gallery',
                        'view faqs', 'view about', 'view fee structures', 'view buildings', 'view years', 'view venues',
                        'view students', 'view attendance', 'view suggestions', 'view queries', 'view users',
                        'view own profile', 'edit own profile',
                        'export faculties', 'export courses', 'export programs', 'export students', 'export attendance'
                    ]);
                    break;
                case 'Dean Of Students':
                    $role->syncPermissions([
                        'view students', 'create students', 'edit students', 'delete students', 'export students', 'import students',
                        'view talents', 'create talents', 'edit talents', 'delete talents',
                        'view attendance', 'create attendance', 'edit attendance', 'export attendance', 'import attendance',
                        'view suggestions', 'reply suggestions',
                        'view calendar', 'view news', 'view events', 'view gallery', 'view faqs', 'view about', 'view fee structures',
                        'view own profile', 'edit own profile'
                    ]);
                    break;
                case 'Director':
                    $role->syncPermissions([
                        'view timetables', 'view examination timetables',
                        'view calendar', 'create calendar', 'edit calendar', 'delete calendar', 'export calendar',
                        'view news', 'create news', 'edit news', 'delete news',
                        'view events', 'create events', 'edit events', 'delete events',
                        'view gallery', 'create gallery', 'edit gallery', 'delete gallery',
                        'view faqs', 'view about', 'view fee structures',
                        'view students', 'export students', 'view attendance', 'export attendance',
                        'view suggestions', 'view queries', 'view users',
                        'view own profile', 'edit own profile'
                    ]);
                    break;
                case 'Timetable Officer':
                    $role->syncPermissions([
                        'view timetables', 'create timetables', 'edit timetables', 'delete timetables', 'export timetables', 'import timetables',
                        'view examination timetables', 'create examination timetables', 'edit examination timetables', 'delete examination timetables', 'export examination timetables', 'import examination timetables',
                        'view faculties', 'export faculties', 'view courses', 'export courses', 'view programs', 'export programs',
                        'view calendar', 'view news', 'view events', 'view gallery', 'view faqs', 'view about', 'view fee structures',
                        'view students', 'view attendance', 'view own profile', 'edit own profile'
                    ]);
                    break;
                case 'Lecturer':
                    $role->syncPermissions([
                        'view courses',
                        'view attendance', 'create attendance', 'edit attendance',
                        'view timetables', 'view examination timetables', 'view calendar', 'view news', 'view events', 'view gallery',
                        'view faqs', 'view about', 'view fee structures', 'view students',
                        'view own profile', 'edit own profile',
                        'view enrolled courses', 'view examinations', 'submit examination results'
                    ]);
                    break;
                case 'User':
                    $role->syncPermissions([
                        'view calendar', 'view news', 'view events', 'view gallery', 'view faqs', 'view about', 'view fee structures',
                        'view own profile', 'edit own profile'
                    ]);
                    break;
                case 'Student':
                    $role->syncPermissions([
                        'view timetables', 'view examination timetables', 'view calendar', 'view news', 'view events', 'view gallery',
                        'view faqs', 'view about', 'view fee structures',
                        'view talents', 'create talents',
                        'view attendance', 'view own profile', 'edit own profile',
                        'view enrolled courses', 'enroll courses', 'drop enrolled courses',
                        'view ipt', 'submit ipt reports', 'view examinations', 'view examination results'
                    ]);
                    break;
                case 'Registrar':
                    $role->syncPermissions([
                        'view students', 'create students', 'edit students', 'delete students', 'export students', 'import students',
                        'view users', 'create users', 'edit users', 'delete users', 'activate users', 'deactivate users',
                        'view faculties', 'view courses', 'view programs', 'view years', 'view venues',
                        'view timetables', 'view examination timetables', 'view calendar', 'view attendance', 'export attendance',
                        'view own profile', 'edit own profile',
                        'view enrolled courses', 'view examinations', 'view examination results'
                    ]);
                    break;
                case 'IT Admin':
                    $role->syncPermissions([
                        'view users', 'create users', 'edit users', 'delete users', 'activate users', 'deactivate users',
                        'view own profile', 'edit own profile'
                    ]);
                    break;
            }
        }
    }
}