<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('water_deliveries', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // Ex: PCPA-2026-0001
            $table->enum('operation_type', ['PADRAO', 'BATE_VOLTA'])->default('PADRAO');
            $table->string('sector');
            $table->integer('empty_bottles_count');
            $table->timestamp('reception_date');
            $table->timestamp('estimated_date')->nullable();
            $table->string('received_from_person');
            $table->string('reception_attendant');
            
            // Dados da 2ª Etapa (Retirada)
            $table->timestamp('delivery_date')->nullable();
            $table->string('delivered_to_person')->nullable();
            $table->string('delivery_attendant')->nullable();
            
            $table->enum('status', ['DISPONIVEL', 'CONCLUIDO'])->default('DISPONIVEL');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('water_deliveries');
    }
};