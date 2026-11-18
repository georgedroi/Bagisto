<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ViewErrorBag;
use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Customer\Models\Customer;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Product\Repositories\ProductAttributeValueRepository;
use Webkul\RMA\Enums\DefaultRMAStatusEnum;
use Webkul\RMA\Models\RMA;
use Webkul\RMA\Models\RMAItem;
use Webkul\RMA\Models\RMAReason;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderItem;
use Webkul\Theme\Enums\SectionTypeEnum;
use Webkul\Theme\Models\Section;

use function Pest\Laravel\get;

beforeEach(function () {
    config(['responsecache.enabled' => false]);
});

/**
 * Text nodes carrying the needle inside the page's Vue root and its x-templates, paired with whether a v-pre ancestor keeps Vue from compiling them.
 *
 * @return array<int, bool>
 */
function sharedContentNodes(string $html, string $needle): array
{
    $fragments = [];

    if (preg_match('#<div[^>]*\bid="app"[^>]*>.*#s', $html, $app)) {
        $fragments[] = $app[0];
    }

    preg_match_all('#<script[^>]*type="text/x-template"[^>]*>(.*?)</script>#s', $html, $templates);

    $nodes = [];

    foreach (array_merge($fragments, $templates[1]) as $fragment) {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8"?><body>'.preg_replace('#<script\b[^>]*>.*?</script>#s', '', $fragment).'</body>');
        $xpath = new DOMXPath($document);

        foreach ($xpath->query('//text()[contains(., "'.$needle.'")]') as $node) {
            $nodes[] = $xpath->query('ancestor::*[@v-pre]', $node)->length > 0;
        }
    }

    return $nodes;
}

/**
 * Assert the needle is rendered where Vue could compile it at least the given number of times, and always under v-pre.
 */
function expectSharedContentIsolated(string $html, string $needle, int $atLeast = 1): void
{
    $nodes = sharedContentNodes($html, $needle);

    expect(count($nodes))->toBeGreaterThanOrEqual($atLeast)
        ->and(array_filter($nodes, fn ($guarded) => ! $guarded))->toBeEmpty();
}

/**
 * Save configuration values for the current channel and locale.
 */
function sharedContentConfig(array $values): void
{
    app(CoreConfigRepository::class)->create($values + [
        'channel' => core()->getCurrentChannelCode(),
        'locale' => app()->getLocale(),
    ]);
}

it('keeps product descriptions and attribute text out of Vue compilation in the default theme', function () {
    $product = (new ProductFaker)->getSimpleProductFactory()->create();
    $values = app(ProductAttributeValueRepository::class);
    $length = Attribute::query()->where('code', 'length')->firstOrFail();
    $length->forceFill(['is_visible_on_front' => 1, 'admin_name' => 'Length sentinel {{ 7*191 }}'])->save();
    $length->translations()->update(['name' => 'Length sentinel {{ 7*191 }}']);
    $values->updateOrCreate(['product_id' => $product->id, 'attribute_id' => $length->id, 'channel' => null, 'locale' => null], ['text_value' => 'Value sentinel {{ 7*191 }}']);

    foreach (['description' => 'Description sentinel', 'short_description' => 'Short sentinel'] as $code => $sentinel) {
        $values->updateOrCreate(
            ['product_id' => $product->id, 'attribute_id' => Attribute::query()->where('code', $code)->firstOrFail()->id, 'channel' => null, 'locale' => app()->getLocale()],
            ['text_value' => '<p>'.$sentinel." {{\n7*191\n}}</p>"],
        );
    }

    $html = get(route('shop.product_or_category.index', $product->fresh()->url_key))->assertOk()->getContent();

    expectSharedContentIsolated($html, 'Description sentinel', 2);
    expectSharedContentIsolated($html, 'Short sentinel');
    expectSharedContentIsolated($html, 'Length sentinel', 2);
    expectSharedContentIsolated($html, 'Value sentinel', 2);
});

it('keeps and sanitizes static home content in the default theme without losing normal formatting', function () {
    $section = Section::factory()->create(['channel_id' => core()->getCurrentChannel()->id, 'theme_code' => 'default', 'type' => SectionTypeEnum::STATIC_CONTENT->value, 'status' => 1, 'sort_order' => 0]);
    $section->translateOrNew(app()->getLocale())->options = [
        'html' => '<h2 class="qa-home-heading">Home sentinel {{ 7*191 }}</h2><p>Read the <a href="https://example.com/guide">guide</a> <strong>today</strong>.</p><img src="x" onerror="alert(1)"><script>alert(2)</script>',
        'css' => '',
    ];
    $section->save();

    $html = get(route('shop.home.index'))->assertOk()->getContent();

    expectSharedContentIsolated($html, 'Home sentinel');

    expect($html)->toContain('<h2 class="qa-home-heading">')
        ->toContain('<a href="https://example.com/guide">guide</a>')
        ->toContain('<strong>today</strong>')
        ->not->toContain('onerror="alert(1)"')
        ->not->toContain('<script>alert(2)</script>');
});

