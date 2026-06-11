<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistration extends Model
{
    protected $fillable = [
        'event_id', 'user_id', 'first_name', 'last_name', 'email',
        'phone', 'specialty', 'professional_grade', 'cuim', 'message', 'status', 'registered_at',
        'is_present', 'present_at', 'feedback_sent_at', 'feedback_token', 'feedback_completed', 'diploma_sent',
    ];

    protected $casts = [
        'registered_at'    => 'datetime',
        'present_at'       => 'datetime',
        'feedback_sent_at' => 'datetime',
        'is_present'       => 'boolean',
        'feedback_completed' => 'boolean',
        'diploma_sent'     => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function feedbackResponse(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(FeedbackResponse::class, 'registration_id');
    }
}
