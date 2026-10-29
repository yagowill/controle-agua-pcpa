<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona colunas de perfil, setor e status à tabela de usuários do SCEA/PCPA.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Perfil de acesso: administrador ou atendente do almoxarifado
            $table->enum('role', ['admin', 'atendente'])->default('atendente')->after('name');
            // Setor/Lotação do servidor na PCPA
            $table->string('sector')->nullable()->after('role');
            // Controle de acesso ativo/inativo da conta
            $table->boolean('is_active')->default(true)->after('remember_token');
        });
    }

    /**
     * Reverte as alterações na tabela de usuários.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'sector', 'is_active']);
        });
    }
};
