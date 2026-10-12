<?php

namespace App\Http\Requests\Billing;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:checkout,refresh,cancel',
            'planKey' => 'nullable|in:chat,freelance,bundle',
            'subscriptionId' => 'required_unless:action,checkout|nullable|integer|min:1',
        ];
    }
}
