<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'clock_in',
        'clock_out'
    ];

    //勤怠一覧（blade側のPHP）で「\Carbon\Carbon::parse」を省略する為
    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime'
    ];

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class, 'attendance_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stampCorrectionRequests()
    {
        return $this->hasMany(StampCorrectionRequest::class, 'attendance_id');
    }

    // bladeでstatusを呼び出し分岐
    public function getStatusAttribute()
    {
        if (!$this->clock_in) {
            return 'before_work';
        }

        if ($this->clock_out) {
            return 'finished';
        }

        // 最新のbreak_startで降順した状態
        $latestBreak = $this->breakTimes()
            ->latest('break_start')
            ->first();

        if ($latestBreak && !$latestBreak->break_end) {
            return 'on_break';
        }

        return 'working';
    }

    // スコープ：今日の出勤レコードを取得
    public function scopeTodayByUser($query, $userId)
    {
        return $query->where('user_id', $userId)
            ->whereBetween('clock_in', [
                now()->startOfDay(),
                now()->endOfDay()
            ]);
    }
}
