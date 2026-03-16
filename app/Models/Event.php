<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    protected $fillable = [
        'title', 'subtitle', 'description', 'slug', 'date',
        'time_start', 'time_end', 'location', 'venue',
        'credits', 'credits_label', 'image', 'image_small', 'image_big', 'status',
        'max_participants', 'created_by',
        'meta_title', 'meta_description', 'schema_org',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class)->orderBy('order');
    }

    public function speakers(): HasMany
    {
        return $this->hasMany(EventSpeaker::class)->orderBy('order');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    public function directors(): HasMany
    {
        return $this->hasMany(EventSpeaker::class)->where('speaker_role', 'director')->orderBy('order');
    }
}
