<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Bloqueia o acesso às rotas do sistema enquanto o usuário autenticado
     * não definir uma nova senha (primeiro acesso ou senha resetada pelo admin).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->mustChangePassword()) {
            // Rotas permitidas mesmo com a troca de senha pendente
            if ($request->routeIs(['password.force', 'password.force.update', 'logout'])) {
                return $next($request);
            }

            return redirect()
                ->route('password.force')
                ->with('warning', 'Por segurança, defina uma nova senha antes de continuar.');
        }

        return $next($request);
    }
}
