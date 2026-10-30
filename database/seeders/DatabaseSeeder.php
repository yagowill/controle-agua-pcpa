<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Popula o banco com o usuário administrador inicial do SCEA/PCPA.
     *
     * Os demais usuários devem ser criados pelo administrador em /admin/usuarios.
     */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);
    }
}
