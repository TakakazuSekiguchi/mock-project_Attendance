<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class AttendanceDetailRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */    
    public function rules()
    {
        // dd('rules通過');
        return [
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'after:clock_in', 'date_format:H:i'],

            //bladeでforeachで記述している箇所のバリデーション
            'breaks' => ['array'],
            'breaks.*.start' => ['required', 'date_format:H:i'],
            'breaks.*.end'   => ['required', 'date_format:H:i'],

            //予備の休憩記入箇所のバリデーション
            'break_start' => ['nullable', 'after:clock_in', 'before:clock_out', 'date_format:H:i'],
            'break_end' => ['nullable', 'after:clock_in', 'before:clock_out', 'date_format:H:i'],

            'reason' => ['required', 'max:20']
        ];
    }

    public function messages()
    {
        return [
            'clock_in.required' => '出勤時刻を入力してください',
            'clock_in.date_format' => '出勤時刻は時刻形式で入力してください',

            'clock_out.required' => '退勤時刻を入力してください',
            'clock_out.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'clock_out.date_format' => '退勤時刻は時刻形式で入力してください',

            'breaks.*.start.required' => '休憩開始時刻を入力してください',
            'breaks.*.start.date_format' => '休憩開始時刻は時刻形式で入力してください',

            'breaks.*.end.required' => '休憩終了時刻を入力してください',
            'breaks.*.end.date_format' => '休憩終了時刻は時刻形式で入力してください',

            'break_start.after' => '休憩時間が不適切な値です',
            'break_start.before' => '休憩時間が不適切な値です',
            'break_start.date_format' => '休憩開始時刻は時刻形式で入力してください',
            'break_end.after' => '休憩時間が不適切な値です',
            'break_end.before' => '休憩時間もしくは退勤時間が不適切な値です',
            'break_end.date_format' => '休憩終了時刻は時刻形式で入力してください',

            'reason.required' => '備考を記入してください',
            'reason.max' => '備考は、20文字以内で入力してください',
        ];
    }

    public function withValidator($validator)
    {
        // dd($this->breaks);
        $validator->after(function ($validator) {
            //すでにエラーがある場合、この後の追加チェックは行わない（この記述がないと無限ループする）
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // 出勤・退勤チェック
            if (empty($this->clock_in) || empty($this->clock_out)) {
                return;
            }

            // breaksチェック
            if (empty($this->breaks) || !is_array($this->breaks)) {
                return;
            }
            
            // 出勤・退勤
            $clockIn  = Carbon::createFromFormat('H:i', $this->clock_in);
            $clockOut = Carbon::createFromFormat('H:i', $this->clock_out);

            $prevEnd = null;

            foreach ($this->breaks as $index => $break) {
                if (empty($break['start']) || empty($break['end'])) {
                    continue;
                }

                $start = Carbon::createFromFormat('H:i', $break['start']);
                $end   = Carbon::createFromFormat('H:i', $break['end']);

                //「出勤 < 休憩開始」となっているかチェック
                if ($start->lessThanOrEqualTo($clockIn)) {
                    $validator->errors()->add(
                        "breaks.$index.start",
                        '休憩時間が不適切な値です'
                    );
                }

                //「休憩開始 < 休憩終了」となっているかチェック
                if ($end->lessThanOrEqualTo($start)) {
                    $validator->errors()->add(
                        "breaks.$index.end",
                        '休憩時間が不適切な値です'
                    );
                }

                //「休憩終了 < 退勤」となっているかチェック
                if ($end->greaterThanOrEqualTo($clockOut)) {
                    $validator->errors()->add(
                        "breaks.$index.end",
                        '休憩時間もしくは退勤時間が不適切な値です'
                    );
                }

                // 前の休憩と重複チェック
                if ($prevEnd && $start->lessThan($prevEnd)) {
                    $validator->errors()->add(
                        "breaks.$index.start",
                        '休憩時間が重複しています'
                    );
                }

                // 前の休憩時刻を記録
                $prevEnd = $end;
            }
         });
    }
}
