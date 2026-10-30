<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Adiciona identificação por nome de usuário e política de troca obrigatória de senha.
     *
     * Regras do SCEA/PCPA:
     * - O login passa a ser feito por "username" (o e-mail deixa de ser obrigatório).
     * - A matrícula institucional é opcional.
     * - Todo usuário criado nasce com "must_change_password = true".
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Identificador de acesso (único) e matrícula funcional (opcional)
            $table->string('username', 50)->nullable()->unique()->after('name');
            $table->string('matricula', 50)->nullable()->after('username');
            // Flag que obriga a troca de senha no primeiro acesso
            $table->boolean('must_change_password')->default(true)->after('is_active');
        });

        $this->backfillUsernames();

        Schema::table('users', function (Blueprint $table) {
            // O e-mail deixa de ser obrigatório para autenticação
            $table->string('email')->nullable()->change();
            // Após o preenchimento dos registros existentes, o username torna-se obrigatório
            $table->string('username', 50)->nullable(false)->change();
        });
    }

    /**
     * Preenche o "username" dos usuários já existentes a partir do e-mail cadastrado,
     * garantindo unicidade (ex.: admin, admin2, admin3...).
     */
    private function backfillUsernames(): void
    {
        $used = [];

        DB::table('users')->orderBy('id')->chunkById(200, function ($users) use (&$used) {
            foreach ($users as $user) {
                $base = Str::slug(Str::before((string) $user->email, '@'), '_');

                if ($base === '') {
                    $base = 'usuario'.$user->id;
                }

                $username = $base;
                $suffix = 2;

                while (in_array($username, $used, true)) {
                    $username = $base.$suffix;
                    $suffix++;
                }

                $used[] = $username;

                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['username' => $username]);
            }
        });
    }

    /**
     * Reverte as colunas adicionadas nesta migration.
     */
    public function down(): void
    {
        // O e-mail permanece nullable no rollback para não perder contas criadas sem e-mail.
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn(['username', 'matricula', 'must_change_password']);
        });
    }
};
