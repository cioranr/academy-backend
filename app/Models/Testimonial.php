<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = ['title', 'subtitle', 'doctor_name', 'quote', 'workshop_title', 'workshop_href', 'image', 'video', 'youtube_url', 'active', 'order'];

    protected $casts = ['active' => 'boolean'];
}
