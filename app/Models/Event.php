<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Event extends Model
{
    protected $fillable = [
        'title', 'subtitle', 'description', 'slug', 'date', 'end_date',
        'time_start', 'time_end', 'location', 'venue',
        'credits', 'credits_label', 'image', 'image_small', 'image_big', 'status',
        'max_participants', 'created_by',
        'meta_title', 'meta_description', 'schema_org',
        'fully_booked_message', 'show_fully_booked_message',
        'cmr_address',
        'send_feedback', 'questionnaire_id',
    ];

    protected $casts = [
        'date' => 'date',
        'end_date' => 'date',
        'show_fully_booked_message' => 'boolean',
        'send_feedback' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(EventSession::class)->orderBy('day_index')->orderBy('order');
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

    public function questionnaire(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Questionnaire::class);
    }

    /** Returns ['dtStart' => 'YYYYMMDDTHHmmss', 'dtEnd' => ..., 'isoStart' => ..., 'isoEnd' => ...] */
    public function calendarDateTimes(): array
    {
        $date      = Carbon::parse($this->date);
        $startTime = $this->time_start
            ? str_replace(':', '', substr($this->time_start, 0, 5)) . '00'
            : '090000';
        $endTime   = $this->time_end
            ? str_replace(':', '', substr($this->time_end, 0, 5)) . '00'
            : $startTime;

        return [
            'dtStart'  => $date->format('Ymd') . 'T' . $startTime,
            'dtEnd'    => $date->format('Ymd') . 'T' . $endTime,
            'isoStart' => $date->format('Y-m-d') . 'T' . substr($startTime, 0, 2) . ':' . substr($startTime, 2, 2) . ':00',
            'isoEnd'   => $date->format('Y-m-d') . 'T' . substr($endTime, 0, 2) . ':' . substr($endTime, 2, 2) . ':00',
        ];
    }

    protected function googleCalendarUrl(): Attribute
    {
        return Attribute::make(get: function () {
            $dt       = $this->calendarDateTimes();
            $location = implode(', ', array_filter([$this->location, $this->venue]));

            return 'https://calendar.google.com/calendar/render?' . http_build_query(array_filter([
                'action'   => 'TEMPLATE',
                'text'     => $this->title,
                'dates'    => $dt['dtStart'] . '/' . $dt['dtEnd'],
                'location' => $location,
                'details'  => $this->description ?? '',
            ]));
        });
    }

    protected function outlookCalendarUrl(): Attribute
    {
        return Attribute::make(get: function () {
            $dt       = $this->calendarDateTimes();
            $location = implode(', ', array_filter([$this->location, $this->venue]));

            return 'https://outlook.live.com/calendar/0/deeplink/compose?' . http_build_query(array_filter([
                'rru'      => 'addevent',
                'path'     => '/calendar/action/compose',
                'subject'  => $this->title,
                'startdt'  => $dt['isoStart'],
                'enddt'    => $dt['isoEnd'],
                'location' => $location,
                'body'     => $this->description ?? '',
            ]));
        });
    }
}
