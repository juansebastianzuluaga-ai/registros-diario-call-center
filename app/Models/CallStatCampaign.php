<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'date',
    'campaign_id',
    'nombre',
    'tipo',
    'total',
    'manuales',
    'recibidas',
    'recibidas_transferencias',
    'atendidas',
    'expiradas',
    'abandonadas',
    'abandonadas_anuncio',
    't_abandono',
    't_espera_conexion',
    'efectuadas_manuales',
    'conectadas_manuales',
    'no_conectadas_manuales',
    't_espera_conexion_manuales',
])]
class CallStatCampaign extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
