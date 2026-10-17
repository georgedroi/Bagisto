<?php

namespace Webkul\Payment\Http\Controllers\Customer;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Webkul\Payment\Payment\DuitNowQRManual;
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
        if (! app(DuitNowQRManual::class)->isAvailable()) {
            throw new ServiceUnavailableHttpException;
        }

        $request->validate([
            'receipt' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
        ]);

        $customer = auth()->guard('customer')->user();

        $order = $this->orderRepository->findOneWhere([
            'id' => $orderId,
            'customer_id' => $customer->id,
        ]);

        if (! $order) {
            session()->flash('error', trans('shop::app.customer.account.orders.view.order-not-found'));

            return redirect()->back();
        }

        $this->paymentReceiptRepository->uploadReceipt($order->id, $request->file('receipt'));

        session()->flash('success', trans('payment::app.receipt-uploaded-success'));

        return redirect()->back();
    }
}