it('escapes and isolates the checkout message in the default theme', function () {
    $order = Order::factory()->create(['status' => 'pending']);
    $order->checkout_message = "Thanks sentinel {{ 7*191 }}\nSecond line <img src=x onerror=\"alert(1)\">";

    themes()->set('default');
    view()->share('errors', new ViewErrorBag);

    $html = '<div id="app">'.view('shop::checkout.success', compact('order'))->render().'</div>';

    expectSharedContentIsolated($html, 'Thanks sentinel');

    expect($html)->not->toContain('<img src=x onerror=')
        ->toContain('&lt;img src=x onerror=')
        ->toContain("<br />\nSecond line");
});

it('sanitizes and isolates the cookie policy descriptions', function () {
    sharedContentConfig(['general' => ['gdpr' => [
        'settings' => ['enabled' => 1],
        'cookie' => ['enabled' => 1],
        'cookie_consent' => [
            'strictly_necessary' => "Necessary sentinel {{\n7*191\n}} <img src=x onerror=\"alert(1)\">",
            'basic_interaction' => 'Basic sentinel {{ 7*191 }} <strong>kept</strong>',
        ],
    ]]]);

    $this->actingAs(Customer::factory()->create());

    $html = get(route('shop.customers.gdpr.cookie-consent'))->assertOk()->getContent();

    expectSharedContentIsolated($html, 'Necessary sentinel');
    expectSharedContentIsolated($html, 'Basic sentinel');

    expect($html)->not->toContain('onerror="alert(1)"')
        ->toContain('<strong>kept</strong>');
});

it('isolates the mini-cart offer text', function () {
    sharedContentConfig(['sales' => ['checkout' => ['mini_cart' => ['display_mini_cart' => 1, 'offer_info' => 'Offer sentinel {{ 7*191 }}']]]]);

    expectSharedContentIsolated(get(route('shop.home.index'))->assertOk()->getContent(), 'Offer sentinel');
});

it('isolates the return policy text on the return request page', function () {
    sharedContentConfig(['sales' => ['rma' => ['setting' => ['enable_rma' => 1, 'return_policy' => 'Policy sentinel {{ 7*191 }}']]]]);

    $this->actingAs(Customer::factory()->create());

    expectSharedContentIsolated(get(route('shop.customers.account.rma.create'))->assertOk()->getContent(), 'Policy sentinel');
});

it('isolates saved product names and reason titles on the return details page', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create(['customer_id' => $customer->id, 'customer_email' => $customer->email, 'status' => 'completed']);
    $product = (new ProductFaker)->getSimpleProductFactory()->create();
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'product_type' => $product::class, 'type' => 'simple', 'sku' => $product->sku, 'name' => 'Item sentinel {{ 7*191 }}']);
    $reason = RMAReason::query()->create(['title' => 'Reason sentinel {{ 7*191 }}', 'status' => 1, 'position' => 1]);
    $rma = RMA::create(['order_id' => $order->id, 'rma_status_id' => DefaultRMAStatusEnum::PENDING->value]);
    RMAItem::create(['rma_id' => $rma->id, 'order_item_id' => $item->id, 'rma_reason_id' => $reason->id, 'quantity' => 1, 'resolution' => 'return']);

    $this->actingAs($customer);

    $html = get(route('shop.customers.account.rma.view', $rma->id))->assertOk()->getContent();

    expectSharedContentIsolated($html, 'Item sentinel');
    expectSharedContentIsolated($html, 'Reason sentinel');
});

it('keeps an uploaded Media Bank file name out of the admin Vue compiler', function () {
    Storage::fake('public');

    $this->loginAsAdmin();

    $this->post(route('admin.media.assets.store'), ['file' => UploadedFile::fake()->image('Name sentinel {{ 7*191 }}.png', 64, 64)], ['Accept' => 'application/json'])->assertCreated();

    expectSharedContentIsolated(get(route('admin.media.index'))->assertOk()->getContent(), 'Name sentinel');
});
