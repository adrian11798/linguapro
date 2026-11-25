<?php

namespace App\Services;

use App\Models\Course;
use App\Models\LiveClass;

/**
 * Regla de horarios de las clases en vivo.
 *
 * Cada clase se considera de DURATION_MINUTES minutos. No se puede programar
 * una clase que se empalme con otra cuando coincide:
 *   - el mismo curso (materia),
 *   - el mismo profesor, o
 *   - algún alumno inscrito en ambos cursos.
 */
class LiveClassScheduler
{
    public const DURATION_MINUTES = 60;

    /** "16:30" o "16:30:00" -> minutos desde la medianoche. */
    public static function minutes(string $time): int
    {
        $parts = array_map('intval', explode(':', $time));

        return ($parts[0] * 60) + ($parts[1] ?? 0);
    }

    /** ¿Dos horas de inicio se empalman? */
    public static function overlaps(string $timeA, string $timeB): bool
    {
        return abs(self::minutes($timeA) - self::minutes($timeB)) < self::DURATION_MINUTES;
    }

    /**
     * Busca una clase que choque con la que se quiere programar.
     *
     * @return array{class: LiveClass, reason: string}|null
     */
    public static function findConflict(Course $course, string $date, string $time, ?int $ignoreId = null): ?array
    {
        $studentIds = $course->enrollments()->pluck('user_id')->all();

        $sameDay = LiveClass::with('course')
            ->whereDate('date', $date)
            ->where('status', '!=', 'cancelled')
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->get();

        foreach ($sameDay as $other) {
            if (! self::overlaps($time, $other->time)) {
                continue;
            }

            if ($other->course_id === $course->id) {
                return ['class' => $other, 'reason' => 'ese curso ya tiene una clase a esa hora'];
            }

            if ($course->teacher_id && $other->course && $other->course->teacher_id === $course->teacher_id) {
                return ['class' => $other, 'reason' => 'el profesor ya tiene una clase a esa hora'];
            }

            if ($studentIds && $other->course
                && $other->course->enrollments()->whereIn('user_id', $studentIds)->exists()) {
                return ['class' => $other, 'reason' => 'hay alumnos inscritos en ambos cursos'];
            }
        }

        return null;
    }

    /** Respuesta 422 lista para devolver cuando hay empalme. */
    public static function conflictResponse(array $conflict)
    {
        $other = $conflict['class'];
        $hour = substr($other->time, 0, 5);
        $message = sprintf(
            'No se puede programar a esa hora: se empalma con «%s» (%s, %s h) — %s. Cada clase se considera de %d minutos; elige otro horario.',
            $other->title,
            $other->course?->name ?? 'otro curso',
            $hour,
            $conflict['reason'],
            self::DURATION_MINUTES
        );

        return response()->json(['message' => $message, 'errors' => ['time' => [$message]]], 422);
    }
}
