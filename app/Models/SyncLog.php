<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['source', 'status', 'date', 'message'])]
class SyncLog extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }
}
