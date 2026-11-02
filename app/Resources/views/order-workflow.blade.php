@php
    $workflowStatus = \App\Support\OrderWorkflow::current($order);
    $nextStatuses = \App\Support\OrderWorkflow::next($order);
    $canManage = bouncer()->hasPermission('sales.orders.workflow');
    $stageClass = match ($workflowStatus) {
        'payment_clarification', 'failed_to_deliver' => 'msp-workflow-stage--issue',
        'confirmed', 'ready_to_ship', 'delivered' => 'msp-workflow-stage--confirmed',
        default => 'msp-workflow-stage--pending',
    };
@endphp

<x-admin::accordion class="msp-workflow mb-4">
    <x-slot:header>
        <p class="p-2.5 text-base font-semibold text-gray-600 dark:text-gray-300">{{ trans('admin::order-status.management') }}</p>
    </x-slot:header>
    <x-slot:content>
        <div class="grid gap-4">
            <div class="msp-workflow-stage rounded border p-3 {{ $stageClass }}">
                <p class="font-semibold text-gray-800 dark:text-white">{{ trans('admin::order-status.'.$workflowStatus) }}</p>
                @if (in_array($workflowStatus, ['awaiting_payment', 'awaiting_verification', 'payment_clarification'], true))
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ trans('admin::order-status.'.$workflowStatus.'-help') }}</p>
                @endif
            </div>

            @if ($order->payment->method === 'duitnowqr_manual')
                <div class="grid gap-3">
                    <h3 class="font-semibold text-gray-800 dark:text-white">{{ trans('admin::order-status.receipts') }}</h3>
                    @forelse ($receipts as $receipt)
                        <div class="rounded border border-gray-200 p-3 dark:border-gray-800">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    @if ($canManage)
                                        <a class="text-blue-600 hover:underline" href="{{ route('admin.sales.receipts.view', $receipt->id) }}">{{ $receipt->original_name ?: trans('payment::app.receipt') }}</a>
                                    @else
                                        <span>{{ $receipt->original_name ?: trans('payment::app.receipt') }}</span>
                                    @endif
                                    <p class="text-xs text-gray-500">{{ core()->formatDate($receipt->created_at, 'd M Y H:i') }} · {{ trans('admin::order-status.receipt-'.$receipt->status) }}</p>
                                    @if ($receipt->admin_notes)
                                        <p class="mt-1 text-sm text-red-700">{{ $receipt->admin_notes }}</p>
                                    @endif
                                </div>
                                @if ($canManage && $receipt->status === 'pending' && $workflowStatus === 'awaiting_verification' && $receipts->first()->id === $receipt->id)
                                    <div class="grid w-full min-w-0 gap-3">
                                        <form method="POST" action="{{ route('admin.sales.receipts.approve', $receipt->id) }}">@csrf<button class="primary-button min-h-[44px]">{{ trans('payment::app.approve') }}</button></form>
                                        <form method="POST" action="{{ route('admin.sales.receipts.reject', $receipt->id) }}" class="grid gap-2">
                                            @csrf
                                            <label for="receipt-notes-{{ $receipt->id }}" class="text-sm">{{ trans('payment::app.notes') }}</label>
                                            <input id="receipt-notes-{{ $receipt->id }}" name="notes" required maxlength="1000" class="min-h-[44px] w-full min-w-0 rounded border border-gray-300 px-3 py-2 dark:bg-gray-900">
                                            @error('notes')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                                            <button class="secondary-button min-h-[44px] w-fit">{{ trans('payment::app.reject') }}</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ trans('admin::order-status.no-receipts') }}</p>
                    @endforelse
                </div>
            @endif

            @if ($canManage && in_array($workflowStatus, ['packing', 'ready_to_ship'], true))
                <form method="POST" enctype="multipart/form-data" action="{{ route('admin.sales.orders.awb.upload', $order->id) }}" class="grid min-w-0 gap-2">
                    @csrf
                    <label for="order-awb" class="text-sm font-medium">{{ trans('admin::order-status.awb-label') }}</label>
                    <input id="order-awb" type="file" name="awb" accept="image/jpeg,image/png,image/webp" required class="max-w-full">
                    @error('awb')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    <p class="text-xs text-gray-500">{{ trans('admin::order-status.awb-help') }}</p>
                    <button class="secondary-button w-fit">{{ trans('admin::order-status.awb-upload') }}</button>
                </form>
            @endif

            @if ($canManage && $order->workflow_awb_path)
                <a href="{{ route('admin.sales.orders.awb.print', $order->id) }}" target="_blank" rel="noopener" class="secondary-button w-fit">{{ trans('admin::order-status.awb-print') }}</a>
            @endif

            @if ($canManage && $nextStatuses)
                <form method="POST" action="{{ route('admin.sales.orders.workflow.update', $order->id) }}" class="grid gap-2">
                    @csrf
                    <input type="hidden" name="expected_status" value="{{ $workflowStatus }}">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300" for="workflow_status">{{ trans('admin::order-status.next-step') }}</label>
                    <select id="workflow_status" name="workflow_status" required class="min-h-[44px] w-full min-w-0 rounded border border-gray-300 px-3 py-2 dark:bg-gray-900">
                        @foreach ($nextStatuses as $nextStatus)
                            <option value="{{ $nextStatus }}">{{ trans('admin::order-status.'.$nextStatus) }}</option>
                        @endforeach
                    </select>
                    <label for="order-workflow-note" class="text-sm">{{ trans('admin::order-status.optional-note') }}</label>
                    <input id="order-workflow-note" name="note" maxlength="1000" class="min-h-[44px] w-full min-w-0 rounded border border-gray-300 px-3 py-2 dark:bg-gray-900">
                    @error('note')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
                    <button class="primary-button min-h-[44px] w-fit">{{ trans('admin::order-status.save-step') }}</button>
                </form>
            @elseif (in_array($workflowStatus, ['awaiting_payment', 'awaiting_verification', 'payment_clarification'], true))
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ trans('admin::order-status.wait-for-payment-action') }}</p>
            @endif

            @if ($workflowStatus === 'ready_to_ship')
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ trans('admin::order-status.shipment-help') }}</p>
            @endif
        </div>
    </x-slot:content>
</x-admin::accordion>
