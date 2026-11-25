<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    /** Alumnos con sus cursos y si su acceso está habilitado. */
    public function index()
    {
        $students = User::where('role', 'student')
            ->with('enrollments.course:id,name')
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'is_enabled' => (bool) $u->is_enabled,
                'courses' => $u->enrollments->map(fn ($e) => $e->course?->name)->filter()->values(),
            ]);

        return response()->json($students);
    }

    /**
     * Habilita o deshabilita a un alumno. Al deshabilitarlo deja de ver los
     * cursos y se le muestra el aviso de ponerse al corriente en sus pagos.
     */
    public function updateStatus(Request $request, User $user)
    {
        abort_unless($user->role === 'student', 422, 'Solo se puede habilitar o deshabilitar a alumnos.');

        $validator = Validator::make($request->all(), [
            'is_enabled' => ['required', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Datos inválidos.', 'errors' => $validator->errors()], 422);
        }

        $user->is_enabled = $request->boolean('is_enabled');
        $user->save();

        return response()->json(['id' => $user->id, 'is_enabled' => $user->is_enabled]);
    }
}
