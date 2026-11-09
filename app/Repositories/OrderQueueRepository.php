<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
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
     * Saved workflow stages listed in the dashboard task list, by group.
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
            ->whereIn('orders.workflow_status', $stages)
            ->selectRaw('workflow_status, COUNT(*) as total')
            ->groupBy('orders.workflow_status')
            ->pluck('total', 'workflow_status');

        return collect($stages)->mapWithKeys(fn ($stage) => [$stage => (int) ($counts[$stage] ?? 0)])->all();
    }

    /**
     * Keep only open orders saved at one task list stage, as the task list counts them.
     */
    public function constrainWorkflow(Builder|EloquentBuilder $query, string $stage): Builder|EloquentBuilder
    {
        if (! in_array($stage, array_merge(...array_values(self::WORKFLOW_TASKS)), true)) {
            return $query;
        }

        return $query->whereNotIn('orders.status', [Order::STATUS_CANCELED, Order::STATUS_CLOSED, Order::STATUS_FRAUD])
            ->where('orders.workflow_status', $stage);
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

        return match ($queue) {
            'awaiting_payment' => $query->whereIn('orders.status', [Order::STATUS_PENDING, Order::STATUS_PENDING_PAYMENT])
                ->where(fn ($query) => $query->whereNull('orders.workflow_status')->orWhere('orders.workflow_status', 'awaiting_payment')),
            'to_ship' => $query->where(fn ($query) => $query
                ->whereIn('orders.workflow_status', ['confirmed', 'packing', 'ready_to_ship'])
                ->orWhere(fn ($query) => $query->whereNull('orders.workflow_status')
                    ->where('orders.status', Order::STATUS_PROCESSING)
                    ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('shipments')->whereColumn('shipments.order_id', 'orders.id')))),
            'shipping' => $query->where(fn ($query) => $query
                ->whereIn('orders.workflow_status', ['handed_to_courier', 'in_transit', 'out_for_delivery'])
                ->orWhere(fn ($query) => $query->whereNull('orders.workflow_status')
                    ->whereIn('orders.status', [Order::STATUS_PROCESSING, Order::STATUS_COMPLETED])
                    ->whereExists(fn ($query) => $query->selectRaw('1')->from('shipments')->whereColumn('shipments.order_id', 'orders.id')))),
            'failed_delivery' => $query->where('orders.workflow_status', 'failed_to_deliver'),
            'delivered' => $query->where('orders.workflow_status', 'delivered'),
        };
    }
}
