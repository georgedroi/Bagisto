<?php

namespace Webkul\Payment\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
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
        abort_unless(bouncer()->hasPermission('sales.orders.workflow'), 403);

        abort_unless($this->paymentReceiptRepository->approveReceipt($receiptId), 422, trans('admin::order-status.receipt-already-processed'));

        session()->flash('success', trans('payment::app.receipt-approved-success'));

        return redirect()->back();
    }

    /**
     * Reject payment receipt.
     */
    public function reject(Request $request, int $receiptId): RedirectResponse
    {
        abort_unless(bouncer()->hasPermission('sales.orders.workflow'), 403);

        $notes = $request->validate(['notes' => ['required', 'string', 'max:1000']])['notes'];

        abort_unless($this->paymentReceiptRepository->rejectReceipt($receiptId, $notes), 422, trans('admin::order-status.receipt-already-processed'));

        session()->flash('success', trans('payment::app.receipt-rejected-success'));

        return redirect()->back();
    }

    /**
     * Download a private receipt for authorized payment review.
     */
    public function view(int $receiptId)
    {
        abort_unless(bouncer()->hasPermission('sales.orders.workflow'), 403);

        $receipt = $this->paymentReceiptRepository->findOrFail($receiptId);

        abort_unless(Storage::disk('local')->exists($receipt->receipt_path), 404);

        return Storage::disk('local')->download($receipt->receipt_path, $receipt->original_name ?: basename($receipt->receipt_path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
