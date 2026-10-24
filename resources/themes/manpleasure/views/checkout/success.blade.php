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
					@lang('shop::app.checkout.success.order-id-info', [
						'order_id' => '<a class="text-blue-700" href="'.route('shop.customers.account.orders.view', $order->id).'">'.$order->increment_id.'</a>'
					])
				@else
					@lang('shop::app.checkout.success.order-id-info', ['order_id' => $order->increment_id])
				@endif
			</p>

			<p class="font-medium md:text-2xl">
				@lang('shop::app.checkout.success.thanks')
			</p>

			<p class="text-xl text-zinc-500 max-md:text-center max-md:text-xs">
				@if (! empty($order->checkout_message))
					{!! nl2br($order->checkout_message) !!}
				@else
					@lang('shop::app.checkout.success.info')
				@endif
			</p>

			@php
				$instructions = \Webkul\Payment\Payment::getInstructions($order->payment?->method);
			@endphp

			@if (! empty($instructions['details']) || $instructions['qr_image'])
				<div
					class="w-full max-w-xl rounded-xl border border-zinc-200 p-5 max-md:p-4"
					style="text-align: left; background-color: #fafafa;"
				>
					<h3 class="text-lg font-medium max-md:text-base">
						@lang('payment::app.how-to-pay')
					</h3>

					@if (! empty($instructions['details']))
						<p class="mt-3 font-medium">
							{{ $instructions['details']['title'] }}
						</p>

						<p class="mt-1 break-words text-zinc-600" style="white-space: pre-line;">{{ $instructions['details']['value'] }}</p>
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

					@if (config('manpleasure-contact.whatsapp'))
						<a
							href="https://wa.me/{{ config('manpleasure-contact.whatsapp') }}?text={{ rawurlencode(trans('payment::app.whatsapp-proof-message', ['order' => '#'.$order->increment_id])) }}"
							target="_blank"
							rel="noopener noreferrer"
							class="mt-4 inline-block rounded-xl px-6 py-3 text-center text-base font-medium"
							style="background-color: #128c7e; color: #fff; text-decoration: none;"
						>
							@lang('payment::app.send-proof-whatsapp')
						</a>
					@endif
				</div>
			@endif

			{{ view_render_event('bagisto.shop.checkout.success.continue-shopping.before', ['order' => $order]) }}

			<a href="{{ route('shop.home.index') }}">
				<div class="w-max cursor-pointer rounded-2xl bg-navyBlue px-11 py-3 text-center text-base font-medium text-white max-md:rounded-lg max-md:px-6 max-md:py-1.5">
             		@lang('shop::app.checkout.cart.index.continue-shopping')
				</div>
			</a>

			{{ view_render_event('bagisto.shop.checkout.success.continue-shopping.after', ['order' => $order]) }}
		</div>
	</div>
</x-shop::layouts>
