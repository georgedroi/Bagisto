<?php

namespace Webkul\Payment\Repositories;

use App\Support\OrderWorkflow;
use Illuminate\Container\Container;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
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
     * Get the status of an order's most recent receipt.
     */
    public function latestStatus(int $orderId): ?string
    {
        return $this->orderBy('id', 'desc')->findWhere(['order_id' => $orderId], ['status'])->first()?->status;
    }

    /**
     * Upload and persist payment receipt.
     */
    public function uploadReceipt(int $orderId, UploadedFile $file): mixed
    {
        $path = null;

        try {
            return DB::transaction(function () use ($orderId, $file, &$path) {
                $order = $this->orderRepository->scopeQuery(fn ($query) => $query->lockForUpdate())
                    ->with('payment')->findOrFail($orderId);

                if ($order->payment?->method !== 'duitnowqr_manual' || ! in_array(OrderWorkflow::current($order), ['awaiting_payment', 'payment_clarification'], true)) {
                    throw ValidationException::withMessages(['receipt' => trans('admin::order-status.receipt-upload-unavailable')]);
                }

                $path = $file->store('receipts', 'local');
                $receipt = $this->create([
                    'order_id' => $orderId,
                    'receipt_path' => $path,
                    'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                    'status' => 'pending',
                ]);

                $this->orderRepository->update(['workflow_status' => 'awaiting_verification'], $orderId);

                return $receipt;
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }
    }

    /**
     * Approve receipt and generate invoice.
     */
    public function approveReceipt(int $receiptId): bool
    {
        return DB::transaction(function () use ($receiptId) {
            [$receipt, $order, $reviewable] = $this->reviewableReceipt($receiptId);

            if (! $reviewable) {
                return false;
            }

            if ($order->items->isEmpty() || ! $order->billing_address || (! $order->canInvoice() && $order->base_total_due > 0.01)) {
                return false;
            }

            if ($order->canInvoice()) {
                $invoiceData = ['order_id' => $order->id];

                foreach ($order->items as $item) {
                    $invoiceData['invoice']['items'][$item->id] = $item->qty_to_invoice;
                }

                $this->invoiceRepository->create($invoiceData);
            }

            $this->update(['status' => 'approved'], $receiptId);
            $this->orderRepository->update(['workflow_status' => 'confirmed'], $order->id);

            return true;
        });
    }

    /**
     * Reject receipt.
     */
    public function rejectReceipt(int $receiptId, ?string $notes = null): bool
    {
        return DB::transaction(function () use ($receiptId, $notes) {
            [$receipt, $order, $reviewable] = $this->reviewableReceipt($receiptId);

            if (! $reviewable) {
                return false;
            }

            $this->update(['status' => 'rejected', 'admin_notes' => $notes], $receiptId);
            $this->orderRepository->update(['workflow_status' => 'payment_clarification'], $order->id);

            return true;
        });
    }

    /**
     * Lock an order before its receipt and refuse stale or terminal reviews.
     */
    protected function reviewableReceipt(int $receiptId): array
    {
        $receipt = $this->findOrFail($receiptId);
        $order = $this->orderRepository->scopeQuery(fn ($query) => $query->lockForUpdate())
            ->with(['payment', 'items', 'addresses'])->findOrFail($receipt->order_id);
        $receipt = $this->scopeQuery(fn ($query) => $query->lockForUpdate())->findOrFail($receiptId);
        $latest = $this->scopeQuery(fn ($query) => $query->where('order_id', $order->id)->orderByDesc('id'))->first();
        $reviewable = $receipt->status === 'pending'
            && $latest?->id === $receipt->id
            && $order->payment?->method === 'duitnowqr_manual'
            && OrderWorkflow::current($order) === 'awaiting_verification';

        return [$receipt, $order, $reviewable];
    }
}
