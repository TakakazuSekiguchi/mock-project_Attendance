<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakRequestDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'stamp_correction_request_id',
        'break_time_id',
        'after_start',
        'after_end'
    ];

    public function stampCorrectionRequest()
    {
        return $this->belongsTo(StampCorrectionRequest::class);
    }
}
