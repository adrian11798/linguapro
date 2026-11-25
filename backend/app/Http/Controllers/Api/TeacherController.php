<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Progress;
use App\Models\LiveClass;
use App\Models\Submission;
use App\Models\User;
use App\Services\LiveClassScheduler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class TeacherController extends Controller
{
    /** Cursos que imparte el profesor autenticado (para elegir dónde agregar video). */
    public function courses(Request $request)
    {
        return response()->json(
            $request->user()->coursesTaught()->latest()->get()
        );
    }

    /** Lecciones de uno de los cursos del profesor autenticado. */
    public function lessons(Request $request, Course $course)
    {
        $this->authorizeCourse($request, $course);

        return response()->json($course->lessons()->orderBy('order')->get());
    }

    /**
     * El profesor agrega una lección con el enlace de video
     * (YouTube, Vimeo o Google Drive) a uno de sus propios cursos.
     */
    public function storeLesson(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => ['required', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'video_url' => ['nullable', 'string', 'max:2048'],
            'material_url' => ['nullable', 'string', 'max:2048'],
            'order' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $validator->errors()], 422);
        }

        $course = Course::findOrFail($request->course_id);
        $this->authorizeCourse($request, $course);

        $lesson = Lesson::create($validator->validated());

        return response()->json($lesson, 201);
    }

    public function updateLesson(Request $request, Lesson $lesson)
    {
        $this->authorizeCourse($request, $lesson->course);

        $validator = Validator::make($request->all(), [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'video_url' => ['nullable', 'string', 'max:2048'],
            'material_url' => ['nullable', 'string', 'max:2048'],
            'order' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $validator->errors()], 422);
        }

        $lesson->update($validator->validated());

        return response()->json($lesson);
    }

    public function destroyLesson(Request $request, Lesson $lesson)
    {
        $this->authorizeCourse($request, $lesson->course);

        $lesson->delete();

        return response()->json(['message' => 'Lección eliminada.']);
    }

    /** Clases en vivo programadas en los cursos del profesor autenticado. */
    public function liveClasses(Request $request)
    {
        $courseIds = $request->user()->coursesTaught()->pluck('id');

        return response()->json(
            \App\Models\LiveClass::whereIn('course_id', $courseIds)
                ->with('course:id,name')
                ->orderBy('date')
                ->orderBy('time')
                ->get()
        );
    }

    /** El profesor programa una clase en vivo en uno de sus cursos. */
    public function storeLiveClass(Request $request)
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
        $this->authorizeCourse($request, $course);

        if ($conflict = LiveClassScheduler::findConflict($course, $request->date, $request->time)) {
            return LiveClassScheduler::conflictResponse($conflict);
        }

        $liveClass = LiveClass::create($validator->validated() + ['status' => 'scheduled']);

        return response()->json($liveClass->load('course:id,name'), 201);
    }

    public function destroyLiveClass(Request $request, LiveClass $liveClass)
    {
        $this->authorizeCourse($request, $liveClass->course);

        $liveClass->delete();

        return response()->json(['message' => 'Clase en vivo eliminada.']);
    }

    /** Busca alumnos (por nombre o correo) para inscribirlos en un curso. */
    public function searchStudents(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';

        return response()->json(
            User::where('role', 'student')
                ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like))
                ->orderBy('name')
                ->limit(8)
                ->get(['id', 'name', 'email'])
        );
    }

    /** El profesor inscribe a un alumno en uno de sus cursos. */
    public function enrollStudent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => ['required', 'exists:courses,id'],
            'user_id' => ['required', 'exists:users,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $validator->errors()], 422);
        }

        $course = Course::findOrFail($request->course_id);
        $this->authorizeCourse($request, $course);

        $student = User::where('id', $request->user_id)->where('role', 'student')->first();
        if (! $student) {
            return response()->json(['message' => 'Solo se pueden inscribir alumnos.'], 422);
        }

        $enrollment = Enrollment::firstOrCreate(
            ['user_id' => $student->id, 'course_id' => $course->id],
            ['enrolled_at' => now(), 'status' => 'active']
        );

        return response()->json([
            'message' => $enrollment->wasRecentlyCreated
                ? "{$student->name} quedó inscrito en {$course->name}."
                : "{$student->name} ya estaba inscrito en {$course->name}.",
            'already_enrolled' => ! $enrollment->wasRecentlyCreated,
        ], $enrollment->wasRecentlyCreated ? 201 : 200);
    }

    /** Avance de un alumno en uno de los cursos del profesor: lecciones, tareas y calificaciones. */
    public function studentDetail(Request $request, Course $course, User $user)
    {
        $this->authorizeCourse($request, $course);

        $enrollment = Enrollment::where('course_id', $course->id)->where('user_id', $user->id)->firstOrFail();

        $lessons = $course->lessons()->orderBy('order')->get();
        $done = Progress::where('user_id', $user->id)
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->where('completed', true)
            ->get()
            ->keyBy('lesson_id');

        $assignments = $course->assignments()->latest()->get()->map(function (Assignment $a) use ($user) {
            $s = $a->submissions()->where('user_id', $user->id)->first();

            return [
                'id' => $a->id,
                'title' => $a->title,
                'due_date' => $a->due_date,
                'submitted_at' => $s?->submitted_at,
                'file_url' => $s ? Storage::url($s->file_path) : null,
                'grade' => $s?->grade,
                'feedback' => $s?->feedback,
            ];
        })->values();

        $grades = $assignments->pluck('grade')->filter(fn ($g) => $g !== null);
        $completed = $lessons->filter(fn ($l) => $done->has($l->id))->count();

        return response()->json([
            'student' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
            'course' => ['id' => $course->id, 'name' => $course->name],
            'enrolled_at' => $enrollment->enrolled_at,
            'progress' => $lessons->count() ? (int) round(($completed / $lessons->count()) * 100) : 0,
            'lessons' => $lessons->map(fn ($l) => [
                'id' => $l->id,
                'title' => $l->title,
                'completed' => $done->has($l->id),
            ])->values(),
            'assignments' => $assignments,
            'average_grade' => $grades->count() ? (int) round($grades->avg()) : null,
        ]);
    }

    /** Verifica que el curso pertenezca al profesor autenticado. */
    private function authorizeCourse(Request $request, Course $course): void
    {
        abort_unless($course->teacher_id === $request->user()->id, 403, 'No puedes gestionar un curso que no impartes.');
    }

    /**
     * Alumnos inscritos en los cursos del profesor autenticado, con su
     * porcentaje de avance (ver mockup "Panel del Profesor").
     */
    public function students(Request $request)
    {
        $courses = $request->user()
            ->coursesTaught()
            ->with(['enrollments.user', 'lessons'])
            ->get();

        $rows = [];

        foreach ($courses as $course) {
            $totalLessons = $course->lessons->count();

            foreach ($course->enrollments as $enrollment) {
                $student = $enrollment->user;

                $completed = $student->progress()
                    ->whereIn('lesson_id', $course->lessons->pluck('id'))
                    ->where('completed', true)
                    ->count();

                $rows[] = [
                    'student_id' => $student->id,
                    'student_name' => $student->name,
                    'course_id' => $course->id,
                    'course_name' => $course->name,
                    'progress' => $totalLessons > 0 ? (int) round(($completed / $totalLessons) * 100) : 0,
                ];
            }
        }

        return response()->json($rows);
    }

    /** Tareas de uno de los cursos del profesor autenticado. */
    public function assignments(Request $request, Course $course)
    {
        $this->authorizeCourse($request, $course);

        $assignments = $course->assignments()
            ->withCount('submissions')
            ->latest()
            ->get()
            ->map(fn (Assignment $a) => [
                'id' => $a->id,
                'title' => $a->title,
                'description' => $a->description,
                'file_url' => $a->file_path ? Storage::url($a->file_path) : null,
                'due_date' => $a->due_date,
                'submissions_count' => $a->submissions_count,
            ]);

        return response()->json($assignments);
    }

    /**
     * El profesor crea una tarea en uno de sus cursos, con un archivo
     * de material opcional (consigna, lectura, plantilla, etc.).
     */
    public function storeAssignment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_id' => ['required', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'due_date' => ['nullable', 'date'],
            'file' => ['nullable', 'file', 'max:10240'], // 10 MB
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $validator->errors()], 422);
        }

        $course = Course::findOrFail($request->course_id);
        $this->authorizeCourse($request, $course);

        $path = $request->hasFile('file')
            ? $request->file('file')->store('assignments', 'public')
            : null;

        $assignment = Assignment::create([
            'course_id' => $course->id,
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'file_path' => $path,
        ]);

        return response()->json($assignment, 201);
    }

    public function destroyAssignment(Request $request, Assignment $assignment)
    {
        $this->authorizeCourse($request, $assignment->course);

        $assignment->delete();

        return response()->json(['message' => 'Tarea eliminada.']);
    }

    /** Entregas de los estudiantes para una tarea, para que el profesor las califique. */
    public function submissions(Request $request, Assignment $assignment)
    {
        $this->authorizeCourse($request, $assignment->course);

        $submissions = $assignment->submissions()
            ->with('student:id,name,email')
            ->latest('submitted_at')
            ->get()
            ->map(fn (Submission $s) => [
                'id' => $s->id,
                'student_name' => $s->student->name,
                'student_email' => $s->student->email,
                'file_url' => Storage::url($s->file_path),
                'submitted_at' => $s->submitted_at,
                'grade' => $s->grade,
                'feedback' => $s->feedback,
                'graded_at' => $s->graded_at,
            ]);

        return response()->json($submissions);
    }

    /** El profesor califica la entrega de un estudiante. */
    public function gradeSubmission(Request $request, Submission $submission)
    {
        $this->authorizeCourse($request, $submission->assignment->course);

        $validator = Validator::make($request->all(), [
            'grade' => ['required', 'integer', 'min:0', 'max:100'],
            'feedback' => ['nullable', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $validator->errors()], 422);
        }

        $submission->update([
            'grade' => $request->grade,
            'feedback' => $request->feedback,
            'graded_at' => now(),
        ]);

        return response()->json($submission);
    }
}
