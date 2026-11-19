@php
    $manualMethods = \App\Support\ManualPaymentPolicy::METHODS;
    $methodIcons = ['duitnowqr_manual' => 'icon-image', 'banktransfer' => 'icon-store', 'cryptotransfer' => 'icon-sales'];
    $methodHelp = ['duitnowqr_manual' => 'qr-help', 'banktransfer' => 'bank-help', 'cryptotransfer' => 'crypto-help'];
    $receivingHelp = ['duitnowqr_manual' => 'qr-required', 'banktransfer' => 'bank-required', 'cryptotransfer' => 'wallet-required'];
    $paymentChildren = $activeConfiguration->getChildren()->keyBy(fn ($item) => $item->getKey());
@endphp

@include('manpleasure-admin::payment-settings-styles')

<div class="mp-payment-settings">
    <div class="mp-payment-intro">
        <p>{{ trans('payment::settings.intro') }}</p>
        <span class="mp-payment-policy">{{ trans('payment::settings.manual-only') }}</span>
    </div>

    <div class="mp-payment-layout">
        <div class="mp-payment-methods">
            @foreach ($manualMethods as $method)
                @php
                    $child = $paymentChildren->get('sales.payment_methods.'.$method);
                    $fields = $child->getFields()->keyBy(fn ($item) => $item->getName());
                    $isActive = (bool) old($child->getKey().'.active', system_config()->getConfigData($child->getKey().'.active', $currentChannel->code, $currentLocale->code));
                    $hasErrors = collect($errors->keys())->contains(fn ($key) => str_starts_with($key, $child->getKey().'.'));
                    $groups = [
                        'receiving-details' => ['qr_image', 'bank_name', 'account_name', 'account_number', 'btc_address', 'usdt_trc20_address', 'usdt_erc20_address'],
                        'customer-display' => ['title', 'description', 'image'],
                        'instructions' => ['instructions'],
                    ];
                @endphp

                <details class="mp-payment-card" data-payment-method="{{ $method }}" @if ($method === 'duitnowqr_manual' || $isActive || $hasErrors) open @endif>
                    <summary>
                        <span class="mp-payment-icon {{ $methodIcons[$method] }}" aria-hidden="true"></span>
                        <span class="mp-payment-heading">
                            <span class="mp-payment-title">{{ $child->getName() }}</span>
                            <span class="mp-payment-subtitle">{{ trans('payment::settings.'.$methodHelp[$method]) }}</span>
                        </span>
                        <span class="mp-payment-status mp-payment-status-off">{{ trans('payment::settings.disabled') }}</span>
                        <span class="mp-payment-status mp-payment-status-on">{{ trans('payment::settings.enabled') }}</span>
                        <span class="mp-payment-chevron icon-arrow-down" aria-hidden="true"></span>
                    </summary>

                    <div class="mp-payment-body">
                        <div class="mp-payment-options mp-payment-grid">
                            @foreach (['active', 'sort'] as $fieldName)
                                @include('manpleasure-admin::payment-settings-field', ['field' => $fields->get($fieldName)])
                            @endforeach
                        </div>

                        @foreach ($groups as $group => $fieldNames)
                            <section class="mp-payment-group">
                                @if ($group !== 'instructions')
                                    <h2>{{ trans('payment::settings.'.$group) }}</h2>
                                @endif
                                @if ($group === 'receiving-details')
                                    <p class="mp-payment-receiving-help">{{ trans('payment::setup.'.$receivingHelp[$method]) }}</p>
                                @endif
                                <div class="mp-payment-grid">
                                    @foreach ($fieldNames as $fieldName)
                                        @if ($fields->has($fieldName))
                                            @include('manpleasure-admin::payment-settings-field', ['field' => $fields->get($fieldName)])
                                        @endif
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                </details>
            @endforeach
        </div>

        <aside class="mp-payment-guide">
            <h2>{{ trans('payment::settings.guide-title') }}</h2>
            <ol>
                <li>{{ trans('payment::settings.guide-enable') }}</li>
                <li>{{ trans('payment::settings.guide-details') }}</li>
                <li>{{ trans('payment::settings.guide-save') }}</li>
            </ol>
            <div class="mp-payment-guide-note">
                <span class="icon-mail" aria-hidden="true"></span>
                <p>{{ trans('payment::settings.whatsapp-auto') }}</p>
            </div>
        </aside>
    </div>

    @foreach ($paymentChildren as $child)
        @unless (in_array($child->getKey(), array_map(fn ($method) => 'sales.payment_methods.'.$method, $manualMethods), true))
            @foreach ($child->getFields() as $field)
                @if ($field->getName() === 'active')
                    <input type="hidden" name="{{ $field->getNameField() }}" value="0">
                @endif
            @endforeach
        @endunless
    @endforeach
</div>
