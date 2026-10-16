<?php

namespace Webkul\Payment\Repositories;

use Illuminate\Container\Container;
use Illuminate\Http\UploadedFile;
use Webkul\Core\Eloquent\Repository;
use Webkul\Payment\Contracts\PaymentReceipt;
use Webkul\Sales\Repositories\InvoiceRepository;
use Webkul\Sales\Repositories\OrderRepository;

class PaymentReceiptRepository extends Repository
{
    /**
     * Create a new repository instance.
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected InvoiceRepository $invoiceRepository,
        Container $container
    ) {
        parent::__construct($container);
    }

    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return PaymentReceipt::class;
    }

    /**
     * Upload and persist payment receipt.
     */
    public function uploadReceipt(int $orderId, UploadedFile $file): mixed
    {
        $path = $file->store('receipts', 'local');

        return $this->create([
            'order_id' => $orderId,
            'receipt_path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'status' => 'pending',
        ]);
    }

    /**
     * Approve receipt and generate invoice.
     */
    public function approveReceipt(int $receiptId): bool
    {
        $receipt = $this->findOrFail($receiptId);
        $order = $this->orderRepository->findOrFail($receipt->order_id);

        if ($order->canInvoice()) {
            $invoiceData = ['order_id' => $order->id];

            foreach ($order->items as $item) {
                $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
            }

            $this->invoiceRepository->create($invoiceData);
        }

        $this->update(['status' => 'approved'], $receiptId);

        return true;
    }

    /**
     * Reject receipt.
     */
    public function rejectReceipt(int $receiptId, ?string $notes = null): bool
    {
        $this->update([
            'status' => 'rejected',
            'admin_notes' => $notes,
        ], $receiptId);

        return true;
    }
}
