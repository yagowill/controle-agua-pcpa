<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Apenas administradores podem cadastrar novos usuários (validação extra de segurança).
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * Normaliza o nome de usuário para minúsculas e sem espaços nas extremidades.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('username')) {
            $this->merge([
                'username' => Str::lower(trim((string) $this->input('username'))),
            ]);
        }

        if ($this->has('matricula') && blank($this->input('matricula'))) {
            $this->merge(['matricula' => null]);
        }
    }

    /**
     * Regras de criação de conta pelo administrador.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9._-]+$/', 'unique:users,username'],
            'matricula' => ['nullable', 'string', 'max:50'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(['admin', 'atendente'])],
            'sector' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Mensagens de validação em português.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome completo é obrigatório.',
            'username.required' => 'O nome de usuário é obrigatório.',
            'username.regex' => 'O nome de usuário deve conter apenas letras, números, ponto (.), hífen (-) ou sublinhado (_).',
            'username.unique' => 'Este nome de usuário já está em uso no sistema.',
            'password.required' => 'A senha inicial é obrigatória.',
            'password.confirmed' => 'A confirmação da senha não coincide.',
            'role.required' => 'Selecione o perfil de acesso.',
            'role.in' => 'Perfil de acesso inválido.',
        ];
    }
}
