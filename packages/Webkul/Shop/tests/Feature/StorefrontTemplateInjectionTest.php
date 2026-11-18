<?php

use Illuminate\Support\ViewErrorBag;
use Webkul\Attribute\Models\Attribute;
use Webkul\CMS\Repositories\PageRepository;
use Webkul\Core\Repositories\CoreConfigRepository;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Product\Repositories\ProductAttributeValueRepository;
use Webkul\Sales\Models\Order;
use Webkul\Sales\Models\OrderPayment;
use Webkul\Theme\Enums\SectionTypeEnum;
use Webkul\Theme\Models\Section;

use function Pest\Laravel\get;

const STOREFRONT_TEMPLATE_PAYLOAD = '{{ 7*191 }}';

beforeEach(function () {
    config(['responsecache.enabled' => false]);

    $channel = clone core()->getCurrentChannel();
    $channel->theme = 'manpleasure';

    core()->setCurrentChannel($channel);
});

/**
 * Every text node carrying the payload inside the Vue root, in the page and in its x-templates, paired with whether a v-pre ancestor keeps Vue from compiling it.
 *
 * @return array<int, bool>
 */
function payloadOccurrences(string $html): array
{
    $fragments = [];

    if (preg_match('#<div id="app">.*#s', $html, $app)) {
        $fragments[] = $app[0];
    }

    preg_match_all('#<script[^>]*type="text/x-template"[^>]*>(.*?)</script>#s', $html, $templates);

    $fragments = array_merge($fragments, $templates[1]);

    $occurrences = [];

    foreach ($fragments as $fragment) {
        $document = new DOMDocument;
        @$document->loadHTML('<?xml encoding="utf-8"?><body>'.preg_replace('#<script\b[^>]*>.*?</script>#s', '', $fragment).'</body>');

        foreach ((new DOMXPath($document))->query('//text()[contains(., "'.STOREFRONT_TEMPLATE_PAYLOAD.'")]') as $node) {
            $occurrences[] = (new DOMXPath($document))->query('ancestor::*[@v-pre]', $node)->length > 0;
        }
    }

    return $occurrences;
}

/**
 * Assert the payload is rendered at least the given number of times and never where Vue would compile it.
 */
function expectPayloadNeverCompiled(string $html, int $atLeast = 1): void
{
    $occurrences = payloadOccurrences($html);

    expect(count($occurrences))->toBeGreaterThanOrEqual($atLeast)
        ->and(array_filter($occurrences, fn ($guarded) => ! $guarded))->toBeEmpty();
}

it('keeps admin text in the desktop and mobile additional information out of Vue compilation', function () {
    $product = (new ProductFaker)->getSimpleProductFactory()->create();
    $attribute = Attribute::query()->where('code', 'length')->firstOrFail();
    $attribute->forceFill(['is_visible_on_front' => 1, 'admin_name' => 'Length '.STOREFRONT_TEMPLATE_PAYLOAD])->save();
    $attribute->translations()->update(['name' => 'Length '.STOREFRONT_TEMPLATE_PAYLOAD]);

    app(ProductAttributeValueRepository::class)->updateOrCreate(
        ['product_id' => $product->id, 'attribute_id' => $attribute->id, 'channel' => null, 'locale' => null],
        ['text_value' => '12 cm '.STOREFRONT_TEMPLATE_PAYLOAD],
    );

    $html = get(route('shop.product_or_category.index', $product->fresh()->url_key))->assertOk()->getContent();

    expectPayloadNeverCompiled($html, 4);
});

it('keeps existing product and short descriptions out of Vue compilation on the product page', function () {
    $product = (new ProductFaker)->getSimpleProductFactory()->create();
    $description = Attribute::query()->where('code', 'description')->firstOrFail();

    app(ProductAttributeValueRepository::class)->updateOrCreate(
        ['product_id' => $product->id, 'attribute_id' => $description->id, 'channel' => null, 'locale' => app()->getLocale()],
        ['text_value' => '<p>Stored before sanitizing '.STOREFRONT_TEMPLATE_PAYLOAD.'</p>'],
    );

    $shortDescription = Attribute::query()->where('code', 'short_description')->firstOrFail();

    app(ProductAttributeValueRepository::class)->updateOrCreate(
        ['product_id' => $product->id, 'attribute_id' => $shortDescription->id, 'channel' => null, 'locale' => app()->getLocale()],
        ['text_value' => '<p>Short stored '.STOREFRONT_TEMPLATE_PAYLOAD.'</p>'],
    );

    $html = get(route('shop.product_or_category.index', $product->fresh()->url_key))->assertOk()->getContent();

    expectPayloadNeverCompiled($html, 3);
});

it('keeps home services and static content text out of Vue compilation', function () {
    $channel = core()->getCurrentChannel();

    foreach ([
        SectionTypeEnum::SERVICES_CONTENT->value => ['services' => [['title' => 'Discreet '.STOREFRONT_TEMPLATE_PAYLOAD, 'description' => 'Plain box '.STOREFRONT_TEMPLATE_PAYLOAD, 'service_icon' => 'icon-truck']]],
        SectionTypeEnum::STATIC_CONTENT->value => ['html' => '<p class="qa-static">Static '.STOREFRONT_TEMPLATE_PAYLOAD.'</p>', 'css' => ''],
    ] as $type => $options) {
        $section = Section::factory()->create(['channel_id' => $channel->id, 'theme_code' => 'manpleasure', 'type' => $type, 'status' => 1, 'sort_order' => 0]);
        $section->translateOrNew(app()->getLocale())->options = $options;
        $section->save();
    }

    $html = get(route('shop.home.index'))->assertOk()->getContent();

    expectPayloadNeverCompiled($html, 3);
});

it('keeps journal page titles out of Vue compilation', function () {
    app(PageRepository::class)->create([
        'page_title' => 'Wellness '.STOREFRONT_TEMPLATE_PAYLOAD,
        'url_key' => 'blog-template-check',
        'html_content' => '<p>Article body</p>',
        'meta_title' => 'Wellness',
        'meta_keywords' => '',
        'meta_description' => '',
        'channels' => [core()->getCurrentChannel()->id],
        'locale' => app()->getLocale(),
    ]);

    $html = get(route('manpleasure.blog.index'))->assertOk()->getContent();

    expectPayloadNeverCompiled($html);
});

it('keeps payment instructions and the checkout message out of Vue compilation', function () {
    app(CoreConfigRepository::class)->create([
        'channel' => core()->getCurrentChannelCode(),
        'locale' => app()->getLocale(),
        'sales' => ['payment_methods' => ['banktransfer' => [
            'active' => true,
            'title' => 'Bank '.STOREFRONT_TEMPLATE_PAYLOAD,
            'bank_name' => 'QA Bank '.STOREFRONT_TEMPLATE_PAYLOAD,
            'account_name' => 'QA Account',
            'account_number' => 'QA-ONLY',
        ]]],
    ]);

    $order = Order::factory()->create(['status' => 'pending', 'workflow_status' => 'awaiting_payment', 'base_grand_total_invoiced' => 0, 'grand_total_invoiced' => 0]);
    OrderPayment::factory()->create(['order_id' => $order->id, 'method' => 'banktransfer']);
    $order = $order->fresh(['payment']);
    $order->checkout_message = 'Thanks '.STOREFRONT_TEMPLATE_PAYLOAD;

    themes()->set('manpleasure');
    view()->share('errors', new ViewErrorBag);

    expectPayloadNeverCompiled(view('shop::checkout.success', compact('order'))->render(), 2);
});
