<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionnaireQuestion extends Model
{
    protected $fillable = ['questionnaire_id', 'question', 'type', 'options', 'required', 'order'];

    protected $casts = [
        'options'  => 'array',
        'required' => 'boolean',
    ];

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }
}
