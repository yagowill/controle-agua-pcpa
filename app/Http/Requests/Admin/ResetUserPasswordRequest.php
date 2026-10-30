<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetUserPasswordRequest extends FormRequest
{
    /**
     * Apenas administradores podem resetar senhas de outros usuários.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * Regras para definição da senha temporária pelo administrador.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', Password::defaults()],
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
            'password.required' => 'Informe a senha temporária.',
            'password.confirmed' => 'A confirmação da senha temporária não coincide.',
        ];
    }
}
