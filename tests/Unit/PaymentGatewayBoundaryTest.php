<?php

use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('blocks every registered gateway action before middleware or provider side effects', function () {
    $previousContainer = Container::getInstance();
    $previousFacade = Facade::getFacadeApplication();
    $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    $router = $app['router'];
    $checked = 0;

    foreach ($router->getRoutes() as $route) {
        $action = $route->getActionName();
        $gateway = false;
        foreach (['Stripe', 'Paypal', 'Razorpay', 'PayU', 'PayGlocal', 'PhonePe'] as $package) {
            $gateway = $gateway || str_starts_with($action, 'Webkul\\'.$package.'\\');
        }
        $gateway = $gateway || str_contains($action, '\\PaymentRedirectController@') || str_contains($action, '\\Payment\\Http\\Controllers\\WebhookController@');
        if (! $gateway) {
            continue;
        }
        $uri = preg_replace('/\{[^}]+\}/', 'qa', $route->uri());
        foreach (array_diff($route->methods(), ['HEAD']) as $method) {
            try {
                $router->dispatch(Request::create('/'.$uri, $method, ['status' => 'success', 'paid' => true]));
                test()->fail('Gateway action was allowed: '.$action);
            } catch (HttpException $exception) {
                expect($exception->getStatusCode())->toBe(403)
                    ->and($route->getAction('uses'))->toBeInstanceOf(Closure::class);
            }
            $checked++;
        }
    }

    expect($checked)->toBeGreaterThanOrEqual(25);
    restore_error_handler();
    restore_exception_handler();
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($previousFacade);
    Container::setInstance($previousContainer);
});
