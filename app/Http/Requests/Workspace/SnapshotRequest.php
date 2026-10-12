<?php

namespace App\Http\Requests\Workspace;

use App\Rules\ValidWorkspace;
use Illuminate\Foundation\Http\FormRequest;

class SnapshotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->status === 'active';
    }

    public function rules(): array
    {
        return ['product' => 'sometimes|in:chat,freelance', 'store' => ['required', 'array', new ValidWorkspace]];
    }

    protected function prepareForValidation(): void
    {
        abort_if(strlen($this->getContent()) > 10485760, 413, 'Snapshots are limited to 10 MB.');
    }
}
