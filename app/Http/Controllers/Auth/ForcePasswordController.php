<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ForcePasswordController extends Controller
{
    /**
     * Exibe a tela de troca de senha.
     *
     * A mesma tela atende dois cenários:
     * - Troca obrigatória (primeiro acesso ou senha resetada pelo admin);
     * - Troca voluntária pelo menu "Alterar Senha".
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Auth/ForcePassword', [
            'username' => $request->user()->username,
            'warning' => session('warning'),
            // Indica se a troca é obrigatória para liberar o sistema
            'required' => $request->user()->mustChangePassword(),
        ]);
    }

    /**
     * Salva a nova senha do usuário e encerra a obrigatoriedade de troca.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults(), 'different:current_password'],
        ], [
            'current_password.required' => 'Informe a senha provisória recebida.',
            'current_password.current_password' => 'A senha provisória informada está incorreta.',
            'password.required' => 'Informe a nova senha.',
            'password.confirmed' => 'A confirmação da nova senha não coincide.',
            'password.different' => 'A nova senha deve ser diferente da senha provisória.',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        return redirect()
            ->intended(route('entregas.nova', absolute: false))
            ->with('success', 'Senha atualizada com sucesso! Bem-vindo ao SCEA.');
    }
}
