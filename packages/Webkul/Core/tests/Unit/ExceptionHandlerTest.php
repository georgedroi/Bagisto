<?php

use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    config(['app.debug' => false]);
    themes()->set('manpleasure');
});

dataset('production request errors', [
    'admin HTML conflict' => ['admin', 409, false],
    'admin JSON conflict' => ['admin', 409, true],
    'admin HTML invalid request' => ['admin', 422, false],
    'admin JSON invalid request' => ['admin', 422, true],
    'shop HTML conflict' => ['shop', 409, false],
    'shop JSON conflict' => ['shop', 409, true],
    'shop HTML invalid request' => ['shop', 422, false],
    'shop JSON invalid request' => ['shop', 422, true],
]);

it('keeps intentional client error statuses when debug is disabled', function (string $namespace, int $status, bool $json) {
    $path = ($namespace === 'admin' ? '/'.config('app.admin_url', 'admin') : '').'/qa-http-error';
    Route::get($path, fn () => throw new HttpException($status));

    $response = $json ? $this->getJson($path) : $this->get($path);

    $response->assertStatus($status);

    if ($json) {
        $response->assertJsonPath('error', trans('core::app.errors.request.title'))
            ->assertJsonPath('description', trans('core::app.errors.request.description'));
    } else {
        $response->assertSeeText((string) $status)
            ->assertSeeText(trans('core::app.errors.request.description'));
    }
})->with('production request errors');

it('renders early admin errors with admin assets while the shop theme is active', function (int $status) {
    $path = '/'.config('app.admin_url', 'admin').'/qa-early-error';
    Route::get($path, fn () => throw new HttpException($status));

    $this->get($path)->assertStatus($status)
        ->assertSee('themes/admin/default/build/assets/', false)
        ->assertSee(bagisto_asset('images/error.svg', 'admin'), false)
        ->assertSee(bagisto_asset('images/favicon.ico', 'admin'), false);
})->with([403, 404, 503]);

it('keeps server failures private and returns a real server error in production mode', function (string $namespace, bool $json) {
    $path = ($namespace === 'admin' ? '/'.config('app.admin_url', 'admin') : '').'/qa-server-error';
    Route::get($path, fn () => throw new RuntimeException('private-server-diagnostic'));

    $response = $json ? $this->getJson($path) : $this->get($path);

    $response->assertStatus(500)->assertDontSee('private-server-diagnostic');

    if ($json) {
        $response->assertJsonPath('description', trans("{$namespace}::app.errors.500.description"));
    } else {
        $response->assertSeeText(trans("{$namespace}::app.errors.500.description"));
    }
})->with([['admin', false], ['admin', true], ['shop', false], ['shop', true]]);

it('retains protocol headers on intentional HTTP errors', function (int $status, array $headers) {
    Route::get('/qa-protocol-error', fn () => throw new HttpException($status, '', null, $headers));

    $response = $this->getJson('/qa-protocol-error')->assertStatus($status);

    foreach ($headers as $header => $value) {
        $response->assertHeader($header, $value);
    }
})->with([[429, ['Retry-After' => '17']], [405, ['Allow' => 'POST']]]);

it('returns the translated business refusal in the JSON message field', function (int $status) {
    $message = trans('admin::product-editor.media-in-use');
    Route::get('/admin/qa-business-error', fn () => throw new HttpException($status, $message));

    $this->getJson('/admin/qa-business-error')->assertStatus($status)
        ->assertJsonPath('message', $message)
        ->assertJsonPath('description', $message);
})->with([409, 422]);

it('renders business refusal text without allowing exception markup to become HTML', function (int $status) {
    $message = trans('admin::product-editor.media-in-use');
    Route::get('/admin/qa-business-error', fn () => throw new HttpException($status, '<strong>'.$message.'</strong><img src=x onerror="private-marker">'));

    $this->get('/admin/qa-business-error')->assertStatus($status)
        ->assertSeeText($message)
        ->assertDontSee('onerror="private-marker"', false)
        ->assertDontSee('<strong>'.$message.'</strong>', false);

    $this->getJson('/admin/qa-business-error')->assertStatus($status)
        ->assertJsonPath('message', $message)
        ->assertJsonPath('description', $message);
})->with([409, 422]);

it('does not expose server HTTP exception details as a business message', function (bool $json) {
    Route::get('/admin/qa-private-http-error', fn () => throw new HttpException(500, 'private-http-diagnostic'));
    $response = $json ? $this->getJson('/admin/qa-private-http-error') : $this->get('/admin/qa-private-http-error');

    $response->assertStatus(500)->assertDontSee('private-http-diagnostic');

    if ($json) {
        $response->assertJsonPath('message', trans('admin::app.errors.500.description'));
    }
})->with([false, true]);
