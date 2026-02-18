<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'date', 'clock_in', 'clock_out', 'note',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function breaks()
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    public function getBreakMinutesAttribute(): int
    {
        return $this->breaks->sum(function ($b) {
            if (! $b->break_in || ! $b->break_out) {
                return 0;
            }

            return $b->break_out->diffInMinutes($b->break_in);
        });
    }

    public function getWorkMinutesAttribute(): int
    {
        if (! $this->clock_in || ! $this->clock_out) {
            return 0;
        }

        $total = $this->clock_out->diffInMinutes($this->clock_in);

        return max(0, $total - $this->break_minutes);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
