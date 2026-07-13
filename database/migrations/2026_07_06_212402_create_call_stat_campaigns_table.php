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
        Schema::create('call_stat_campaigns', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('campaign_id');
            $table->string('nombre');
            $table->string('tipo')->nullable();

            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('manuales')->default(0);

            // Detalle enriquecido (cuando Zennia lo reporta para el tipo de la campaña)
            $table->unsignedInteger('recibidas')->default(0);
            $table->unsignedInteger('recibidas_transferencias')->default(0);
            $table->unsignedInteger('atendidas')->default(0);
            $table->unsignedInteger('expiradas')->default(0);
            $table->unsignedInteger('abandonadas')->default(0);
            $table->unsignedInteger('abandonadas_anuncio')->default(0);
            $table->float('t_abandono')->default(0);
            $table->float('t_espera_conexion')->default(0);
            $table->unsignedInteger('efectuadas_manuales')->default(0);
            $table->unsignedInteger('conectadas_manuales')->default(0);
            $table->unsignedInteger('no_conectadas_manuales')->default(0);
            $table->float('t_espera_conexion_manuales')->default(0);

            $table->timestamps();

            $table->unique(['date', 'campaign_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_stat_campaigns');
    }
};
