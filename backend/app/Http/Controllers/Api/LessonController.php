<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;

class LessonController extends Controller
{
    /**
     * RF-08: el estudiante debe poder acceder a las lecciones de sus
     * cursos. (La verificación de inscripción se puede reforzar aquí
     * más adelante comprobando enrollments; se deja abierta para el MVP,
     * ver RNF-06 sobre escalabilidad).
     */
    public function index(Course $course)
    {
        return response()->json(
            $course->lessons()->orderBy('order')->get()
        );
    }
}
