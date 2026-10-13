<?php

namespace App\Http\Requests\Admin;

use App\Services\EntitlementService;
use Illuminate\Foundation\Http\FormRequest;

class AdminActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() &&
            app(EntitlementService::class)->admin($this->user());
    }

    public function rules(): array
    {
        return match ($this->input('action')) {
            'settings' => [
                'revision' => 'required|integer|min:0',
                'config' => 'required|array:appName,maintenance,notice,maxUploadMB,maxImportMessages,demoEnabled,trialEnabled,trialDays,trialMessages,trialRequests,aiEnabled,aiModel,aiMaxOutput,paypalEnvironment,paypalClientId,paypalWebhookId,plans',
                'config.appName' => 'required|string|max:80',
                'config.maintenance' => 'required|boolean',
                'config.notice' => 'present|string|max:500',
                'config.maxUploadMB' => 'required|integer|between:1,50',
                'config.maxImportMessages' => 'required|integer|between:100,100000',
                'config.demoEnabled' => 'required|boolean',
                'config.trialEnabled' => 'required|boolean',
                'config.trialDays' => 'required|integer|between:1,30',
                'config.trialMessages' => 'required|integer|between:1,10000',
                'config.trialRequests' => 'required|integer|between:1,100',
                'config.aiEnabled' => 'required|boolean',
                'config.aiModel' => 'present|string|max:150|regex:/^[A-Za-z0-9._:-]*$/',
                'config.aiMaxOutput' => 'required|integer|between:1000,10000',
                'config.paypalEnvironment' => 'required|in:sandbox,live',
                'config.paypalClientId' => 'present|string|max:200',
                'config.paypalWebhookId' => 'present|string|max:100',
                'config.plans' => 'required|array|size:3',
                'config.plans.*.key' => 'required|in:chat,freelance,bundle|distinct',
                'config.plans.*.name' => 'required|string|max:60',
                'config.plans.*.description' => 'present|string|max:200',
                'config.plans.*.price' => 'required|numeric|between:0,1000',
                'config.plans.*.currency' => 'required|in:USD,EUR,GBP,CAD,AUD',
                'config.plans.*.messages' => 'required|integer|between:100,1000000',
                'config.plans.*.requests' => 'required|integer|between:1,10000',
                'config.plans.*.products' => 'required|array|min:1',
                'config.plans.*.products.*' => 'required|in:chat,freelance',
                'config.plans.*.paypalPlanId' => 'present|string|max:100',
                'config.plans.*.enabled' => 'required|boolean',
                'openaiKey' => 'nullable|string|max:300|regex:/^sk-[A-Za-z0-9_-]{15,}$/',
                'paypalSecret' => 'nullable|string|max:500',
                'action' => 'required|in:settings',
            ],
            'user-status' => [
                'action' => 'required|in:user-status',
                'userId' => 'required|integer|min:1',
                'status' => 'required|in:active,suspended',
            ],
            'delete-secret' => [
                'action' => 'required|in:delete-secret',
                'provider' => 'required|in:openai,paypal',
            ],
            default => [
                'action' => 'required|in:settings,user-status,delete-secret',
            ],
        };
    }
}
