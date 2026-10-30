<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'matricula', 'email', 'password', 'role', 'sector', 'is_active', 'must_change_password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Define os tipos de conversão dos atributos do modelo.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    /**
     * Verifica se o usuário tem perfil de administrador.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Verifica se o usuário tem perfil de atendente.
     */
    public function isAttendant(): bool
    {
        return $this->role === 'atendente';
    }

    /**
     * Verifica se a conta está ativa (pode autenticar no sistema).
     */
    public function isActive(): bool
    {
        return (bool) $this->is_active;
    }

    /**
     * Indica que o usuário precisa definir uma nova senha antes de usar o sistema.
     */
    public function mustChangePassword(): bool
    {
        return (bool) $this->must_change_password;
    }
}
