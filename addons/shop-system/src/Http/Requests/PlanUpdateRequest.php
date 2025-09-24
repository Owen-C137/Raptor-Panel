<?php

namespace PterodactylAddons\ShopSystem\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PlanUpdateRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:shop_categories,id',
            'visible' => 'sometimes|boolean',
            'sort_order' => 'nullable|integer|min:0',
            
            // Server Limits
            'server_limits.memory' => 'required|integer|min:0',
            'server_limits.swap' => 'nullable|integer|min:-1',
            'server_limits.disk' => 'required|integer|min:0',
            'server_limits.io' => 'nullable|integer|min:10|max:1000',
            'server_limits.cpu' => 'required|integer|min:0',
            'server_limits.threads' => 'nullable|string',
            'server_limits.oom_disabled' => 'sometimes|boolean',
            
            // Server Feature Limits
            'server_feature_limits.databases' => 'nullable|integer|min:0',
            'server_feature_limits.allocations' => 'nullable|integer|min:0',
            'server_feature_limits.backups' => 'nullable|integer|min:0',
            
            // Server Configuration
            'egg_id' => 'required|exists:eggs,id',
            'allowed_nodes' => 'nullable|array',
            'allowed_nodes.*' => 'exists:nodes,id',
            'allowed_locations' => 'nullable|array',
            'allowed_locations.*' => 'exists:locations,id',
            
            // Billing Cycles
            'billing_cycles' => 'required|array|min:1',
            'billing_cycles.*.cycle' => 'required|string|in:monthly,quarterly,semi_annually,annually,one_time',
            'billing_cycles.*.price' => 'required|numeric|min:0',
            'billing_cycles.*.setup_fee' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Plan name is required.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'Selected category does not exist.',
            'egg_id.required' => 'Please select an egg.',
            'egg_id.exists' => 'Selected egg does not exist.',
            'billing_cycles.required' => 'At least one billing cycle is required.',
            'billing_cycles.min' => 'At least one billing cycle is required.',
            'billing_cycles.*.cycle.required' => 'Billing cycle type is required.',
            'billing_cycles.*.cycle.in' => 'Invalid billing cycle type.',
            'billing_cycles.*.price.required' => 'Price is required for each billing cycle.',
            'billing_cycles.*.price.min' => 'Price must be 0 or greater.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert checkbox values
        $this->merge([
            'visible' => $this->has('visible'),
            'server_limits' => array_merge($this->input('server_limits', []), [
                'oom_disabled' => $this->input('server_limits.oom_disabled') === '1',
            ]),
        ]);
    }
}
