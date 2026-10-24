@php
    $links = [
        'received' => \App\Support\WhatsAppMessage::linkFor($order, \App\Support\WhatsAppMessage::RECEIVED),
        'paid' => \App\Support\WhatsAppMessage::linkFor($order, \App\Support\WhatsAppMessage::PAID),
        'shipped' => \App\Support\WhatsAppMessage::linkFor($order, \App\Support\WhatsAppMessage::SHIPPED),
    ];

    $phone = \App\Support\WhatsAppMessage::phoneFor($order);

    $shipped = \App\Support\WhatsAppMessage::isShipped($order);

    $buttonStyle = 'display:block;margin-top:8px;padding:8px 12px;border-radius:6px;background:#128c7e;color:#fff;text-align:center;font-weight:600;text-decoration:none;';

    $disabledStyle = 'display:block;margin-top:8px;padding:8px 12px;border-radius:6px;background:#e5e7eb;color:#6b7280;text-align:center;font-weight:600;';
@endphp

<div class="box-shadow rounded bg-white p-4 dark:bg-gray-900" style="margin-bottom: 8px;">
    <p class="text-base font-semibold text-gray-600 dark:text-gray-300">
        @lang('admin::whatsapp.title')
    </p>

    @if ($phone)
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            @lang('admin::whatsapp.hint', ['phone' => '+'.$phone])
        </p>

        <a href="{{ $links['received'] }}" target="_blank" rel="noopener noreferrer" style="{{ $buttonStyle }}">
            @lang('admin::whatsapp.send-received')
        </a>

        <a href="{{ $links['paid'] }}" target="_blank" rel="noopener noreferrer" style="{{ $buttonStyle }}">
            @lang('admin::whatsapp.send-paid')
        </a>

        @if ($shipped)
            <a href="{{ $links['shipped'] }}" target="_blank" rel="noopener noreferrer" style="{{ $buttonStyle }}">
                @lang('admin::whatsapp.send-shipped')
            </a>
        @else
            <span style="{{ $disabledStyle }}">
                @lang('admin::whatsapp.send-shipped')
            </span>

            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                @lang('admin::whatsapp.not-shipped')
            </p>
        @endif
    @else
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            @lang('admin::whatsapp.no-phone')
        </p>
    @endif
</div>
