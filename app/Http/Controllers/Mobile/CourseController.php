<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller {
    /**
    * Display a listing of the resource.
    */

    public function index() {
        //
    }

    public function getAllCourses( Request $request ) {
        $facultyId = $request->query( 'faculty_id' );

        // Validate query parameters
        if ( !$facultyId ) {
            return response()->json( [
                'success' => false,
                'error' => 'Missing faculty_id'
            ], 400 );
        }

        // Fetch courses with lecturers for the specified faculty
        $courses = Course::with( 'lecturer' )
        ->join( 'course_faculty', 'courses.id', '=', 'course_faculty.course_id' )
        ->where( 'course_faculty.faculty_id', $facultyId )
        ->get();

        return response()->json( [
            'success' => true,
            'data' => $courses
        ], 200 );
    }

    /**
    * Store a newly created resource in storage.
    */

    public function store( Request $request ) {
        //
    }

    /**
    * Display the specified resource.
    */

    public function show( string $id ) {
        //
    }

    /**
    * Update the specified resource in storage.
    */

    public function update( Request $request, string $id ) {
        //
    }

    /**
    * Remove the specified resource from storage.
    */

    public function destroy( string $id ) {
        //
    }
}
