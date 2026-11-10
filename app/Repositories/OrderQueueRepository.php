<?php

namespace App\Repositories;

use App\Support\OrderWorkflow;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\RMA\Enums\DefaultRMAStatusEnum;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Repositories\OrderRepository;

class OrderQueueRepository extends OrderRepository
{
    /**
     * Operational queues shared by the dashboard and order list.
     */
    public const QUEUES = [
        'awaiting_payment' => 'admin::executive-layout.unpaid',
        'to_ship' => 'admin::order-status.to-ship',
        'shipping' => 'admin::order-status.shipping',
        'failed_delivery' => 'admin::order-status.failed-to-deliver',
        'delivered' => 'admin::order-status.delivered',
        'returns' => 'admin::order-status.to-return-refund',
    ];

    /**
     * Workflow stages listed in the dashboard task list, by group.
     */
    public const WORKFLOW_TASKS = [
        'task-payments' => ['awaiting_verification', 'payment_clarification'],
        'task-fulfilment' => ['confirmed', 'packing', 'ready_to_ship', 'out_for_delivery'],
    ];

    /**
     * Count each task list stage using the same constraints as the order list workflow filter.
     */
    public function workflowCounts(): array
    {
        $stages = array_merge(...array_values(self::WORKFLOW_TASKS));

        $counts = $this->model->newQuery()
            ->whereNotIn('orders.status', [Order::STATUS_CANCELED, Order::STATUS_CLOSED, Order::STATUS_FRAUD])
            ->whereIn(DB::raw($this->workflowStatusExpression()), $stages)
            ->selectRaw($this->workflowStatusExpression().' as resolved_workflow_status, COUNT(*) as total')
            ->groupBy('resolved_workflow_status')
            ->pluck('total', 'resolved_workflow_status');

        return collect($stages)->mapWithKeys(fn ($stage) => [$stage => (int) ($counts[$stage] ?? 0)])->all();
    }

    /**
     * Keep only open orders at one resolved stage, as the task list counts them.
     */
    public function constrainWorkflow(Builder|EloquentBuilder $query, string $stage): Builder|EloquentBuilder
    {
        if (! in_array($stage, ['awaiting_payment', 'awaiting_verification', 'payment_clarification', ...array_keys(OrderWorkflow::TRANSITIONS), 'delivered'], true)) {
            return $query;
        }

        return $query->whereNotIn('orders.status', [Order::STATUS_CANCELED, Order::STATUS_CLOSED, Order::STATUS_FRAUD])
            ->whereRaw($this->workflowStatusExpression().' = ?', [$stage]);
    }

    /**
     * Count each queue using the same constraints as its destination page.
     */
    public function queueCounts(): array
    {
        $counts = [];

        foreach (array_keys(self::QUEUES) as $queue) {
            $counts[$queue] = $this->constrainQueue($this->model->newQuery(), $queue)->count();
        }

        return $counts;
    }

    /**
     * Apply operational stages while keeping invoice completion separate from delivery.
     */
    public function constrainQueue(Builder|EloquentBuilder $query, string $queue): Builder|EloquentBuilder
    {
        if (! array_key_exists($queue, self::QUEUES)) {
            return $query;
        }

        if ($queue === 'returns') {
            return $query->where(fn ($query) => $query
                ->whereIn('orders.status', [Order::STATUS_CANCELED, Order::STATUS_CLOSED])
                ->orWhereExists(fn ($query) => $query->selectRaw('1')->from('rma')
                    ->whereColumn('rma.order_id', 'orders.id')
                    ->whereNotIn('rma.rma_status_id', [
                        DefaultRMAStatusEnum::RECEIVED_PACKAGE->value,
                        DefaultRMAStatusEnum::SOLVED->value,
                        DefaultRMAStatusEnum::DECLINED->value,
                        DefaultRMAStatusEnum::ITEM_CANCELED->value,
                        DefaultRMAStatusEnum::CANCELED->value,
                    ])));
        }

        $query->whereNotIn('orders.status', [Order::STATUS_CANCELED, Order::STATUS_CLOSED, Order::STATUS_FRAUD]);
        $workflow = DB::raw($this->workflowStatusExpression());

        return match ($queue) {
            'awaiting_payment' => $query->whereIn('orders.status', [Order::STATUS_PENDING, Order::STATUS_PENDING_PAYMENT])
                ->where(fn ($query) => $query->whereNull($workflow)->orWhere($workflow, 'awaiting_payment')),
            'to_ship' => $query->where(fn ($query) => $query
                ->whereIn($workflow, ['confirmed', 'packing', 'ready_to_ship'])
                ->orWhere(fn ($query) => $query->whereNull($workflow)
                    ->where('orders.status', Order::STATUS_PROCESSING)
                    ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('shipments')->whereColumn('shipments.order_id', 'orders.id')))),
            'shipping' => $query->where(fn ($query) => $query
                ->whereIn($workflow, ['handed_to_courier', 'in_transit', 'out_for_delivery'])
                ->orWhere(fn ($query) => $query->whereNull($workflow)
                    ->whereIn('orders.status', [Order::STATUS_PROCESSING, Order::STATUS_COMPLETED])
                    ->whereExists(fn ($query) => $query->selectRaw('1')->from('shipments')->whereColumn('shipments.order_id', 'orders.id')))),
            'failed_delivery' => $query->where($workflow, 'failed_to_deliver'),
            'delivered' => $query->where($workflow, 'delivered'),
        };
    }

    /**
     * Resolve legacy DuitNow stages for filtering and counting without loading orders individually.
     */
    public function workflowStatusExpression(): string
    {
        $grammar = $this->model->getConnection()->getQueryGrammar();
        $orders = $grammar->wrapTable('orders');
        $payments = $grammar->wrapTable('order_payment');
        $receipts = $grammar->wrapTable('payment_receipts');
        $shipments = $grammar->wrapTable('shipments');

        return <<<SQL
            COALESCE(NULLIF({$orders}.workflow_status, ''), CASE
                WHEN EXISTS (SELECT 1 FROM {$payments} WHERE {$payments}.order_id = {$orders}.id AND {$payments}.method = 'duitnowqr_manual') THEN CASE
                    WHEN {$orders}.base_grand_total_invoiced > 0 AND {$orders}.base_grand_total - {$orders}.base_grand_total_invoiced <= 0.01 THEN CASE
                        WHEN EXISTS (SELECT 1 FROM {$shipments} WHERE {$shipments}.order_id = {$orders}.id) THEN 'handed_to_courier'
                        ELSE 'confirmed'
                    END
                    ELSE CASE (SELECT {$receipts}.status FROM {$receipts} WHERE {$receipts}.order_id = {$orders}.id ORDER BY {$receipts}.id DESC LIMIT 1)
                        WHEN 'pending' THEN 'awaiting_verification'
                        WHEN 'rejected' THEN 'payment_clarification'
                        WHEN 'approved' THEN 'confirmed'
                        ELSE 'awaiting_payment'
                    END
                END
            END)
            SQL;
    }
}
