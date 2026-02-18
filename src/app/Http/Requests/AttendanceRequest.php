<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AttendanceRequest extends FormRequest
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

            // 休憩1・休憩2（任意）
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
            // 備考
            'note.required' => '備考を記入してください',
            'note.max' => '備考は1000文字以内で入力してください。',

            // （任意：形式エラーを出したいなら）
            // 'clock_in.date_format' => '出勤時間の形式が正しくありません。',
            // 'clock_out.date_format' => '退勤時間の形式が正しくありません。',
        ];
    }

    public function attributes()
    {
        return [
            'clock_in' => '出勤時間',
            'clock_out' => '退勤時間',
            'break1_in' => '休憩時間',
            'break1_out' => '休憩終了時間',
            'break2_in' => '休憩時間2',
            'break2_out' => '休憩終了時間2',
            'note' => '備考',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $validator->after(function (Validator $validator) {

            $in = $this->parseTime($this->input('clock_in'));
            $out = $this->parseTime($this->input('clock_out'));

            // 1) 出勤 >= 退勤
            if ($in && $out && $in->greaterThanOrEqualTo($out)) {
                $msg = '出勤時間もしくは退勤時間が不適切な値です';
                $validator->errors()->add('clock_in', $msg);
                $validator->errors()->add('clock_out', $msg);
            }

            // 2) 休憩開始が 出勤より前 or 退勤より後 → 「休憩時間が不適切な値です」
            $this->validateBreakInRange($validator, 'break1_in', $in, $out);
            $this->validateBreakInRange($validator, 'break2_in', $in, $out);

            // 3) 休憩終了が 退勤より後 → 「休憩時間もしくは退勤時間が不適切な値です」
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

    private function parseTime(?string $value)
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
