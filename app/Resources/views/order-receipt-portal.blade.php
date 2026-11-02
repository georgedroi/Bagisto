<x-shop::layouts>
    <x-slot:title>{{ trans('payment::app.receipt') }}</x-slot>
    <main class="msp-receipt-portal container mx-auto max-w-3xl px-4 py-8">
        <h1 class="text-xl font-semibold">{{ trans('admin::executive-layout.order-number') }} #{{ $order->increment_id }}</h1>
        @include('manpleasure-admin::customer-order-receipt', ['receiptUploadUrl' => request()->fullUrl()])
    </main>
</x-shop::layouts>
