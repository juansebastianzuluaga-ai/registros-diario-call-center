<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('call_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();

            $table->unsignedInteger('total')->default(0);

            // Llamadas salientes por Discador
            $table->unsignedInteger('salientes_discador_atendidas')->default(0);
            $table->unsignedInteger('salientes_discador_no_atendidas')->default(0);
            $table->unsignedInteger('salientes_discador_perdidas')->default(0);

            // Llamadas Entrantes
            $table->unsignedInteger('entrantes_total')->default(0);
            $table->unsignedInteger('entrantes_atendidas')->default(0);
            $table->unsignedInteger('entrantes_expiradas')->default(0);
            $table->unsignedInteger('entrantes_abandonadas')->default(0);
            $table->unsignedInteger('entrantes_abandonadas_anuncio')->default(0);
            $table->unsignedInteger('entrantes_transferidas_atendidas')->default(0);
            $table->unsignedInteger('entrantes_transferidas_no_atendidas')->default(0);

            // Llamadas salientes Manuales
            $table->unsignedInteger('salientes_manuales_conectadas')->default(0);
            $table->unsignedInteger('salientes_manuales_no_conectadas')->default(0);

            // Llamadas salientes Preview
            $table->unsignedInteger('salientes_preview_conectadas')->default(0);
            $table->unsignedInteger('salientes_preview_no_conectadas')->default(0);

            $table->json('raw_payload')->nullable();
            $table->timestamp('synced_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_stats');
    }
};
