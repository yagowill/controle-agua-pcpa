<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Exibe a tela de cadastro de usuários do SCEA/PCPA.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Processa o cadastro de novo usuário com perfil e setor de lotação.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role'     => ['required', 'in:admin,atendente'],
            'sector'   => ['nullable', 'string', 'max:255'],
        ], [
            'name.required'     => 'O nome completo é obrigatório.',
            'email.required'    => 'O e-mail institucional é obrigatório.',
            'email.unique'      => 'Este e-mail já está cadastrado no sistema.',
            'password.required' => 'A senha é obrigatória.',
            'password.confirmed'=> 'A confirmação de senha não coincide.',
            'role.required'     => 'Selecione o perfil de acesso.',
            'role.in'           => 'Perfil de acesso inválido.',
        ]);

        $user = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'sector'    => $request->sector ? mb_strtoupper($request->sector, 'UTF-8') : null,
            'is_active' => true,
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Redireciona para o painel principal do SCEA após o cadastro
        return redirect(route('entregas.nova', absolute: false));
    }
}
