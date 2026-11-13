<?php

namespace Webkul\Admin\DataGrids\Sales;

use App\Repositories\OrderQueueRepository;
use App\Support\OrderWorkflow;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Webkul\DataGrid\DataGrid;
use Webkul\RMA\Enums\DefaultRMAStatusEnum;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderAddress;
use Webkul\Sales\Repositories\OrderRepository;

class OrderDataGrid extends DataGrid
{
    /**
     * Prepare query builder.
     *
     * @return Builder
     */
    public function prepareQueryBuilder()
    {
        $queryBuilder = DB::table('orders')
            ->leftJoin('addresses as order_address_shipping', function ($leftJoin) {
                $leftJoin->on('order_address_shipping.order_id', '=', 'orders.id')
                    ->where('order_address_shipping.address_type', OrderAddress::ADDRESS_TYPE_SHIPPING);
            })
            ->leftJoin('addresses as order_address_billing', function ($leftJoin) {
                $leftJoin->on('order_address_billing.order_id', '=', 'orders.id')
                    ->where('order_address_billing.address_type', OrderAddress::ADDRESS_TYPE_BILLING);
            })
            ->leftJoin('order_payment', 'orders.id', '=', 'order_payment.order_id')
            ->select(
                'orders.id',
                DB::raw('GROUP_CONCAT('.DB::getTablePrefix().'order_payment.method SEPARATOR "|") as method'),
                'orders.increment_id',
                'orders.total_qty_ordered',
                'orders.base_grand_total',
                'orders.created_at',
                'orders.updated_at',
                'orders.shipping_title',
                'orders.shipping_method',
                'orders.shipping_description',
                DB::raw('(SELECT COUNT(*) FROM '.DB::getTablePrefix().'shipments WHERE shipments.order_id = '.DB::getTablePrefix().'orders.id) as shipment_count'),
                DB::raw('(SELECT COUNT(*) FROM '.DB::getTablePrefix().'rma WHERE rma.order_id = '.DB::getTablePrefix().'orders.id AND rma.rma_status_id NOT IN ('.implode(',', [DefaultRMAStatusEnum::RECEIVED_PACKAGE->value, DefaultRMAStatusEnum::SOLVED->value, DefaultRMAStatusEnum::DECLINED->value, DefaultRMAStatusEnum::ITEM_CANCELED->value, DefaultRMAStatusEnum::CANCELED->value]).')) as active_rma_count'),
                DB::raw('(SELECT GROUP_CONCAT(DISTINCT CONCAT_WS(": ", inventory_sources.name, shipments.carrier_title, shipments.track_number) SEPARATOR " | ") FROM '.DB::getTablePrefix().'shipments LEFT JOIN '.DB::getTablePrefix().'inventory_sources ON inventory_sources.id = shipments.inventory_source_id WHERE shipments.order_id = '.DB::getTablePrefix().'orders.id) as shipment_tracking'),
                'channel_name',
                'channel_id',
                'status',
                'workflow_status',
                'customer_email',
                'orders.cart_id as items',
                DB::raw('CONCAT('.DB::getTablePrefix().'orders.customer_first_name, " ", '.DB::getTablePrefix().'orders.customer_last_name) as full_name'),
                DB::raw('CONCAT('.DB::getTablePrefix().'order_address_billing.city, ", ", '.DB::getTablePrefix().'order_address_billing.state,", ", '.DB::getTablePrefix().'order_address_billing.country) as location')
            )
            ->selectRaw(app(OrderQueueRepository::class)->workflowStatusExpression().' as resolved_workflow_status')
            ->groupBy('orders.id');

        $queue = request()->query('queue');

        if (is_string($queue) && array_key_exists($queue, OrderQueueRepository::QUEUES)) {
            app(OrderQueueRepository::class)->constrainQueue($queryBuilder, $queue);
        } elseif (in_array($queue, [Order::STATUS_PENDING, Order::STATUS_PENDING_PAYMENT, Order::STATUS_PROCESSING, Order::STATUS_COMPLETED, Order::STATUS_CANCELED, Order::STATUS_CLOSED, Order::STATUS_FRAUD], true)) {
            $queryBuilder->where('orders.status', $queue);
        }

        $workflow = request()->query('workflow');

        if (in_array($workflow, ['awaiting_payment', 'awaiting_verification', 'payment_clarification', ...array_keys(OrderWorkflow::TRANSITIONS), 'delivered'], true)) {
            app(OrderQueueRepository::class)->constrainWorkflow($queryBuilder, $workflow);
        }

        $this->addFilter('full_name', DB::raw('CONCAT('.DB::getTablePrefix().'orders.customer_first_name, " ", '.DB::getTablePrefix().'orders.customer_last_name)'));
        $this->addFilter('created_at', 'orders.created_at');

        return $queryBuilder;
    }

