<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Atualiza a senha do usuário autenticado (fluxo voluntário, via perfil).
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed', 'different:current_password'],
        ], [
            'current_password.required' => 'Informe a sua senha atual.',
            'current_password.current_password' => 'A senha atual informada está incorreta.',
            'password.required' => 'Informe a nova senha.',
            'password.confirmed' => 'A confirmação da nova senha não coincide.',
            'password.different' => 'A nova senha deve ser diferente da senha atual.',
        ]);

        // Ao trocar a senha, a obrigatoriedade de troca é encerrada
        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        return back()->with('status', 'password-updated');
    }
}
