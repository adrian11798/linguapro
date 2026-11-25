<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\LiveClass;
use App\Services\LiveClassScheduler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LiveClassController extends Controller
{
    /** Todas las clases en vivo (pasadas y futuras) para el panel administrativo. */
    public function index()
    {
        return response()->json(
            LiveClass::with('course:id,name,teacher_id')
                ->orderBy('date')
                ->orderBy('time')
                ->get()
        );
    }

    /** RF-11: el administrador debe poder registrar y programar clases en vivo. */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => ['required', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'time' => ['required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'meeting_url' => ['required', 'string', 'max:2048'],
        ], [
            'meeting_url.required' => 'Falta el enlace de la videoconferencia.',
            'time.regex' => 'La hora no tiene un formato válido.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $course = Course::findOrFail($request->course_id);

        if ($conflict = LiveClassScheduler::findConflict($course, $request->date, $request->time)) {
            return LiveClassScheduler::conflictResponse($conflict);
        }

        $liveClass = LiveClass::create($validator->validated() + ['status' => 'scheduled']);

        return response()->json($liveClass->load('course:id,name,teacher_id'), 201);
    }

    public function update(Request $request, LiveClass $liveClass)
    {
        $validator = Validator::make($request->all(), [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'date' => ['sometimes', 'required', 'date'],
            'time' => ['sometimes', 'required', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'meeting_url' => ['sometimes', 'required', 'string', 'max:2048'],
            'status' => ['nullable', 'in:scheduled,completed,cancelled'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $date = $data['date'] ?? $liveClass->date->toDateString();
        $time = $data['time'] ?? $liveClass->time;
        $status = $data['status'] ?? $liveClass->status;

        if ($status !== 'cancelled'
            && ($conflict = LiveClassScheduler::findConflict($liveClass->course, $date, $time, $liveClass->id))) {
            return LiveClassScheduler::conflictResponse($conflict);
        }

        $liveClass->update($data);

        return response()->json($liveClass);
    }

    public function destroy(LiveClass $liveClass)
    {
        $liveClass->delete();

        return response()->json(['message' => 'Clase en vivo eliminada.']);
    }
}
