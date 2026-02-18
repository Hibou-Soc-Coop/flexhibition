<?php

namespace App\Http\Requests;

use App\Facades\Settings;
use Illuminate\Foundation\Http\FormRequest;

class BackupRestoreRequest extends FormRequest
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
        $checksumRequired = (bool) Settings::get('backups.checksum_enabled', true);
        $checksumRule = $checksumRequired ? 'required' : 'nullable';

        return [
            'backup_file' => ['required', 'file', 'mimes:zip'],
            'checksum_file' => [$checksumRule, 'file', 'mimes:txt,sha256'],
        ];
    }
}
