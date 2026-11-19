<x-shop::layouts>
    <x-slot:title>{{ trans('payment::app.receipt') }}</x-slot>
    <main class="msp-receipt-portal container mx-auto max-w-3xl px-4 py-8">
        <h1 class="text-xl font-semibold">{{ trans('admin::executive-layout.order-number') }} #{{ $order->increment_id }}</h1>
        <p class="mt-2 text-lg font-semibold">{{ trans('shop::app.checkout.cart.summary.grand-total') }}: {{ core()->formatPrice($order->grand_total, $order->order_currency_code) }}</p>
        @foreach ($order->shipments as $shipment)
            <p class="mt-3 break-words" v-pre>{{ $shipment->carrier_title }} · {{ trans('payment::workflow.tracking-number') }}: {{ $shipment->track_number }}</p>
        @endforeach
        @include('manpleasure-admin::customer-order-receipt', ['receiptUploadUrl' => request()->fullUrl()])
        @if (in_array(\App\Support\OrderWorkflow::current($order), ['awaiting_payment', 'payment_clarification'], true))
            @include('manpleasure-admin::whatsapp-proof')
        @endif
    </main>
</x-shop::layouts>
