<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialty extends Model
{
    protected $fillable = ['value', 'label', 'order'];

    protected $casts = ['order' => 'integer'];
}
