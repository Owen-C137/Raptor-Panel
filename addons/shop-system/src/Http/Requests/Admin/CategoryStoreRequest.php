<?php

namespace PterodactylAddons\ShopSystem\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CategoryStoreRequest extends FormRequest
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
            'name' => 'required|string|max:255|unique:shop_categories,name',
            'description' => 'nullable|string|max:1000',
            'slug' => 'nullable|string|max:255|unique:shop_categories,slug|regex:/^[a-z0-9-]+$/',
            'image' => 'nullable|string|max:255',
            'image_path' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'active' => 'boolean',
            'parent_id' => 'nullable|exists:shop_categories,id',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required.',
            'name.unique' => 'A category with this name already exists.',
            'name.max' => 'Category name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 1000 characters.',
            'slug.unique' => 'A category with this slug already exists.',
            'slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens.',
            'sort_order.min' => 'Sort order must be a positive number.',
            'parent_id.exists' => 'Selected parent category does not exist.',
        ];
    }
}