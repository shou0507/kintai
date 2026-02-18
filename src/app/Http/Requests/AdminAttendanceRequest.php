<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AdminAttendanceRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'clock_in' => ['required', 'date_format:H:i'],
            'clock_out' => ['required', 'date_format:H:i'],

            'break1_in' => ['nullable', 'date_format:H:i'],
            'break1_out' => ['nullable', 'date_format:H:i'],
            'break2_in' => ['nullable', 'date_format:H:i'],
            'break2_out' => ['nullable', 'date_format:H:i'],

            'note' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages()
    {
        return [
            'note.required' => '備考を記入してください',
            'note.max' => '備考は1000文字以内で入力してください。',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function (Validator $validator) {

            $in = $this->parseTime($this->input('clock_in'));
            $out = $this->parseTime($this->input('clock_out'));

            // 1) 出勤 >= 退勤
            if ($in && $out && $in->greaterThanOrEqualTo($out)) { // ✅ 修正
                $msg = '出勤時間もしくは退勤時間が不適切な値です';
                $validator->errors()->add('clock_in', $msg);
                $validator->errors()->add('clock_out', $msg);
            }

            // ★ outだけ入力を弾く（break_in が NOT NULL 対応）
            if ($this->input('break1_out') && ! $this->input('break1_in')) {
                $validator->errors()->add('break1_in', '休憩時間が不適切な値です');
            }
            if ($this->input('break2_out') && ! $this->input('break2_in')) {
                $validator->errors()->add('break2_in', '休憩時間が不適切な値です');
            }

            // 2) 休憩開始：勤務時間外
            $this->validateBreakInRange($validator, 'break1_in', $in, $out);
            $this->validateBreakInRange($validator, 'break2_in', $in, $out);

            // 3) 休憩終了：退勤より後
            $this->validateBreakOutAfterWork($validator, 'break1_out', $out);
            $this->validateBreakOutAfterWork($validator, 'break2_out', $out);
        });
    }

    private function validateBreakInRange(Validator $validator, string $field, ?Carbon $in, ?Carbon $out): void
    {
        $bIn = $this->parseTime($this->input($field));
        if (! $bIn || ! $in || ! $out) {
            return;
        }

        if ($bIn->lt($in) || $bIn->gt($out)) {
            $validator->errors()->add($field, '休憩時間が不適切な値です');
        }
    }

    private function validateBreakOutAfterWork(Validator $validator, string $field, ?Carbon $out): void
    {
        $bOut = $this->parseTime($this->input($field));
        if (! $bOut || ! $out) {
            return;
        }

        if ($bOut->gt($out)) {
            $validator->errors()->add($field, '休憩時間もしくは退勤時間が不適切な値です');
        }
    }

    private function parseTime(?string $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            return Carbon::createFromFormat('H:i', $value);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
