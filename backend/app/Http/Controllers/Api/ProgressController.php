<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Progress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProgressController extends Controller
{
    /** Progreso del estudiante autenticado. */
    public function myProgress(Request $request)
    {
        return response()->json(
            $request->user()->progress()->get()
        );
    }

    /**
     * RF-09: el sistema debe registrar las lecciones completadas y el
     * avance del estudiante.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lesson_id' => ['required', 'exists:lessons,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $validator->errors()], 422);
        }

        $progress = Progress::updateOrCreate(
            ['user_id' => $request->user()->id, 'lesson_id' => $request->lesson_id],
            ['completed' => true, 'completed_at' => now()]
        );

        return response()->json($progress, 201);
    }
}
