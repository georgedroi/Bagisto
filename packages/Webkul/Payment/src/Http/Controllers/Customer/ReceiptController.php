<?php

namespace Webkul\Payment\Http\Controllers\Customer;

use App\Support\OrderWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Webkul\Payment\Repositories\PaymentReceiptRepository;
use Webkul\Sales\Repositories\OrderRepository;

class ReceiptController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected PaymentReceiptRepository $paymentReceiptRepository
    ) {}

    /**
     * Upload customer payment receipt.
     */
    public function upload(Request $request, int $orderId): RedirectResponse
    {
        $request->validate([
            'receipt' => 'required|file|extensions:jpeg,jpg,png,webp|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $customer = auth()->guard('customer')->user();

        $order = $this->orderRepository->with('payment')->findOneWhere([
            'id' => $orderId,
            'customer_id' => $customer->id,
        ]);

        if (! $order) {
            session()->flash('error', trans('shop::app.customer.account.orders.view.order-not-found'));

            return redirect()->back();
        }

        if ($order->payment?->method !== 'duitnowqr_manual' || ! in_array(OrderWorkflow::current($order), ['awaiting_payment', 'payment_clarification'], true)) {
            abort(422, trans('admin::order-status.receipt-upload-unavailable'));
        }

        $this->paymentReceiptRepository->uploadReceipt($order->id, $request->file('receipt'));

        session()->flash('success', trans('payment::app.receipt-uploaded-success'));

        return redirect()->back();
    }

    /**
     * Show a private, signed receipt portal for customers including guest checkout.
     */
    public function showSigned(int $orderId)
    {
        $order = $this->orderRepository->with('payment')->findOrFail($orderId);

        abort_unless($order->payment?->method === 'duitnowqr_manual', 404);

        $receipts = $this->paymentReceiptRepository
            ->scopeQuery(fn ($query) => $query->where('order_id', $orderId)->orderByDesc('id'))->all();

        return view('manpleasure-admin::order-receipt-portal', compact('order', 'receipts'));
    }

    /**
     * Accept a receipt only when the signed order URL is valid.
     */
    public function uploadSigned(Request $request, int $orderId): RedirectResponse
    {
        $request->validate([
            'receipt' => 'required|file|extensions:jpeg,jpg,png,webp|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        $this->paymentReceiptRepository->uploadReceipt($orderId, $request->file('receipt'));

        session()->flash('success', trans('payment::app.receipt-uploaded-success'));

        return redirect()->to($request->fullUrl());
    }
}
