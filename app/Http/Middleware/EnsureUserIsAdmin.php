<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Restringe o acesso às rotas administrativas (gestão de usuários).
     * Apenas usuários com perfil "admin" podem prosseguir.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! optional($request->user())->isAdmin()) {
            abort(403, 'Acesso restrito aos administradores do SCEA.');
        }

        return $next($request);
    }
}
