<?php

namespace App\Http\Controllers\Admin;

use App\Support\OrderWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Sales\Repositories\OrderCommentRepository;
use Webkul\Sales\Repositories\OrderRepository;

class OrderWorkflowController extends Controller
{
    /**
     * Create the workflow controller.
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderCommentRepository $orderCommentRepository
    ) {}

    /**
     * Apply a sequential, locked workflow transition and record its activity.
     */
    public function update(int $orderId): RedirectResponse
    {
        abort_unless(bouncer()->hasPermission('sales.orders.workflow'), 403);

        $data = request()->validate([
            'workflow_status' => ['required', 'string', 'in:packing,ready_to_ship,handed_to_courier,in_transit,out_for_delivery,delivered,failed_to_deliver'],
            'note' => ['required_if:workflow_status,failed_to_deliver', 'nullable', 'string', 'max:1000'],
            'expected_status' => ['required', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($data, $orderId) {
            $order = $this->orderRepository->scopeQuery(fn ($query) => $query->lockForUpdate())
                ->with(['payment', 'shipments', 'items'])->findOrFail($orderId);
            $current = OrderWorkflow::current($order);

            abort_unless($order->payment?->method === 'duitnowqr_manual', 422);
            abort_unless($data['expected_status'] === $current, 409, trans('admin::order-status.stale-order'));
            abort_unless(in_array($data['workflow_status'], OrderWorkflow::next($order), true), 422, trans('admin::order-status.invalid-transition'));

            if ($data['workflow_status'] === 'ready_to_ship') {
                abort_unless($order->workflow_awb_path, 422, trans('admin::order-status.awb-required'));
            }

            if ($data['workflow_status'] === 'handed_to_courier') {
                abort_if($order->canShip() || $order->shipments->isEmpty() || $order->shipments->contains(fn ($shipment) => ! trim((string) $shipment->track_number) || ! trim((string) $shipment->carrier_title)), 422, trans('admin::order-status.shipment-required'));
            }

            $this->orderRepository->update(['workflow_status' => $data['workflow_status']], $orderId);
            $note = trans('admin::order-status.history-entry', [
                'from' => trans('admin::order-status.'.$current),
                'to' => trans('admin::order-status.'.$data['workflow_status']),
            ]);

            if ($data['note'] ?? false) {
                $note .= ' — '.$data['note'];
            }

            $this->orderCommentRepository->create([
                'order_id' => $order->id,
                'comment' => $note,
                'customer_notified' => false,
            ]);
        });

        session()->flash('success', trans('admin::order-status.updated'));

        return redirect()->route('admin.sales.orders.view', $orderId);
    }

    /**
     * Store a carrier-issued AWB image for printing during packing.
     */
    public function uploadAwb(int $orderId): RedirectResponse
    {
        abort_unless(bouncer()->hasPermission('sales.orders.workflow'), 403);

        request()->validate(['awb' => ['required', 'file', 'extensions:jpg,jpeg,png,webp', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);

        $path = request()->file('awb')->store('order-awb', 'local');

        try {
            $oldPath = DB::transaction(function () use ($orderId, $path) {
                $order = $this->orderRepository->scopeQuery(fn ($query) => $query->lockForUpdate())->findOrFail($orderId);

                abort_unless(in_array(OrderWorkflow::current($order), ['packing', 'ready_to_ship'], true), 422, trans('admin::order-status.invalid-transition'));

                $oldPath = $order->workflow_awb_path;
                $this->orderRepository->update([
                    'workflow_awb_path' => $path,
                    'workflow_awb_name' => request()->file('awb')->getClientOriginalName(),
                ], $orderId);

                return $oldPath;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($path);

            throw $exception;
        }

        if ($oldPath && $oldPath !== $path) {
            Storage::disk('local')->delete($oldPath);
        }

        session()->flash('success', trans('admin::order-status.awb-saved'));

        return redirect()->route('admin.sales.orders.view', $orderId);
    }

    /**
     * Display the carrier label in a printable view.
     */
    public function printAwb(int $orderId)
    {
        abort_unless(bouncer()->hasPermission('sales.orders.workflow'), 403);

        $order = $this->orderRepository->findOrFail($orderId);

        abort_unless($order->workflow_awb_path, 404);

        return view('manpleasure-admin::order-awb', compact('order'));
    }

    /**
     * Stream the private carrier label only to an authorized administrator.
     */
    public function awbImage(int $orderId)
    {
        abort_unless(bouncer()->hasPermission('sales.orders.workflow'), 403);

        $order = $this->orderRepository->findOrFail($orderId);

        abort_unless($order->workflow_awb_path && Storage::disk('local')->exists($order->workflow_awb_path), 404);

        return Storage::disk('local')->response($order->workflow_awb_path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
