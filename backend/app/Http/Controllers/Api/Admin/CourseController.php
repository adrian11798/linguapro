<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CourseController extends Controller
{
    /** RF-04: el administrador debe poder crear, consultar, editar y gestionar cursos. */
    public function index()
    {
        return response()->json(Course::with('teacher:id,name')->latest()->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'language' => ['required', 'string', 'max:100'],
            'level' => ['required', 'string', 'max:50'],
            'image' => ['nullable', 'string', 'max:2048'],
            'status' => ['nullable', 'in:draft,published'],
            'teacher_id' => ['nullable', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $validator->errors()], 422);
        }

        $course = Course::create($validator->validated() + ['status' => $request->input('status', 'draft')]);

        return response()->json($course->load('teacher:id,name'), 201);
    }

    public function update(Request $request, Course $course)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'language' => ['sometimes', 'required', 'string', 'max:100'],
            'level' => ['sometimes', 'required', 'string', 'max:50'],
            'image' => ['nullable', 'string', 'max:2048'],
            'status' => ['nullable', 'in:draft,published'],
            'teacher_id' => ['nullable', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $validator->errors()], 422);
        }

        $course->update($validator->validated());

        return response()->json($course->load('teacher:id,name'));
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return response()->json(['message' => 'Curso eliminado.']);
    }
}
