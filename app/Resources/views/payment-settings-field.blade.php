@php
    $fieldName = $field->getName();
    $fieldLabel = match ($fieldName) {
        'image' => trans('payment::settings.logo-label'),
        'qr_image' => trans('payment::settings.qr-label'),
        default => trans($field->getTitle()),
    };
    $fieldInfo = match ($fieldName) {
        'image' => trans('payment::settings.logo-help'),
        'qr_image' => trans('payment::settings.qr-upload-help'),
        default => trans($field->getInfo()),
    };
    $fieldAccept = in_array($fieldName, ['image', 'qr_image'], true) ? '.jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp' : '';
    $isWide = ! in_array($fieldName, ['active', 'sort', 'bank_name', 'account_name'], true);
@endphp

<div class="mp-payment-field {{ $isWide ? 'mp-payment-field-wide' : '' }}" data-payment-field="{{ $fieldName }}">
    @include('admin::configuration.field-type')
</div>
