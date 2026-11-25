<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;

class CourseController extends Controller
{
    /** RF-06: el estudiante debe poder consultar los cursos disponibles. */
    public function index()
    {
        return response()->json(
            Course::where('status', 'published')->latest()->get()
        );
    }

    public function show(Course $course)
    {
        return response()->json($course);
    }
}
