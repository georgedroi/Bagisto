<?php

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Webkul\Core\Exceptions\Handler;

/**
 * Render the exception through the storefront handler as it is registered when debugging is off.
 */
function renderWithoutDebug(Throwable $exception, bool $json = false, string $path = '/qa-error')
{
    config(['app.debug' => false]);

    $handler = new Handler(app());
    $handler->register();

    $request = Request::create($path, 'POST', [], [], [], $json ? ['HTTP_ACCEPT' => 'application/json'] : []);
    app()->instance('request', $request);

    return $handler->render($request, $exception);
}

it('keeps the status of a client error the storefront has no page for', function (Throwable $exception, int $status, bool $json) {
    expect(renderWithoutDebug($exception, $json)->getStatusCode())->toBe($status);
})->with([
    'refused step as a page' => [new HttpException(422, 'QA refused step'), 422, false],
    'refused step as JSON' => [new HttpException(422, 'QA refused step'), 422, true],
    'wrong method' => [new MethodNotAllowedHttpException(['GET']), 405, false],
    'conflict' => [new HttpException(409, 'QA conflict'), 409, true],
    'expired form' => [new TokenMismatchException('CSRF token mismatch.'), 419, false],
    'too many attempts' => [new ThrottleRequestsException('Too Many Attempts.'), 429, true],
]);

it('tells a JSON client why its request was refused', function () {
    expect(renderWithoutDebug(new HttpException(422, 'QA refused step'), true)->getData(true)['message'])->toBe('QA refused step');
});

it('sends the response an HttpResponseException carries', function () {
    $response = renderWithoutDebug(new HttpResponseException(new JsonResponse(['qa' => 'kept'], 403)), true);

    expect($response->getStatusCode())->toBe(403)
        ->and($response->getData(true))->toBe(['qa' => 'kept']);
});

it('still shows the store error pages for the statuses it has them for', function (int $status) {
    $response = renderWithoutDebug(new HttpException($status), true);

    expect($response->getStatusCode())->toBe($status)
        ->and($response->getData(true)['error'])->toBe(trans("shop::app.errors.{$status}.title"));
})->with([401, 403, 404, 503]);

it('still answers validation failures with the field errors', function () {
    $exception = new ValidationException(Validator::make([], ['email' => 'required']));

    $response = renderWithoutDebug($exception, true);

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true)['errors'])->toHaveKey('email');
});

it('still hides server failures behind a generic 500', function (bool $json) {
    $response = renderWithoutDebug(new RuntimeException('QA internal secret'), $json);

    expect($response->getStatusCode())->toBe(500)
        ->and($response->getContent())->not->toContain('QA internal secret');
})->with(['page' => false, 'JSON' => true]);

/**
 * Routes that fail the way the store's controllers do, answered by the handler registered for the current debug mode.
 */
function registerStatusProbeRoutes(): void
{
    Route::get('qa-status/get-only', fn () => 'ok');
    Route::post('qa-status/refused', fn () => abort(422, 'QA refused step'));
    Route::post('qa-status/stale', fn () => abort(409, 'QA stale order'));
    Route::post('qa-status/expired', fn () => throw new TokenMismatchException('CSRF token mismatch.'));
    Route::post('qa-status/prepared', fn () => 'not reached')->middleware(RejectWithPreparedResponse::class);
}

/**
 * Middleware that stops a request with a ready response, as request guards outside a route action do.
 */
class RejectWithPreparedResponse
{
    /**
     * Refuse the request before the route action runs.
     */
    public function handle(Request $request, Closure $next): never
    {
        throw new HttpResponseException(new JsonResponse(['qa' => 'kept'], 403));
    }
}

it('answers client errors with the same status in either debug mode', function (string $method, string $uri, int $status) {
    registerStatusProbeRoutes();

    $this->json($method, $uri)->assertStatus($status);
    $this->call($method, $uri)->assertStatus($status);
})->with([
    'wrong method' => ['DELETE', 'qa-status/get-only', 405],
    'refused step' => ['POST', 'qa-status/refused', 422],
    'stale order' => ['POST', 'qa-status/stale', 409],
    'expired form' => ['POST', 'qa-status/expired', 419],
    'prepared response' => ['POST', 'qa-status/prepared', 403],
]);

it('gives a JSON client the reason for a refused or stale step in either debug mode', function (string $uri, string $message) {
    registerStatusProbeRoutes();

    $this->postJson($uri)->assertJsonPath('message', $message);
})->with([
    'refused step' => ['qa-status/refused', 'QA refused step'],
    'stale order' => ['qa-status/stale', 'QA stale order'],
]);

it('sends the prepared response unchanged in either debug mode', function () {
    registerStatusProbeRoutes();

    $this->postJson('qa-status/prepared')->assertStatus(403)->assertExactJson(['qa' => 'kept']);
});
