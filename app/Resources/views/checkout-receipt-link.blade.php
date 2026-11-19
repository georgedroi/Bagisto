<div class="mt-4 grid w-full max-w-xl gap-3">
    <a class="primary-button" href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('payment.orders.receipt.show', now()->addDays(30), ['id' => $order->id]) }}">@if (in_array(\App\Support\OrderWorkflow::current($order), ['awaiting_payment', 'payment_clarification'], true)){{ trans('payment::app.upload-receipt') }} · @endif{{ trans('payment::workflow.order-progress') }}</a>
</div>
