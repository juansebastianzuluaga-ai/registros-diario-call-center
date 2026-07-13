<?php

namespace Database\Factories;

use App\Models\CallStat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CallStat>
 */
class CallStatFactory extends Factory
{
    protected $model = CallStat::class;

    public function definition(): array
    {
        $entrantesTotal = fake()->numberBetween(50, 500);
        $entrantesAtendidas = fake()->numberBetween(0, $entrantesTotal);

        return [
            'date' => fake()->date(),
            'total' => $entrantesTotal,
            'salientes_discador_atendidas' => 0,
            'salientes_discador_no_atendidas' => 0,
            'salientes_discador_perdidas' => 0,
            'entrantes_total' => $entrantesTotal,
            'entrantes_atendidas' => $entrantesAtendidas,
            'entrantes_expiradas' => 0,
            'entrantes_abandonadas' => $entrantesTotal - $entrantesAtendidas,
            'entrantes_abandonadas_anuncio' => 0,
            'entrantes_transferidas_atendidas' => 0,
            'entrantes_transferidas_no_atendidas' => 0,
            'salientes_manuales_conectadas' => 0,
            'salientes_manuales_no_conectadas' => 0,
            'salientes_preview_conectadas' => 0,
            'salientes_preview_no_conectadas' => 0,
            'raw_payload' => [],
            'synced_at' => now(),
        ];
    }
}
