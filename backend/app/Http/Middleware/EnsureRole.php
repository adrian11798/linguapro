<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RNF-01 / RF-03: protege rutas privadas según el rol del usuario
 * autenticado (student, teacher, admin).
 *
 * Uso en routes/api.php:
 *   Route::middleware(['auth:sanctum', 'role:admin'])->group(...);
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, $roles, true)) {
            return response()->json([
                'message' => 'No tienes permiso para acceder a este recurso.',
            ], 403);
        }

        return $next($request);
    }
}
