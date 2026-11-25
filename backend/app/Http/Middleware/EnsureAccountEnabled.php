<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea el contenido (cursos, lecciones, tareas, clases en vivo) a los
 * alumnos que el administrador deshabilitó. Siguen pudiendo iniciar sesión
 * para ver el aviso de pago pendiente.
 */
class EnsureAccountEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'student' && ! $user->is_enabled) {
            return response()->json([
                'message' => 'Favor de ponerse al corriente en sus pagos.',
                'code' => 'account_disabled',
            ], 403);
        }

        return $next($request);
    }
}
