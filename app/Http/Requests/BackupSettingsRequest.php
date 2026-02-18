<?php

namespace App\Http\Requests;

use Cron\CronExpression;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BackupSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $disks = array_keys(config('filesystems.disks', []));

        return [
            'remote_enabled' => ['required', 'boolean'],
            'remote_disk' => ['nullable', 'string', Rule::in($disks)],
            'retention_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'checksum_enabled' => ['required', 'boolean'],
            'schedule_enabled' => ['required', 'boolean'],
            'schedule_cron' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $remoteEnabled = filter_var($this->input('remote_enabled'), FILTER_VALIDATE_BOOLEAN);
            $remoteDisk = (string) $this->input('remote_disk');

            if ($remoteEnabled && $remoteDisk === '') {
                $validator->errors()->add('remote_disk', 'Remote disk is required when remote backups are enabled.');
            }

            $scheduleEnabled = filter_var($this->input('schedule_enabled'), FILTER_VALIDATE_BOOLEAN);
            $cronExpression = (string) $this->input('schedule_cron');

            if ($scheduleEnabled) {
                if ($cronExpression === '') {
                    $validator->errors()->add('schedule_cron', 'Schedule cron expression is required.');
                } elseif (! CronExpression::isValidExpression($cronExpression)) {
                    $validator->errors()->add('schedule_cron', 'Schedule cron expression is invalid.');
                }
            }
        });
    }
}
