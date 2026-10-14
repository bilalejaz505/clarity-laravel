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
            'action' => 'required|in:checkout,complete,refresh,cancel',
            'planKey' => 'nullable|in:chat,freelance,bundle',
            'subscriptionId' => 'required_if:action,refresh,cancel|nullable|integer|min:1',
            'paypalSubscriptionId' => [
                'required_if:action,complete',
                'nullable',
                'string',
                'max:100',
                'regex:/^I-[A-Z0-9]+$/i',
            ],
        ];
    }
}
