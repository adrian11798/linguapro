<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LiveClass;
use App\Services\LiveClassScheduler;
use Illuminate\Http\Request;

class LiveClassController extends Controller
{
    /**
     * RF-10: el estudiante ve las clases en vivo de los cursos en los que
     * está inscrito, ordenadas por horario y sin dos clases a la misma hora.
     */
    public function index(Request $request)
    {
        $courseIds = $request->user()->enrollments()->pluck('course_id');

        // Se trae desde ayer para no depender de la zona horaria del servidor;
        // el frontend descarta lo que ya pasó según la hora local del alumno.
        $classes = LiveClass::with('course:id,name')
            ->whereIn('course_id', $courseIds)
            ->where('status', '!=', 'cancelled')
            ->whereDate('date', '>=', now()->subDay()->toDateString())
            ->orderBy('date')
            ->orderBy('time')
            ->orderBy('id')
            ->get();

        // Una sola clase por franja horaria: si dos se empalman, se muestra
        // la primera (por horario y, a igualdad, la que se creó antes).
        $kept = collect();
        foreach ($classes as $class) {
            $clash = $kept->first(fn ($k) => $k->date->toDateString() === $class->date->toDateString()
                && LiveClassScheduler::overlaps($k->time, $class->time));

            if (! $clash) {
                $kept->push($class);
            }
        }

        return response()->json($kept->values());
    }
}
