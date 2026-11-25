<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AssignmentController extends Controller
{
    /**
     * Tareas de un curso, con la entrega del estudiante autenticado
     * (si ya entregó), para que vea su calificación y comentarios.
     */
    public function index(Request $request, Course $course)
    {
        $userId = $request->user()->id;

        $assignments = $course->assignments()->latest()->get()->map(function (Assignment $assignment) use ($userId) {
            $submission = $assignment->submissions()->where('user_id', $userId)->first();

            return [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'file_url' => $assignment->file_path ? Storage::url($assignment->file_path) : null,
                'due_date' => $assignment->due_date,
                'submission' => $submission ? [
                    'id' => $submission->id,
                    'file_url' => Storage::url($submission->file_path),
                    'submitted_at' => $submission->submitted_at,
                    'grade' => $submission->grade,
                    'feedback' => $submission->feedback,
                    'graded_at' => $submission->graded_at,
                ] : null,
            ];
        });

        return response()->json($assignments);
    }

    /**
     * El estudiante entrega (o vuelve a entregar) su archivo para una tarea.
     * Al resubir, se reemplaza el archivo anterior y se borra la
     * calificación previa, ya que corresponde a la versión anterior.
     */
    public function submit(Request $request, Assignment $assignment)
    {
        $validator = Validator::make($request->all(), [
            'file' => ['required', 'file', 'max:10240'], // 10 MB
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $validator->errors()], 422);
        }

        $path = $request->file('file')->store('submissions', 'public');

        $submission = Submission::updateOrCreate(
            ['assignment_id' => $assignment->id, 'user_id' => $request->user()->id],
            [
                'file_path' => $path,
                'submitted_at' => now(),
                'grade' => null,
                'feedback' => null,
                'graded_at' => null,
            ]
        );

        return response()->json([
            'id' => $submission->id,
            'file_url' => Storage::url($submission->file_path),
            'submitted_at' => $submission->submitted_at,
            'grade' => $submission->grade,
            'feedback' => $submission->feedback,
        ], 201);
    }
}
