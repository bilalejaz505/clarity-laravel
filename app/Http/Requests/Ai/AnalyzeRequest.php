<?php

namespace App\Http\Requests\Ai;

use Illuminate\Foundation\Http\FormRequest;

class AnalyzeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => 'required|in:analyze,ask,reply',
            'messages' => 'required|array|min:1|max:105',
            'messages.*.id' => 'required|string|max:100',
            'messages.*.sender' => 'required|string|max:200',
            'messages.*.text' => 'present|string|max:12000',
            'messages.*.timestamp' => 'nullable|string|max:100',
            'messages.*.source' => 'required|string|max:1000',
            'question' => 'nullable|string|max:2500',
            'purpose' => 'nullable|in:personal,freelance',
        ];
    }
}
