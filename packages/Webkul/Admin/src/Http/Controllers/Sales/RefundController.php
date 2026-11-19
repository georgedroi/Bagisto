<?php

namespace Webkul\Admin\Http\Controllers\Sales;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Webkul\Admin\DataGrids\Sales\OrderRefundDataGrid;
use Webkul\Admin\Http\Controllers\Controller;
use Webkul\Sales\Exceptions\InvalidRefundQuantityException;
use Webkul\Sales\Repositories\OrderItemRepository;
use Webkul\Sales\Repositories\OrderRepository;
use Webkul\Sales\Repositories\RefundRepository;

class RefundController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        protected OrderRepository $orderRepository,
        protected OrderItemRepository $orderItemRepository,
        protected RefundRepository $refundRepository
    ) {}

    /**
     * Display a listing of the resource.
     *
     * @return View
     */
    public function index()
    {
        if (request()->ajax()) {
            return datagrid(OrderRefundDataGrid::class)->process();
        }

        return view('admin::sales.refunds.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create(int $orderId)
    {
        $order = $this->orderRepository->findOrFail($orderId);

        return view('admin::sales.refunds.create', compact('order'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store(int $orderId)
    {
        $order = $this->orderRepository->findOrFail($orderId);

        if (! $order->canRefund()) {
            session()->flash('error', trans('admin::app.sales.refunds.create.creation-error'));

            return redirect()->back();
        }

        $this->validate(request(), [
            'refund.items' => 'array',
            'refund.items.*' => 'required|numeric|min:0',
            'refund.shipping' => 'nullable|numeric|min:0',
            'refund.adjustment_refund' => 'nullable|numeric|min:0',
            'refund.adjustment_fee' => 'nullable|numeric|min:0',
        ]);

        $data = request()->all();

        foreach (['shipping', 'adjustment_refund', 'adjustment_fee'] as $field) {
            $data['refund'][$field] ??= 0;
        }

        try {
            $totals = $this->refundRepository->getOrderItemsRefundSummary($data['refund'], $orderId);

            if (! $totals) {
                throw new InvalidRefundQuantityException(trans('admin::app.sales.refunds.create.invalid-qty'));
            }
        } catch (InvalidRefundQuantityException $invalidRefundQuantityException) {
            session()->flash('error', $invalidRefundQuantityException->getMessage());

            return redirect()->back();
        }

        $maxShippingAmount = $this->refundRepository->getRefundableShippingAmount($order);

        if (round((float) $data['refund']['shipping'], 4) > $maxShippingAmount) {
            session()->flash('error', trans('admin::app.sales.refunds.create.refund-limit-error', [
                'amount' => core()->formatBasePrice((float) $data['refund']['shipping']),
            ]));

            return redirect()->back();
        }

        $maxRefundAmount = $this->refundRepository->getRefundableAmount($order);

        $refundAmount = round($totals['grand_total']['price'], 4);

        if ($refundAmount <= 0) {
            session()->flash('error', trans('admin::app.sales.refunds.create.invalid-refund-amount-error'));

            return redirect()->back();
        }

        if ($refundAmount > $maxRefundAmount) {
            session()->flash('error', trans('admin::app.sales.refunds.create.refund-limit-error', [
                'amount' => core()->formatBasePrice($maxRefundAmount),
            ]));

            return redirect()->back();
        }

        $this->refundRepository->create(array_merge($data, ['order_id' => $orderId]));

        session()->flash('success', trans('admin::app.sales.refunds.create.create-success'));

        return redirect()->route('admin.sales.orders.view', $orderId);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return JsonResponse|mixed
     */
    public function updateTotals(int $orderId)
    {
        try {
            $data = $this->refundRepository->getOrderItemsRefundSummary(request()->input(), $orderId);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 400);
        }

        return response()->json($data);
    }

    /**
     * Show the view for the specified resource.
     *
     * @param  int  $id
     * @return View
     */
    public function view($id)
    {
        $refund = $this->refundRepository->findOrFail($id);

        return view('admin::sales.refunds.view', compact('refund'));
    }
}
