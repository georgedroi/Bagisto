<x-shop::layouts
	:has-header="true"
	:has-feature="false"
	:has-footer="true"
>
    <!-- Page Title -->
    <x-slot:title>
		@lang('shop::app.checkout.success.thanks')
    </x-slot>

	<!-- Page content -->
	<div class="container mt-8 px-[60px] max-lg:px-8">
		<div class="grid place-items-center gap-y-5 max-md:gap-y-2.5">
			{{ view_render_event('bagisto.shop.checkout.success.image.before', ['order' => $order]) }}

			<img
				class="max-md:h-[100px] max-md:w-[100px]"
				src="{{ bagisto_asset('images/thank-you.png') }}"
				alt="@lang('shop::app.checkout.success.thanks')"
				title="@lang('shop::app.checkout.success.thanks')"
                loading="lazy"
                decoding="async"
			>

			{{ view_render_event('bagisto.shop.checkout.success.image.after', ['order' => $order]) }}

			<p class="text-xl max-md:text-sm">
				@if (auth()->guard('customer')->user())
					<a class="text-blue-700" href="{{ route('shop.customers.account.orders.view', $order->id) }}">{{ trans('shop::app.checkout.success.order-id-info', ['order_id' => $order->increment_id]) }}</a>
				@else
					{{ trans('shop::app.checkout.success.order-id-info', ['order_id' => $order->increment_id]) }}
				@endif
			</p>

			<p class="font-medium md:text-2xl">
				@lang('shop::app.checkout.success.thanks')
			</p>

			<p class="text-xl text-zinc-500 max-md:text-center max-md:text-xs" v-pre>
				@if (! empty($order->checkout_message))
					{!! nl2br(e($order->checkout_message)) !!}
				@else
					@lang('shop::app.checkout.success.info')
				@endif
			</p>

			@php
				$instructions = \Webkul\Payment\Payment::getInstructions($order->payment?->method);
                $awaitingPayment = $order->payment?->method === 'duitnowqr_manual'
                    ? in_array(\App\Support\OrderWorkflow::current($order), ['awaiting_payment', 'payment_clarification'], true)
                    : ! \App\Support\WhatsAppMessage::paymentReceived($order);
			@endphp

            @if ($order->payment?->method === 'duitnowqr_manual')
                <p role="status" class="text-base font-semibold">{{ trans('admin::order-status.current-order-stage') }}: {{ trans('admin::order-status.'.\App\Support\OrderWorkflow::current($order)) }}</p>
            @endif

			@if ($awaitingPayment && (! empty($instructions['details']) || $instructions['qr_image']))
				<div
					class="w-full max-w-xl rounded-xl border border-zinc-200 p-5 max-md:p-4"
					v-pre
					style="text-align: left; background-color: var(--panel, #fafafa);"
				>
					<h3 class="text-lg font-medium max-md:text-base">
						@lang('payment::app.how-to-pay')
					</h3>

					@if (! empty($instructions['details']))
						<p class="mt-3 font-medium">
							{{ $instructions['details']['title'] }}
						</p>

						<div class="mt-1 break-words text-zinc-600">
                            @foreach (explode("\n", $instructions['details']['value']) as $line)
                                <p>{{ $line }}</p>
                            @endforeach
                        </div>
					@endif

					@if ($instructions['qr_image'])
						<img
							src="{{ $instructions['qr_image'] }}"
							alt="@lang('payment::app.duitnowqr-details')"
							class="mt-4 rounded-lg border border-zinc-200 p-2"
							style="width: 220px; height: 220px; background-color: #fff; object-fit: contain;"
						>

						<p class="mt-2 text-sm text-zinc-500">
							@lang('payment::app.duitnowqr-scan')
						</p>
					@endif

					<p class="mt-4 text-sm text-zinc-500">
						@lang('payment::app.payment-reference-note', ['order' => $order->increment_id])
					</p>

					@include('manpleasure-admin::whatsapp-proof')
				</div>
			@endif

			<p class="mt-2 font-semibold" v-pre>{{ trans('shop::app.checkout.onepage.summary.grand-total') }}: {{ core()->formatPrice($order->grand_total, $order->order_currency_code) }}</p>

			{!! view_render_event('bagisto.shop.checkout.success.continue-shopping.before', ['order' => $order]) !!}

			<a href="{{ route('shop.home.index') }}">
				<div class="w-max cursor-pointer rounded-2xl bg-navyBlue px-11 py-3 text-center text-base font-medium text-white max-md:rounded-lg max-md:px-6 max-md:py-1.5">
             		@lang('shop::app.checkout.cart.index.continue-shopping')
				</div>
			</a>

			{{ view_render_event('bagisto.shop.checkout.success.continue-shopping.after', ['order' => $order]) }}
		</div>
	</div>
</x-shop::layouts>
