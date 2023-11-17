<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LightActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'completed',
        'execution_date',
    ];
}
