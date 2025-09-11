{{-- 
Shop System Checkbox Component
Usage: @include('shop::components.shop-checkbox', [
    'name' => 'field_name',
    'label' => 'Display Label',
    'description' => 'Help text (optional)',
    'checked' => $model->field_name ?? false,
    'activeText' => 'Active Text (optional)',
    'inactiveText' => 'Inactive Text (optional)'
])
--}}

@php
    $fieldId = $name ?? 'checkbox_field';
    $fieldName = $name ?? 'checkbox_field';
    $fieldLabel = $label ?? 'Checkbox Field';
    $fieldDescription = $description ?? null;
    $isChecked = old($fieldName, $checked ?? false);
    $activeText = $activeText ?? 'Active';
    $inactiveText = $inactiveText ?? 'Inactive';
@endphp

<div class="form-group">
    <label for="{{ $fieldId }}">{{ $fieldLabel }}</label>
    <div class="checkbox-wrapper shop-checkbox-wrapper" style="margin-top: 10px;">
        <input type="hidden" name="{{ $fieldName }}" value="0">
        <label class="checkbox-label shop-checkbox-label" style="font-weight: normal; cursor: pointer; display: flex; align-items: center;">
            <input type="checkbox" id="{{ $fieldId }}" name="{{ $fieldName }}" value="1" 
                   {{ $isChecked ? 'checked' : '' }}
                   class="shop-checkbox-input"
                   style="margin-right: 8px; transform: scale(1.3);"
                   data-active-text="{{ $activeText }}"
                   data-inactive-text="{{ $inactiveText }}">
            <span class="shop-checkbox-text">
                <strong>{{ $isChecked ? $activeText : $inactiveText }}</strong>
                @if($fieldDescription)
                    - {{ $fieldDescription }}
                @endif
            </span>
        </label>
    </div>
    @error($fieldName)
        <span class="text-danger">{{ $message }}</span>
    @enderror
</div>

@once
@push('head-styles')
<style>
    .shop-checkbox-wrapper {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 12px 15px;
        margin-bottom: 10px;
    }
    
    .shop-checkbox-label {
        margin-bottom: 0 !important;
        font-weight: normal;
        cursor: pointer;
    }
    
    .shop-checkbox-input {
        width: 18px;
        height: 18px;
        cursor: pointer;
        accent-color: #00a65a;
    }
    
    .shop-checkbox-text {
        font-size: 14px;
        line-height: 1.4;
    }
    
    .shop-checkbox-wrapper:hover {
        background: #f0f0f0;
        border-color: #ccc;
    }
    
    .shop-checkbox-active {
        color: #00a65a !important;
    }
    
    .shop-checkbox-inactive {
        color: #dd4b39 !important;
    }
</style>
@endpush

@push('footer-scripts')
<script>
$(document).ready(function() {
    // Initialize all shop checkboxes
    $('.shop-checkbox-input').on('change', function() {
        var $checkbox = $(this);
        var $span = $checkbox.closest('label').find('.shop-checkbox-text');
        var activeText = $checkbox.data('active-text');
        var inactiveText = $checkbox.data('inactive-text');
        
        if ($checkbox.is(':checked')) {
            $span.html('<strong class="shop-checkbox-active">' + activeText + '</strong>');
            $span.removeClass('shop-checkbox-inactive').addClass('shop-checkbox-active');
        } else {
            $span.html('<strong class="shop-checkbox-inactive">' + inactiveText + '</strong>');
            $span.removeClass('shop-checkbox-active').addClass('shop-checkbox-inactive');
        }
    });
    
    // Trigger change event on load for all checkboxes
    $('.shop-checkbox-input').trigger('change');
});
</script>
@endpush
@endonce