    /**
     * Add columns.
     *
     * @return void
     */
    public function prepareColumns()
    {
        $this->addColumn([
            'index' => 'increment_id',
            'label' => trans('admin::app.sales.orders.index.datagrid.order-id'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'status',
            'label' => trans('admin::app.sales.orders.index.datagrid.status'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'filterable_type' => 'dropdown',
            'filterable_options' => [
                [
                    'label' => trans('admin::app.sales.orders.index.datagrid.processing'),
                    'value' => Order::STATUS_PROCESSING,
                ],
                [
                    'label' => trans('admin::app.sales.orders.index.datagrid.completed'),
                    'value' => Order::STATUS_COMPLETED,
                ],
                [
                    'label' => trans('admin::app.sales.orders.index.datagrid.canceled'),
                    'value' => Order::STATUS_CANCELED,
                ],
                [
                    'label' => trans('admin::app.sales.orders.index.datagrid.closed'),
                    'value' => Order::STATUS_CLOSED,
                ],
                [
                    'label' => trans('admin::app.sales.orders.index.datagrid.pending'),
                    'value' => Order::STATUS_PENDING,
                ],
                [
                    'label' => trans('admin::app.sales.orders.index.datagrid.pending-payment'),
                    'value' => Order::STATUS_PENDING_PAYMENT,
                ],
                [
                    'label' => trans('admin::app.sales.orders.index.datagrid.fraud'),
                    'value' => Order::STATUS_FRAUD,
                ],
            ],
            'sortable' => true,
            'closure' => function ($row) {
                if ((int) $row->active_rma_count > 0) {
                    return '<p class="label-pending">'.e(trans('admin::order-status.to-return-refund')).'</p>';
                }

                $workflowStatus = $row->resolved_workflow_status;
                $workflowLabel = [
                    'awaiting_payment' => ['pending-payment', 'label-pending'],
                    'awaiting_verification' => ['awaiting-verification', 'label-pending'],
                    'payment_clarification' => ['payment-clarification', 'label-canceled'],
                    'confirmed' => ['order-confirmed', 'label-active'],
                    'packing' => ['packing', 'label-processing'],
                    'ready_to_ship' => ['ready-to-ship', 'label-processing'],
                    'handed_to_courier' => ['handed-to-courier', 'label-processing'],
                    'in_transit' => ['in-transit', 'label-processing'],
                    'out_for_delivery' => ['out-for-delivery', 'label-processing'],
                    'failed_to_deliver' => ['failed-to-deliver', 'label-canceled'],
                    'delivered' => ['delivered', 'label-active'],
                ][$workflowStatus] ?? null;

                if ($workflowLabel && ! in_array($row->status, [Order::STATUS_CANCELED, Order::STATUS_CLOSED, Order::STATUS_FRAUD], true)) {
                    return '<p class="'.$workflowLabel[1].'">'.e(trans('admin::order-status.'.$workflowLabel[0])).'</p>';
                }

                [$label, $class] = match ($row->status) {
                    Order::STATUS_PENDING => [trans('admin::order-status.to-confirm'), 'label-pending'],
                    Order::STATUS_PENDING_PAYMENT => [trans('admin::order-status.pending-payment'), 'label-pending'],
                    Order::STATUS_PROCESSING => $row->shipment_count > 0
                        ? [trans('admin::order-status.shipping'), 'label-processing']
                        : [trans('admin::order-status.to-ship'), 'label-processing'],
                    Order::STATUS_COMPLETED => [trans('admin::order-status.shipping'), 'label-processing'],
                    Order::STATUS_CANCELED => [trans('admin::order-status.cancelled'), 'label-canceled'],
                    Order::STATUS_FRAUD => [trans('admin::app.sales.orders.index.datagrid.fraud'), 'label-canceled'],
                    Order::STATUS_CLOSED => [trans('admin::app.sales.orders.index.datagrid.closed'), 'label-closed'],
                    default => [(string) $row->status, 'label-pending'],
                };

                return '<p class="'.$class.'">'.e($label).'</p>';
            },
        ]);

        $this->addColumn([
            'index' => 'base_grand_total',
            'label' => trans('admin::app.sales.orders.index.datagrid.grand-total'),
            'type' => 'decimal',
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'method',
            'label' => trans('admin::app.sales.orders.index.datagrid.pay-via'),
            'type' => 'string',
            'closure' => function ($row) {
                return collect(explode('|', $row->method))
                    ->map(fn ($method) => core()->getConfigData('sales.payment_methods.'.$method.'.title'))
                    ->filter()
                    ->unique()
                    ->join(', ');
            },
        ]);

        $this->addColumn([
            'index' => 'channel_id',
            'label' => trans('admin::app.sales.orders.index.datagrid.channel-name'),
            'type' => 'string',
            'filterable' => true,
            'filterable_type' => 'dropdown',
            'filterable_options' => core()->getAllChannels()
                ->map(fn ($channel) => ['label' => $channel->name, 'value' => $channel->id])
                ->values()
                ->toArray(),
        ]);

        $this->addColumn([
            'index' => 'full_name',
            'label' => trans('admin::app.sales.orders.index.datagrid.customer'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'customer_email',
            'label' => trans('admin::app.sales.orders.index.datagrid.email'),
            'type' => 'string',
            'searchable' => true,
            'filterable' => true,
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'location',
            'label' => trans('admin::app.sales.orders.index.datagrid.location'),
            'type' => 'string',
        ]);

        $this->addColumn([
            'index' => 'items',
            'label' => trans('admin::app.sales.orders.index.datagrid.items'),
            'type' => 'string',
            'exportable' => false,
            'closure' => function ($value) {
                $order = app(OrderRepository::class)->with(['items.product.images'])->find($value->id);

                return view('manpleasure-admin::order-card-items', compact('order'))->render();
            },
        ]);

        $this->addColumn([
            'index' => 'created_at',
            'label' => trans('admin::app.sales.orders.index.datagrid.date'),
            'type' => 'date',
            'filterable' => true,
            'filterable_type' => 'date_range',
            'sortable' => true,
        ]);

        $this->addColumn([
            'index' => 'updated_at',
            'label' => trans('admin::app.sales.orders.index.datagrid.date'),
            'type' => 'date',
        ]);

        $this->addColumn([
            'index' => 'shipping_title',
            'label' => trans('admin::app.sales.orders.view.shipping-method'),
            'type' => 'string',
        ]);

        $this->addColumn([
            'index' => 'shipping_description',
            'label' => trans('admin::app.sales.orders.view.shipping-method'),
            'type' => 'string',
        ]);

        $this->addColumn([
            'index' => 'shipping_method',
            'label' => trans('admin::app.sales.orders.view.shipping-method'),
            'type' => 'string',
        ]);

        $this->addColumn([
            'index' => 'shipment_tracking',
            'label' => trans('admin::app.sales.shipments.view.tracking-number'),
            'type' => 'string',
        ]);
    }

    /**
     * Prepare actions.
     *
     * @return void
     */
    public function prepareActions()
    {
        if (bouncer()->hasPermission('sales.orders.view')) {
            $this->addAction([
                'icon' => 'icon-view',
                'title' => trans('admin::app.sales.orders.index.datagrid.view'),
                'method' => 'GET',
                'url' => function ($row) {
                    return route('admin.sales.orders.view', $row->id);
                },
            ]);
        }
    }
}
