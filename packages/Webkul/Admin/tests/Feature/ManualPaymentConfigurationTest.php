<?php

use App\Support\CryptoAddress;
use App\Support\ManualPaymentPolicy;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Payment\Payment\CashOnDelivery;
use Webkul\Payment\PaymentPolicy;

it('blocks a configured gateway omitted by the former production readiness list', function () {
    app(CoreConfigRepository::class)->create(['channel' => core()->getCurrentChannelCode(), 'locale' => 'en', 'sales' => ['payment_methods' => ['razorpay' => ['active' => 1]]]]);

    expect(ManualPaymentPolicy::readinessChecks()['unverified payment method disabled: razorpay'])->toBeFalse();
});

it('blocks runtime availability of an unknown gateway even when its configured flag is off', function () {
    $method = new class extends CashOnDelivery
    {
        /**
         * Advertise availability independently of saved settings.
         */
        public function isAvailable(): bool
        {
            return true;
        }
    };
    app()->instance($method::class, $method);
    config(['payment_methods.future_gateway' => ['class' => $method::class, 'active' => false]]);

    expect(ManualPaymentPolicy::readinessChecks()['unverified payment method unavailable: future_gateway'])->toBeFalse();
});

it('requires at least one approved available manual payment method for launch', function () {
    app(CoreConfigRepository::class)->create(['channel' => core()->getCurrentChannelCode(), 'locale' => 'en', 'sales' => ['payment_methods' => array_fill_keys(['banktransfer', 'duitnowqr_manual', 'cryptotransfer'], ['active' => 0])]]);

    expect(ManualPaymentPolicy::readinessChecks()['approved manual payment method available'])->toBeFalse();
});

/**
 * Submit only the approved method settings through the existing configuration route.
 */
function manualPaymentConfigurationPayload(string $code, array $fields): array
{
    return ['channel' => core()->getCurrentChannelCode(), 'locale' => 'en', 'sales' => ['payment_methods' => [$code => ['active' => 1, 'title' => 'QA manual method', ...$fields]]]];
}

