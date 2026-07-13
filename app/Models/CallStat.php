<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'date',
    'total',
    'salientes_discador_atendidas',
    'salientes_discador_no_atendidas',
    'salientes_discador_perdidas',
    'entrantes_total',
    'entrantes_atendidas',
    'entrantes_expiradas',
    'entrantes_abandonadas',
    'entrantes_abandonadas_anuncio',
    'entrantes_transferidas_atendidas',
    'entrantes_transferidas_no_atendidas',
    'salientes_manuales_conectadas',
    'salientes_manuales_no_conectadas',
    'salientes_preview_conectadas',
    'salientes_preview_no_conectadas',
    'raw_payload',
    'synced_at',
])]
class CallStat extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'raw_payload' => 'array',
            'synced_at' => 'datetime',
        ];
    }
}
