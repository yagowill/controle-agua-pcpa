<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetUserPasswordRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Lista os usuários do sistema com busca por nome, usuário ou matrícula.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search'));

        $users = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('matricula', 'like', "%{$search}%")
                        ->orWhere('sector', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'matricula' => $user->matricula,
                'email' => $user->email,
                'role' => $user->role,
                'sector' => $user->sector,
                'is_active' => (bool) $user->is_active,
                'must_change_password' => (bool) $user->must_change_password,
                'created_at' => optional($user->created_at)->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Usuarios/Index', [
            'users' => $users,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Cadastra um novo usuário com senha provisória e troca obrigatória no primeiro acesso.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => mb_strtoupper($validated['name'], 'UTF-8'),
            'username' => $validated['username'],
            'matricula' => $validated['matricula'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'sector' => ! empty($validated['sector'])
                ? mb_strtoupper($validated['sector'], 'UTF-8')
                : null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            // Todo usuário novo deve redefinir a senha no primeiro login
            'must_change_password' => true,
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "Usuário {$user->username} cadastrado com sucesso. Senha provisória deve ser alterada no primeiro acesso.");
    }

    /**
     * Redefine a senha de um usuário (senha temporária) e reativa a troca obrigatória.
     */
    public function resetPassword(ResetUserPasswordRequest $request, User $user): RedirectResponse
    {
        // O próprio administrador deve usar a tela "Alterar Senha" informando a senha atual
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Para alterar a sua própria senha, utilize a opção "Alterar Senha" no menu.');
        }

        $temporaryPassword = $request->validated('password');

        $user->forceFill([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
            // Invalida o "lembrar-me" do usuário afetado
            'remember_token' => Str::random(60),
        ])->save();

        return back()->with(
            'success',
            "Senha do usuário {$user->username} redefinida. Senha temporária: {$temporaryPassword} — o usuário deverá alterá-la no próximo acesso."
        );
    }

    /**
     * Ativa ou desativa o acesso de um usuário ao sistema.
     */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Não é possível desativar a própria conta.');
        }

        $user->update(['is_active' => ! $user->is_active]);

        $status = $user->is_active ? 'ativado' : 'desativado';

        return back()->with('success', "Acesso do usuário {$user->username} {$status} com sucesso.");
    }

    /**
     * Remove um usuário do sistema.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Não é possível excluir a própria conta.');
        }

        $username = $user->username;

        $user->delete();

        return back()->with('success', "Usuário {$username} removido do sistema.");
    }
}
