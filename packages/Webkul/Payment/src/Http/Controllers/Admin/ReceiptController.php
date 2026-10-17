<?php

namespace Webkul\Payment\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Webkul\Payment\Payment\DuitNowQRManual;
use Webkul\Payment\Repositories\PaymentReceiptRepository;

class ReceiptController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        protected PaymentReceiptRepository $paymentReceiptRepository
    ) {}

    /**
     * Approve payment receipt.
     */
    public function approve(int $receiptId): RedirectResponse
    {
        if (! app(DuitNowQRManual::class)->isAvailable()) {
            throw new ServiceUnavailableHttpException;
        }

        $this->paymentReceiptRepository->approveReceipt($receiptId);

        session()->flash('success', trans('payment::app.receipt-approved-success'));

        return redirect()->back();
    }

    /**
     * Reject payment receipt.
     */
    public function reject(Request $request, int $receiptId): RedirectResponse
    {
        if (! app(DuitNowQRManual::class)->isAvailable()) {
            throw new ServiceUnavailableHttpException;
        }

        $notes = $request->input('notes');

        $this->paymentReceiptRepository->rejectReceipt($receiptId, $notes);

        session()->flash('success', trans('payment::app.receipt-rejected-success'));

        return redirect()->back();
    }
}
