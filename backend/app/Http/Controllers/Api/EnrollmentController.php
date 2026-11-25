<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EnrollmentController extends Controller
{
    /** Cursos en los que el estudiante autenticado está inscrito. */
    public function myEnrollments(Request $request)
    {
        return response()->json(
            $request->user()->enrollments()->get()
        );
    }

    /** RF-07: el estudiante debe poder inscribirse a un curso. */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => ['required', 'exists:courses,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $validator->errors()], 422);
        }

        $course = Course::findOrFail($request->course_id);

        // Regla de integridad (5.4): sin inscripciones duplicadas.
        $enrollment = Enrollment::firstOrCreate(
            ['user_id' => $request->user()->id, 'course_id' => $course->id],
            ['enrolled_at' => now(), 'status' => 'active']
        );

        return response()->json($enrollment, 201);
    }
}
