<?php

use Webkul\Core\Repositories\CoreConfigRepository;

use function Pest\Laravel\get;

beforeEach(function () {
    config(['responsecache.enabled' => false]);
    app(CoreConfigRepository::class)->create(['channel' => core()->getCurrentChannelCode(), 'locale' => 'en', 'general' => ['content' => ['footer' => ['copyright_content' => '']]]]);
});

it('uses the store brand and copyright on standalone customer pages', function (string $route, array $parameters) {
    get(route($route, $parameters))->assertOk()
        ->assertSee('data-store-brand', false)
        ->assertSeeText('MAN SECRET PLEASURE')
        ->assertDontSeeText('Webkul Software');
})->with([
    'sign in' => ['shop.customer.session.index', []],
    'register' => ['shop.customers.register.index', []],
    'forgot password' => ['shop.customers.forgot_password.create', []],
    'reset password' => ['shop.customers.reset_password.create', ['token' => 'qa-token']],
]);

it('prefills the reset form email supplied by the password link', function () {
    $response = get(route('shop.customers.reset_password.create', ['token' => 'qa-token', 'email' => 'reset-qa@example.test']))->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $field = (new DOMXPath($document))->query('//*[@name="email"]')->item(0);

    expect($field->getAttribute('value'))->toBe('reset-qa@example.test');
});

it('preserves a saved custom copyright on the recovery page', function () {
    app(CoreConfigRepository::class)->create(['channel' => core()->getCurrentChannelCode(), 'locale' => 'en', 'general' => ['content' => ['footer' => ['copyright_content' => '<strong>QA store copyright</strong>']]]]);

    get(route('shop.customers.forgot_password.create'))->assertOk()->assertSeeText('QA store copyright');
});

it('sanitizes configured copyright HTML on standalone pages', function () {
    app(CoreConfigRepository::class)->create(['channel' => core()->getCurrentChannelCode(), 'locale' => 'en', 'general' => ['content' => ['footer' => ['copyright_content' => '<strong>QA safe copyright</strong><script>copyrightProbe()</script>']]]]);

    get(route('shop.customers.forgot_password.create'))->assertOk()
        ->assertSeeText('QA safe copyright')
        ->assertDontSee('copyrightProbe()', false);
});

it('retains a corrected email after reset validation instead of replacing it with the link email', function () {
    $response = $this->withSession(['_old_input' => ['email' => 'corrected-qa@example.test']])
        ->get(route('shop.customers.reset_password.create', ['token' => 'qa-token', 'email' => 'reset-qa@example.test']))->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML($response->getContent());
    $field = (new DOMXPath($document))->query('//*[@name="email"]')->item(0);

    expect($field->getAttribute('value'))->toBe('corrected-qa@example.test');
});
