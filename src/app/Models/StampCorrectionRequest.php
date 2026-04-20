<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StampCorrectionRequest extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'status',
        'reason',
        'attendance_id',
        'after_clock_in',
        'after_clock_out',
        'approved_by',
        'approved_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function breakRequestDetails()
    {
        return $this->hasMany(BreakRequestDetail::class, 'stamp_correction_request_id');
    }
}