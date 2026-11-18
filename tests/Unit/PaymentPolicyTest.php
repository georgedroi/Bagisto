<?php

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Webkul\Checkout\Cart;
use Webkul\Core\Core;
use Webkul\Payment\Payment;
use Webkul\Payment\Payment\BankTransfer;
use Webkul\Payment\Payment\DuitNowQRManual;
use Webkul\Sales\Repositories\OrderRepository;

beforeEach(function () {
    $this->previousContainer = Container::getInstance();
    $this->previousFacade = Facade::getFacadeApplication();
    $this->app = new Application(dirname(__DIR__, 2));
    $this->app->instance('config', new Repository);
    $this->app->instance('translator', new Translator(new ArrayLoader, 'en'));
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($this->app);
    $this->settings = new class
    {
        public array $values = [];

        /**
         * Supply only synthetic payment configuration.
         */
        public function getConfigData(string $key): mixed
        {
            return $this->values[$key] ?? null;
        }
    };
    $this->app->instance(Core::class, $this->settings);
    require_once dirname(__DIR__, 2).'/packages/Webkul/Core/src/Http/helpers.php';
});

afterEach(function () {
    Mockery::close();
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($this->previousFacade);
    Container::setInstance($this->previousContainer);
});

it('excludes every unapproved method even if its provider advertises availability', function () {
    $methods = ['banktransfer', 'duitnowqr_manual', 'cashondelivery', 'moneytransfer', 'cryptotransfer', 'stripe', 'paypal_standard', 'paypal_smart_button', 'razorpay', 'payu', 'payglocal', 'phonepe', 'creditdebitcards', 'fpx', 'tngewallet', 'duitnowqr_auto', 'duitnowqr', 'future_gateway'];
    $registry = [];
    foreach ($methods as $code) {
        $class = 'qa.payment.'.$code;
        $registry[$code] = ['class' => $class, 'code' => $code];
        $provider = Mockery::mock();
        $provider->shouldReceive('isAvailable')->andReturn(true);
        $provider->shouldReceive('getCode')->andReturn($code);
        $provider->shouldReceive('getTitle', 'getDescription', 'getImage')->andReturn('QA');
        $provider->shouldReceive('getSortOrder')->andReturn(1);
        $provider->shouldReceive('getAdditionalDetails')->andReturn([]);
        $this->app->instance($class, $provider);
    }
    config(['payment_methods' => $registry]);

    expect(array_column((new Payment)->getPaymentMethods(), 'method'))->toBe(['banktransfer', 'duitnowqr_manual']);
});

it('requires all bank details before offering bank transfer', function (string $missing) {
    foreach (['active' => true, 'bank_name' => 'QA Bank', 'account_name' => 'QA Store', 'account_number' => 'QA-000', 'instructions' => 'Transfer the total with your order reference.'] as $key => $value) {
        $this->settings->values['sales.payment_methods.banktransfer.'.$key] = $value;
    }
    expect((new BankTransfer)->isAvailable())->toBeTrue();
    $this->settings->values['sales.payment_methods.banktransfer.'.$missing] = ' ';
    expect((new BankTransfer)->isAvailable())->toBeFalse();
})->with(['bank_name', 'account_name', 'account_number', 'instructions']);

it('requires an actual QR setting rather than only an account number', function () {
    $this->settings->values = ['sales.payment_methods.duitnowqr_manual.active' => true, 'sales.payment_methods.duitnowqr_manual.account_number' => 'QA-000'];
    expect((new DuitNowQRManual)->isAvailable())->toBeFalse();
    $this->settings->values['sales.payment_methods.duitnowqr_manual.qr_image'] = 'qa/qr.png';
    expect((new DuitNowQRManual)->isAvailable())->toBeTrue();
});

it('refuses manipulated cart selections without deleting the existing selection', function (mixed $method) {
    $cart = (new ReflectionClass(Cart::class))->newInstanceWithoutConstructor();
    $existing = new class
    {
        /**
         * Prove invalid requests cannot even access the stored payment.
         */
        public function __get(string $name): never
        {
            throw new RuntimeException('Existing cart was accessed');
        }
    };
    (new ReflectionProperty($cart, 'cart'))->setValue($cart, $existing);
    expect($cart->savePaymentMethod(['method' => $method]))->toBeFalse();
})->with(['stripe', 'cashondelivery', 'moneytransfer', 'cryptotransfer', 'paypal_standard', 'unknown', null, [['stripe']], 'BANKTRANSFER']);

it('rejects forbidden order creation before starting a database transaction', function (mixed $method) {
    $orders = (new ReflectionClass(OrderRepository::class))->newInstanceWithoutConstructor();
    expect(fn () => $orders->create(['payment' => ['method' => $method]]))->toThrow(fn (HttpException $exception) => expect($exception->getStatusCode())->toBe(403));
})->with(['stripe', 'cashondelivery', 'moneytransfer', 'cryptotransfer', 'paypal_smart_button', 'unknown', null, [['banktransfer']]]);
