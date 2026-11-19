@php
    $proofLink = \App\Support\WhatsAppMessage::proofLinkFor($order);
    $proofQr = \App\Support\WhatsAppMessage::proofQrFor($order);
@endphp

@if ($proofLink && $proofQr)
    <section class="mt-4 w-full" aria-label="{{ trans('payment::workflow.whatsapp-qr') }}" style="overflow-wrap: anywhere;">
        <h3 class="text-base font-semibold">{{ trans('payment::workflow.whatsapp-qr') }}</h3>
        <img
            data-whatsapp-proof-qr
            src="{{ $proofQr }}"
            alt="{{ trans('payment::workflow.whatsapp-qr') }}"
            width="240"
            height="240"
            class="mt-3 rounded-lg border border-zinc-200"
            style="display: block; width: 240px; max-width: 100%; height: auto; background: #fff;"
        >
        <p class="mt-3 text-sm">{{ trans('payment::workflow.whatsapp-qr-help') }}</p>
        <a href="{{ $proofLink }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-block rounded-xl px-6 py-3 text-center font-medium" style="background-color: #128c7e; color: #fff; min-height: 44px;">
            {{ trans('payment::app.send-proof-whatsapp') }}
        </a>
    </section>
@endif