it('offers only approved payment editors and distinguishes checkout logos from receiving QR images', function () {
    $this->loginAsAdmin();
    $page = $this->get(route('admin.configuration.index', ['slug' => 'sales', 'slug2' => 'payment_methods']))->assertOk();
    $document = new DOMDocument;
    @$document->loadHTML($page->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//details[@data-payment-method]')->length)->toBe(2)
        ->and($xpath->query('//details[@data-payment-method="cryptotransfer"]')->length)->toBe(0)
        ->and($xpath->query('//v-configurable[@name="sales[payment_methods][duitnowqr_manual][image]"]')->item(0)->getAttribute('label'))->toBe(trans('payment::settings.logo-label'))
        ->and($xpath->query('//v-configurable[@name="sales[payment_methods][duitnowqr_manual][qr_image]"]')->item(0)->getAttribute('label'))->toBe(trans('payment::settings.qr-label'))
        ->and($xpath->query('//v-configurable[@name="sales[payment_methods][stripe][active]"]')->length)->toBe(0)
        ->and($xpath->query('//input[@name="sales[payment_methods][stripe][active]" and @type="hidden" and @value="0"]')->length)->toBe(1);
});

it('refuses enabling an upstream gateway without changing its saved state', function () {
    $this->loginAsAdmin();
    $before = core()->getConfigData('sales.payment_methods.razorpay.active');
    $this->postJson(route('admin.configuration.store', ['slug' => 'sales', 'slug2' => 'payment_methods']), manualPaymentConfigurationPayload('razorpay', []))
        ->assertUnprocessable()->assertJsonValidationErrors('sales.payment_methods.razorpay.active');
    expect(core()->getConfigData('sales.payment_methods.razorpay.active'))->toBe($before);
});

it('refuses enabling bank transfer with incomplete receiving details', function () {
    $this->loginAsAdmin();
    $payload = manualPaymentConfigurationPayload('banktransfer', ['bank_name' => '', 'account_name' => '', 'account_number' => '']);
    $this->postJson(route('admin.configuration.store', ['slug' => 'sales', 'slug2' => 'payment_methods']), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors(['sales.payment_methods.banktransfer.bank_name', 'sales.payment_methods.banktransfer.account_name', 'sales.payment_methods.banktransfer.account_number']);
});

it('refuses enabling QR without a saved image or complete bank details', function () {
    $this->loginAsAdmin();
    $payload = manualPaymentConfigurationPayload('duitnowqr_manual', ['bank_name' => '', 'account_name' => '', 'account_number' => '', 'qr_image' => ['delete' => 1]]);
    $this->post(route('admin.configuration.store', ['slug' => 'sales', 'slug2' => 'payment_methods']), $payload, ['Accept' => 'application/json'])
        ->assertUnprocessable()->assertJsonValidationErrors('sales.payment_methods.duitnowqr_manual.qr_image');
});

it('refuses an invalid wallet before any configuration is persisted', function () {
    $this->loginAsAdmin();
    $payload = manualPaymentConfigurationPayload('cryptotransfer', ['btc_address' => '', 'usdt_trc20_address' => 'invalid', 'usdt_erc20_address' => '']);
    $this->postJson(route('admin.configuration.store', ['slug' => 'sales', 'slug2' => 'payment_methods']), $payload)
        ->assertUnprocessable()->assertJsonValidationErrors('sales.payment_methods.cryptotransfer.usdt_trc20_address');
    expect(app(CoreConfigRepository::class)->findOneByField('value', 'invalid'))->toBeNull();
});

it('accepts official checksummed address examples and refuses a changed checksum', function () {
    $ethereum = '0x'.'5aAeb6053F3E94C9b9A09f33'.'669435E7Ef1BeAed';
    $bitcoin = 'bc1q'.'w508d6qejxtdg4y5r3zarvary0c5xw7k'.'v8f3t4';
    $tron = 'TNPeeaa'.'FB7K9cmo4uQpcU32zGK8G1NYqeL';
    expect(CryptoAddress::valid($ethereum, 'erc20'))->toBeTrue()
        ->and(CryptoAddress::valid(strtolower($ethereum), 'erc20'))->toBeFalse()
        ->and(CryptoAddress::valid($bitcoin, 'btc'))->toBeTrue()
        ->and(CryptoAddress::valid(substr($bitcoin, 0, -1).'q', 'btc'))->toBeFalse()
        ->and(CryptoAddress::valid($tron, 'trc20'))->toBeTrue()
        ->and(CryptoAddress::valid(substr($tron, 0, -1).'1', 'trc20'))->toBeFalse();
});

it('saves complete approved receiving details through the existing configuration route', function (string $code, array $fields) {
    $this->loginAsAdmin();
    $this->post(route('admin.configuration.store', ['slug' => 'sales', 'slug2' => 'payment_methods']), manualPaymentConfigurationPayload($code, $fields))
        ->assertSessionHasNoErrors()->assertRedirect();
    $repository = app(CoreConfigRepository::class);
    expect((bool) $repository->findOneWhere(['code' => "sales.payment_methods.{$code}.active", 'channel_code' => core()->getCurrentChannelCode()])->value)->toBeTrue();
    foreach ($fields as $field => $value) {
        expect($repository->findOneWhere(['code' => "sales.payment_methods.{$code}.{$field}", 'channel_code' => core()->getCurrentChannelCode()])->value)->toBe($value);
    }
})->with([
    'bank' => ['banktransfer', ['bank_name' => 'QA Bank', 'account_name' => 'QA Only', 'account_number' => 'QA-NO-FUNDS']],
    'QR bank fallback' => ['duitnowqr_manual', ['bank_name' => 'QA Bank', 'account_name' => 'QA Only', 'account_number' => 'QA-NO-FUNDS']],
]);

it('refuses to enable crypto, which checkout does not offer, even with a valid wallet', function () {
    $this->loginAsAdmin();
    $this->postJson(route('admin.configuration.store', ['slug' => 'sales', 'slug2' => 'payment_methods']), manualPaymentConfigurationPayload('cryptotransfer', ['usdt_erc20_address' => '0x'.str_repeat('1', 40)]))
        ->assertUnprocessable()->assertJsonValidationErrors('sales.payment_methods.cryptotransfer.active');
    expect(core()->getConfigData('sales.payment_methods.cryptotransfer.active'))->not->toBeTruthy();
});

it('keeps the admin whitelist equal to the methods checkout offers', function () {
    expect(ManualPaymentPolicy::METHODS)->toEqualCanonicalizing(['duitnowqr_manual', 'banktransfer'])
        ->and(collect(ManualPaymentPolicy::METHODS)->every(fn (string $code) => PaymentPolicy::allows($code)))->toBeTrue();
});

it('preserves submitted payment fields and exposes errors after a rejected multi-method save', function () {
    $this->loginAsAdmin();
    $url = route('admin.configuration.index', ['slug' => 'sales', 'slug2' => 'payment_methods']);
    $payload = manualPaymentConfigurationPayload('duitnowqr_manual', ['bank_name' => 'QA preserved bank', 'account_name' => 'QA preserved recipient', 'account_number' => 'QA-NO-FUNDS']);
    $payload['sales']['payment_methods']['banktransfer'] = ['active' => 1, 'title' => 'QA bank', 'bank_name' => '', 'account_name' => '', 'account_number' => ''];
    $this->from($url)->post($url, $payload)->assertRedirect($url)->assertSessionHasErrors('sales.payment_methods.banktransfer.bank_name');
    $page = $this->get($url)->assertOk()->assertSee('role="alert"', false)->assertSeeText(trans('payment::setup.bank-required'));
    $document = new DOMDocument;
    @$document->loadHTML($page->getContent());
    $xpath = new DOMXPath($document);
    expect($xpath->query('//v-configurable[@name="sales[payment_methods][duitnowqr_manual][bank_name]"]')->item(0)->getAttribute('value'))->toBe('QA preserved bank')
        ->and($xpath->query('//v-configurable[@name="sales[payment_methods][duitnowqr_manual][active]"]')->item(0)->getAttribute('value'))->toBe('1');
});

it('keeps a saved QR when its delete checkbox is unchecked', function () {
    Storage::fake();
    Storage::put('configuration/qa.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRz8AAAAASUVORK5CYII='));
    app(CoreConfigRepository::class)->create(['channel' => core()->getCurrentChannelCode(), 'locale' => 'en', 'sales' => ['payment_methods' => ['duitnowqr_manual' => ['qr_image' => 'configuration/qa.png']]]]);
    $this->loginAsAdmin();
    $this->post(route('admin.configuration.store', ['slug' => 'sales', 'slug2' => 'payment_methods']), manualPaymentConfigurationPayload('duitnowqr_manual', ['qr_image' => ['delete' => 0]]))
        ->assertSessionHasNoErrors()->assertRedirect();
    expect(app(CoreConfigRepository::class)->findOneWhere(['code' => 'sales.payment_methods.duitnowqr_manual.qr_image', 'channel_code' => core()->getCurrentChannelCode()])->value)->toBe('configuration/qa.png');
    Storage::assertExists('configuration/qa.png');
});

it('rejects a disguised executable QR image and accepts a genuine raster upload', function () {
    Storage::fake();
    $this->loginAsAdmin();
    $payload = manualPaymentConfigurationPayload('duitnowqr_manual', ['qr_image' => UploadedFile::fake()->createWithContent('payload.png', '<?php echo 1;')]);
    $this->post(route('admin.configuration.store', ['slug' => 'sales', 'slug2' => 'payment_methods']), $payload, ['Accept' => 'application/json'])
        ->assertUnprocessable()->assertJsonValidationErrors('sales.payment_methods.duitnowqr_manual.qr_image');
    $payload['sales']['payment_methods']['duitnowqr_manual']['qr_image'] = UploadedFile::fake()->image('qr.png');
    $this->post(route('admin.configuration.store', ['slug' => 'sales', 'slug2' => 'payment_methods']), $payload)
        ->assertSessionHasNoErrors()->assertRedirect();
    $image = app(CoreConfigRepository::class)->findOneWhere(['code' => 'sales.payment_methods.duitnowqr_manual.qr_image', 'channel_code' => core()->getCurrentChannelCode()])->value;
    Storage::assertExists($image);
});

it('refuses a submitted arbitrary QR path even when the old image exists', function () {
    Storage::fake();
    Storage::put('configuration/qa.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRz8AAAAASUVORK5CYII='));
    app(CoreConfigRepository::class)->create(['channel' => core()->getCurrentChannelCode(), 'locale' => 'en', 'sales' => ['payment_methods' => ['duitnowqr_manual' => ['qr_image' => 'configuration/qa.png']]]]);
    $this->loginAsAdmin();
    $this->postJson(route('admin.configuration.store', ['slug' => 'sales', 'slug2' => 'payment_methods']), manualPaymentConfigurationPayload('duitnowqr_manual', ['qr_image' => 'https://example.test/qr.png']))
        ->assertUnprocessable()->assertJsonValidationErrors('sales.payment_methods.duitnowqr_manual.qr_image');
});

