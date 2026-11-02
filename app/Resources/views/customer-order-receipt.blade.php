@if ($order->payment->method === 'duitnowqr_manual')
    <section class="msp-payment-proof mt-8 rounded-lg border border-zinc-200 p-5" aria-labelledby="payment-receipt-title">
        <h2 id="payment-receipt-title" class="text-lg font-semibold text-zinc-900">{{ trans('admin::order-status.payment-proof-title') }}</h2>
        <p class="mt-1 text-sm text-zinc-600">{{ trans('admin::order-status.payment-proof-help') }}</p>
        <p class="mt-2 text-sm font-semibold text-zinc-800">{{ trans('admin::order-status.current-order-stage') }}: {{ trans('admin::order-status.'.\App\Support\OrderWorkflow::current($order)) }}</p>

        @if (in_array(\App\Support\OrderWorkflow::current($order), ['awaiting_payment', 'payment_clarification'], true))
            <form method="POST" action="{{ $receiptUploadUrl ?? route('customer.orders.receipt.upload', $order->id) }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
                @csrf
                <label class="grid gap-1 text-sm">
                    <span>{{ trans('payment::app.receipt') }}</span>
                    <input type="file" name="receipt" accept="image/jpeg,image/png,image/webp" required class="max-w-full min-w-0">
                </label>
                <button class="primary-button min-h-[44px]">{{ trans('payment::app.upload-receipt') }}</button>
                @error('receipt')<p class="w-full text-sm text-red-700">{{ $message }}</p>@enderror
            </form>
        @endif

        @if ($receipts->isNotEmpty())
            <ul class="mt-4 grid gap-2">
                @foreach ($receipts as $receipt)
                    <li class="text-sm text-zinc-700">{{ $receipt->original_name ?: trans('payment::app.receipt') }} · {{ trans('admin::order-status.receipt-'.$receipt->status) }} @if ($receipt->admin_notes) — {{ $receipt->admin_notes }} @endif</li>
                @endforeach
            </ul>
        @endif
    </section>
    @pushOnce('styles')
        <style>
            .msp-receipt-portal { width: 100%; max-width: 48rem; margin: auto; padding: 32px 16px; }
            .msp-payment-proof { max-width: 100%; margin-top: 24px; border: 1px solid #e4e4e7; border-radius: 8px; padding: 20px; overflow-wrap: anywhere; }
            .msp-payment-proof form { display: flex; flex-wrap: wrap; align-items: end; gap: 12px; margin-top: 16px; }
            .msp-payment-proof label { display: grid; gap: 6px; min-width: 0; max-width: 100%; }
            .msp-payment-proof input { min-width: 0; max-width: 100%; min-height: 44px; }
            .msp-payment-proof button { min-height: 44px; }
        </style>
    @endPushOnce
@endif
