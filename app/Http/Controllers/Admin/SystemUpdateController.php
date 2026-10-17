<?php

namespace App\Http\Controllers\Admin;

use App\Services\Updates\DeploymentOperations;
use App\Services\Updates\ReleaseCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;
use Webkul\Admin\Http\Controllers\Controller;

class SystemUpdateController extends Controller
{
    /**
     * Display current release and connection status without starting an update.
     */
    public function index(Request $request, DeploymentOperations $operations): View
    {
        return view('manpleasure-admin::system-updates', [
            'installed' => $operations->installed(),
            'capabilities' => $operations->capabilities(),
            'activeOperation' => $request->session()->get('manpleasure_updates.active_operation'),
        ]);
    }

    /**
     * Check published project releases and bind installation to this session's result.
     */
    public function check(Request $request, ReleaseCatalog $catalog, DeploymentOperations $operations): JsonResponse
    {
        $request->session()->forget('manpleasure_updates.candidate');

        try {
            $candidate = $catalog->latest();

            if ($candidate === null) {
                return response()->json(['message' => trans('admin::system-updates.no-release'), 'candidate' => null, 'capabilities' => $operations->capabilities()]);
            }

            $candidate['token'] = Str::random(40);
            $candidate['checked_at'] = time();
            $installed = $operations->installed();
            $candidate['available'] = $candidate['release_id'] !== $installed['release_id']
                && (! preg_match('/^\d{4}\.\d{1,2}\.\d{1,2}\.\d{1,4}$/D', $installed['version'])
                    || version_compare($candidate['version'], $installed['version'], '>'));
            $request->session()->put('manpleasure_updates.candidate', $candidate);

            return response()->json([
                'candidate' => $candidate,
                'capabilities' => $operations->capabilities(),
                'message' => trans('admin::system-updates.'.($candidate['available'] ? 'available' : 'up-to-date')),
            ]);
        } catch (Throwable) {
            return response()->json(['message' => trans('admin::system-updates.failed')], 502);
        }
    }

    /**
     * Enqueue only the recent release that the configured owner checked.
     */
    public function install(Request $request, DeploymentOperations $operations): JsonResponse
    {
        $data = $request->validate(['token' => 'required|string|size:40']);
        $candidate = $request->session()->get('manpleasure_updates.candidate');

        if (! is_array($candidate) || empty($candidate['available']) || time() - ($candidate['checked_at'] ?? 0) > 900
            || ! hash_equals((string) ($candidate['token'] ?? ''), $data['token'])) {
            return response()->json(['message' => trans('admin::system-updates.check-again')], 409);
        }

        return $this->enqueue($request, $operations, 'install', $candidate);
    }

    /**
     * Request the worker's recorded previous release without accepting a target path.
     */
    public function rollback(Request $request, DeploymentOperations $operations): JsonResponse
    {
        return $this->enqueue($request, $operations, 'rollback');
    }

    /**
     * Poll an operation originally submitted by this authenticated session.
     */
    public function status(Request $request, string $operation, DeploymentOperations $operations): JsonResponse
    {
        abort_unless(in_array($operation, $request->session()->get('manpleasure_updates.operations', []), true), 404);

        $status = $operations->status($operation);

        if ($status['terminal'] && $request->session()->get('manpleasure_updates.active_operation') === $operation) {
            $request->session()->forget('manpleasure_updates.active_operation');
        }

        return response()->json($status + ['capabilities' => $operations->capabilities()]);
    }

    /**
     * Record an operation for the polling session after the worker accepts a request.
     */
    protected function enqueue(Request $request, DeploymentOperations $operations, string $action, ?array $candidate = null): JsonResponse
    {
        try {
            $operation = $operations->enqueue($action, $candidate, (int) auth('admin')->id());
            $ids = $request->session()->get('manpleasure_updates.operations', []);
            $ids[] = $operation;
            $request->session()->put('manpleasure_updates.operations', array_slice($ids, -20));
            $request->session()->put('manpleasure_updates.active_operation', $operation);
            $request->session()->forget('manpleasure_updates.candidate');

            return response()->json([
                'operation' => $operation,
                'status_url' => route('admin.settings.system_updates.status', $operation),
                'message' => trans('admin::system-updates.queued'),
            ], 202);
        } catch (Throwable) {
            return response()->json(['message' => trans('admin::system-updates.unavailable')], 409);
        }
    }
}
