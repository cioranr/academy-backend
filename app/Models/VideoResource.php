<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class VideoResource extends Model
{
    protected $fillable = [
        'title', 'slug', 'short_description', 'content',
        'video_path', 'video_embed', 'active', 'created_by',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class, 'video_resource_doctor')
            ->withPivot('order')
            ->orderBy('video_resource_doctor.order')
            ->withTimestamps();
    }
}