it('validates public checksum vectors for all supported address encodings', function (string $address, string $network, bool $valid) {
    expect(CryptoAddress::valid($address, $network))->toBe($valid);
})->with([
    'BTC legacy' => ['1BoatSLRHtKNngkdXEeobR76b53LETtpyT', 'btc', true],
    'BTC script hash' => ['3J98t1WpEZ73CNmQviecrnyiWrnqRhWNLy', 'btc', true],
    'BTC legacy bad checksum' => ['1BoatSLRHtKNngkdXEeobR76b53LETtpyU', 'btc', false],
    'BTC testnet' => ['mipcBbFg9gMiCh81Kj8tqqdgoZub1ZJRfn', 'btc', false],
    'BTC uppercase witness' => ['BC1QW508D6QEJXTDG4Y5R3ZARVARY0C5XW7KV8F3T4', 'btc', true],
    'BTC mixed witness' => ['bc1Qw508d6qejxtdg4y5r3zarvary0c5xw7kv8f3t4', 'btc', false],
    'wrong network' => ['TNPeeaaFB7K9cmo4uQpcU32zGK8G1NYqeL', 'btc', false],
    'ETH all uppercase checksum' => ['0x52908400098527886E0F7030069857D2E4169EE7', 'erc20', true],
    'ETH all lowercase checksum' => ['0xde709f2102306220921060314715629080e2fb77', 'erc20', true],
    'ETH mixed checksum' => ['0xdbF03B407c01E7cD3CBea99509d93f8DDDC8C6FB', 'erc20', true],
    'BTC taproot' => ['bc1p0xlxvlhemja6c4dqv22uapctqupfhlxm9h8z3k2e72q4k9hcz7vqzk5jj0', 'btc', true],
    'BTC witness16' => ['BC1SW50QGDZ25J', 'btc', true],
    'BTC wrong witness checksum type' => ['bc1p0xlxvlhemja6c4dqv22uapctqupfhlxm9h8z3k2e72q4k9hcz7vqh2y7hd', 'btc', false],
    'BTC wrong v0 checksum type' => ['bc1qw508d6qejxtdg4y5r3zarvary0c5xw7kemeawh', 'btc', false],
    'BTC invalid program length' => ['bc1pw5dgrnzv', 'btc', false],
    'BTC excessive padding' => ['bc1p0xlxvlhemja6c4dqv22uapctqupfhlxm9h8z3k2e72q4k9hcz7v07qwwzcrf', 'btc', false],
    'ETH invalid checksum' => ['0x52908400098527886e0f7030069857d2e4169ee7', 'erc20', false],
    'empty' => ['', 'btc', false],
    'overlong' => [str_repeat('1', 91), 'btc', false],
    'invalid character' => ['0OIl', 'trc20', false],
]);
