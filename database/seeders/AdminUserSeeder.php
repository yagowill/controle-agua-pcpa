<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Cria o usuário administrador padrão do SCEA/PCPA.
     * Executar apenas em ambiente de desenvolvimento/primeiro deploy.
     */
    public function run(): void
    {
        // Verifica se já existe para evitar duplicatas
        if (User::where('username', 'yago.martins')->exists()) {
            $this->command->info('Usuário admin já existe. Seeder ignorado.');

            return;
        }

        User::create([
            'name' => 'Yago Martins',
            'username' => 'yago.martins',
            'matricula' => null,
            'email' => null,
            'password' => Hash::make('Admin@pcpa2026'),
            'role' => 'admin',
            'sector' => 'ALMOXARIFADO CENTRAL',
            'is_active' => true,
            // Por segurança, exige a troca da senha padrão no primeiro acesso
            'must_change_password' => true,
        ]);

        $this->command->info('Usuário admin criado: yago.martins / Admin@pcpa2026 (troca de senha obrigatória no primeiro acesso)');
    }
}
