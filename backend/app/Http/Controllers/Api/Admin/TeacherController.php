<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    /** Maestros, con los cursos que imparten y cuántos alumnos tienen. */
    public function index()
    {
        $teachers = User::where('role', 'teacher')
            ->with('coursesTaught:id,name,teacher_id')
            ->orderBy('name')
            ->get()
            ->map(function (User $teacher) {
                $courseIds = $teacher->coursesTaught->pluck('id');

                return [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'email' => $teacher->email,
                    'courses' => $teacher->coursesTaught->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
                    'students_count' => Enrollment::whereIn('course_id', $courseIds)->distinct()->count('user_id'),
                ];
            });

        return response()->json($teachers);
    }

    /** Da de alta una cuenta de maestro. */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ], [
            'email.unique' => 'Ya existe una cuenta con ese correo.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $teacher = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password, // se cifra automáticamente (cast "hashed")
            'role' => 'teacher',
            'is_enabled' => true,
        ]);

        return response()->json([
            'id' => $teacher->id,
            'name' => $teacher->name,
            'email' => $teacher->email,
            'courses' => [],
            'students_count' => 0,
        ], 201);
    }
}
