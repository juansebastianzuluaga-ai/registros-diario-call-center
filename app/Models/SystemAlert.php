<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['type', 'message', 'date', 'resolved_at'])]
class SystemAlert extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'resolved_at' => 'datetime',
        ];
    }
}
