<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índices que aceleram os filtros e agregações do dashboard/relatórios.
     */
    public function up(): void
    {
        Schema::table('water_deliveries', function (Blueprint $table) {
            $table->index('reception_date');
            $table->index('sector');
            $table->index('status');
            $table->index(['reception_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('water_deliveries', function (Blueprint $table) {
            $table->dropIndex(['reception_date', 'status']);
            $table->dropIndex(['status']);
            $table->dropIndex(['sector']);
            $table->dropIndex(['reception_date']);
        });
    }
};
