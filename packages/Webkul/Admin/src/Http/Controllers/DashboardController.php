<?php

namespace Webkul\Admin\Http\Controllers;

use App\Http\Controllers\Admin\SellerWorkspaceController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Webkul\Admin\Helpers\Dashboard;

class DashboardController extends Controller
{
    /**
     * Request param functions
     */
    protected array $typeFunctions = [
        'over-all' => 'getOverAllStats',
        'today' => 'getTodayStats',
        'stock-threshold-products' => 'getStockThresholdProducts',
        'total-sales' => 'getSalesStats',
        'top-selling-products' => 'getTopSellingProducts',
        'top-customers' => 'getTopCustomers',
    ];

    /**
     * Show the combined dashboard using the seller workspace layout and data.
     */
    public function index(SellerWorkspaceController $workspace): View
    {
        return $workspace->overview();
    }

    /**
     * Display a listing of the resource.
     */
    public function stats(): JsonResponse
    {
        $function = $this->resolveTypeFunction();

        request()->validate([
            'start' => ['nullable', 'date_format:Y-m-d'],
            'end' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start'],
            'channel' => ['nullable', 'string', 'in:'.core()->getAllChannels()->pluck('code')->implode(',')],
        ]);

        $dashboard = app(Dashboard::class);

        $stats = $dashboard->{$function}();

        return response()->json([
            'statistics' => $stats,
            'date_range' => $dashboard->getDateRange(),
        ]);
    }

    /**
     * Resolve the requested type into a valid function name.
     */
    protected function resolveTypeFunction(): string
    {
        $type = request()->query('type');

        if (
            ! is_string($type)
            || ! array_key_exists($type, $this->typeFunctions)
        ) {
            abort(404);
        }

        return $this->typeFunctions[$type];
    }
}
