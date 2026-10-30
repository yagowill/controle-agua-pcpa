<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Exibe a tela de login institucional do SCEA/PCPA (acesso por nome de usuário).
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
        ]);
    }

    /**
     * Processa o login e redireciona o usuário conforme a política de senha.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Primeiro acesso (ou senha resetada pelo admin): obriga a troca antes de liberar o sistema
        if ($request->user()->mustChangePassword()) {
            return redirect()
                ->route('password.force')
                ->with('warning', 'Por segurança, defina uma nova senha antes de continuar.');
        }

        // Redireciona para o painel principal após autenticação bem-sucedida
        return redirect()->intended(route('entregas.nova', absolute: false));
    }

    /**
     * Encerra a sessão do usuário de forma segura e redireciona para o login.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
