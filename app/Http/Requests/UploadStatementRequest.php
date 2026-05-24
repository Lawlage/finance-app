<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadStatementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'statement' => ['required', 'file', 'mimes:pdf,csv', 'max:10240'],
            'account' => ['required', 'string', 'max:255'],
        ];
    }
}
