<?php

use Webkul\Category\Models\Category;
use Webkul\CMS\Repositories\PageRepository;
use Webkul\Core\Repositories\CoreConfigRepository;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

beforeEach(function () {
    config(['responsecache.enabled' => false]);
});

/**
 * Text nodes inside the storefront's Vue root that contain the needle, paired with whether a v-pre ancestor keeps Vue from compiling them.
 *
 * @return array<int, bool>
 */
function vueRootTextNodes(string $html, string $needle): array
{
    if (! preg_match('#<div id="app">.*#s', $html, $app)) {
        return [];
    }

    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8"?><body>'.preg_replace('#<script\b[^>]*>.*?</script>#s', '', $app[0]).'</body>');
    $xpath = new DOMXPath($document);
    $nodes = [];

    foreach ($xpath->query('//text()[contains(., "'.$needle.'")]') as $node) {
        $nodes[] = $xpath->query('ancestor::*[@v-pre]', $node)->length > 0;
    }

    return $nodes;
}

/**
 * Assert the needle is rendered inside the Vue root at least once and never where Vue would compile it.
 */
function expectIsolatedFromVue(string $html, string $needle): void
{
    $nodes = vueRootTextNodes($html, $needle);

    expect($nodes)->not->toBeEmpty()
        ->and(array_filter($nodes, fn ($guarded) => ! $guarded))->toBeEmpty();
}

/**
 * Use the production storefront theme for the current request.
 */
function useManpleasureTheme(): void
{
    $channel = clone core()->getCurrentChannel();
    $channel->theme = 'manpleasure';

    core()->setCurrentChannel($channel);
}

/**
 * Create a CMS page through the admin form and give its stored body.
 */
function storeCmsPage(string $slug, string $html): string
{
    post(route('admin.cms.store'), [
        'page_title' => 'QA isolation '.$slug,
        'url_key' => $slug,
        'html_content' => $html,
        'meta_title' => 'QA isolation',
        'meta_keywords' => '',
        'meta_description' => '',
        'channels' => [core()->getCurrentChannel()->id],
    ])->assertRedirect()->assertSessionHasNoErrors();

    return (string) app(PageRepository::class)->findByUrlKey($slug)->html_content;
}

dataset('vue expression variants', [
    'multi-line' => ["<p>QA sentinel {{\n7*191\n}}</p>"],
    'multi-line entity encoded' => ["<p>QA sentinel &#123;&#123;\n7*191\n&#125;&#125;</p>"],
    'single-line entity encoded' => ['<p>QA sentinel &#123;&#123; 7*191 &#125;&#125;</p>'],
    'split across tags' => ['<p>QA sentinel</p><p>{{ 7*191 /*</p><p>*/ }}</p>'],
    'reassembled by a removed directive' => ['<p>QA sentinel {@if{ 7*191 }}</p>'],
    'reassembled by a removed comment' => ['<p>QA sentinel {<!-- x -->{ 7*191 }}</p>'],
    'nested delimiters' => ['<p>QA sentinel {{{{ 7*191 }}}} {{ {{ 7*191 }} }}</p>'],
    'raw echo' => ["<p>QA sentinel {!!\n7*191\n!!}</p>"],
]);

it('stores no Vue template expression from a CMS page created through the admin form', function (string $html) {
    $this->loginAsAdmin();

    $stored = html_entity_decode(storeCmsPage('qa-isolation-create', $html), ENT_QUOTES | ENT_HTML5);

    expect(preg_match('/\{\{.*\}\}/s', $stored))->toBe(0)
        ->and(preg_match('/\{!!.*!!\}/s', $stored))->toBe(0)
        ->and($stored)->toContain('QA sentinel');
})->with('vue expression variants');

it('stores no Vue template expression from a CMS page edited through the admin form', function (string $html) {
    $this->loginAsAdmin();

    storeCmsPage('qa-isolation-edit', '<p>Original body</p>');

    $page = app(PageRepository::class)->findByUrlKey('qa-isolation-edit');
    $locale = app()->getLocale();

    put(route('admin.cms.update', $page->id), [
        $locale => ['page_title' => 'QA isolation edit', 'url_key' => 'qa-isolation-edit', 'html_content' => $html, 'meta_title' => 'QA', 'meta_keywords' => '', 'meta_description' => ''],
        'channels' => [core()->getCurrentChannel()->id],
        'locale' => $locale,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $stored = html_entity_decode((string) app(PageRepository::class)->findByUrlKey('qa-isolation-edit')->html_content, ENT_QUOTES | ENT_HTML5);

    expect(preg_match('/\{\{.*\}\}/s', $stored))->toBe(0)
        ->and($stored)->toContain('QA sentinel');
})->with('vue expression variants');

it('renders a CMS page body where Vue never compiles it, whatever is already stored', function (string $theme, string $stored) {
    if ($theme === 'manpleasure') {
        useManpleasureTheme();
    }

    $page = app(PageRepository::class)->create([
        'page_title' => 'QA legacy body',
        'url_key' => 'qa-legacy-body',
        'html_content' => '<p>Placeholder</p>',
        'meta_title' => 'QA',
        'meta_keywords' => '',
        'meta_description' => '',
        'channels' => [core()->getCurrentChannel()->id],
        'locale' => app()->getLocale(),
    ]);

    $page->translations()->update(['html_content' => $stored]);

    $html = get(route('shop.cms.page', 'qa-legacy-body'))->assertOk()->getContent();

    expectIsolatedFromVue($html, '7*191');
    expectIsolatedFromVue($html, 'QA legacy sentinel');
})->with(['default', 'manpleasure'])->with([
    'raw multi-line' => ["<h2>QA legacy sentinel</h2><p>{{\n7*191\n}}</p>"],
    'entity encoded, as the old filter left it' => ["<p>QA legacy sentinel &#123;&#123;\n7*191\n&#125;&#125;</p>"],
    'nested blocks' => ['<div><table><tbody><tr><td><p>QA legacy sentinel {{ 7*191 }}</p></td></tr></tbody></table></div>'],
]);

it('keeps normal CMS formatting, links, headings, images and tables when saved and when shown', function () {
    $this->loginAsAdmin();

    $body = '<h2 class="qa-heading">Shipping guide</h2>'
        .'<p style="text-align:center;">Read our <a href="https://example.com/returns" title="Returns">returns policy</a> and <strong>discreet</strong> <em>packing</em> notes.</p>'
        .'<ul><li>First point</li><li>Second point</li></ul>'
        .'<p><img src="/storage/qa/guide.png" alt="Guide" width="320" height="200" /></p>'
        .'<table class="qa-table"><thead><tr><th>Zone</th><th>Days</th></tr></thead><tbody><tr><td>West Malaysia</td><td>2</td></tr></tbody></table>'
        .'<blockquote>Prices show the cost of 10 { items } in RM.</blockquote>';

    $stored = storeCmsPage('qa-formatting', $body);

    foreach ([
        '<h2 class="qa-heading">Shipping guide</h2>',
        '<a href="https://example.com/returns" title="Returns">returns policy</a>',
        '<strong>discreet</strong>',
        '<em>packing</em>',
        '<li>First point</li>',
        'src="/storage/qa/guide.png"',
        '<table class="qa-table">',
        '<th>Zone</th>',
        '<td>West Malaysia</td>',
        'Prices show the cost of 10 { items } in RM.',
    ] as $kept) {
        expect($stored)->toContain($kept);
    }

    $html = get(route('shop.cms.page', 'qa-formatting'))->assertOk()->getContent();

    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8"?>'.$html);
    $xpath = new DOMXPath($document);
    $container = '//*[@data-cms-content][@v-pre]';

    expect($xpath->query($container)->length)->toBe(1)
        ->and($xpath->query($container.'//h2[@class="qa-heading"]')->length)->toBe(1)
        ->and($xpath->query($container.'//a[@href="https://example.com/returns"]')->length)->toBe(1)
        ->and($xpath->query($container.'//img[@src="/storage/qa/guide.png"]')->length)->toBe(1)
        ->and($xpath->query($container.'//table//td')->length)->toBe(2)
        ->and($xpath->query($container.'//ul/li')->length)->toBe(2);
});

it('renders a category description where Vue never compiles it', function (string $theme) {
    if ($theme === 'manpleasure') {
        useManpleasureTheme();
    }

    $category = Category::factory()->create(['status' => 1, 'display_mode' => 'products_and_description']);
    $category->translateOrNew(app()->getLocale())->fill([
        'name' => 'QA isolation category',
        'slug' => 'qa-isolation-category',
        'description' => "<p>QA category sentinel {{\n7*191\n}}</p>",
    ]);
    $category->save();

    $html = get(route('shop.product_or_category.index', 'qa-isolation-category'))->assertOk()->getContent();

    expectIsolatedFromVue($html, '7*191');
    expectIsolatedFromVue($html, 'QA category sentinel');
})->with(['default', 'manpleasure']);

it('renders the store copyright where Vue never compiles it, on the storefront and the customer sign-in pages', function (string $route) {
    useManpleasureTheme();

    app(CoreConfigRepository::class)->create([
        'channel' => core()->getCurrentChannelCode(),
        'locale' => app()->getLocale(),
        'general' => ['content' => ['footer' => ['copyright_content' => '<p>QA copyright sentinel</p>']]],
    ]);

    expectIsolatedFromVue(get(route($route))->assertOk()->getContent(), 'QA copyright sentinel');
})->with([
    'shop.home.index',
]);

it('renders the registration agreement text where Vue never compiles it', function () {
    useManpleasureTheme();

    app(CoreConfigRepository::class)->create([
        'channel' => core()->getCurrentChannelCode(),
        'locale' => app()->getLocale(),
        'general' => ['gdpr' => [
            'settings' => ['enabled' => 1],
            'agreement' => ['enabled' => 1, 'agreement_label' => 'QA agreement label', 'agreement_content' => '<p>QA agreement sentinel</p>'],
        ]],
    ]);

    expectIsolatedFromVue(get(route('shop.customers.register.index'))->assertOk()->getContent(), 'QA agreement sentinel');
});

it('sanitizes the registration agreement content before it is shown', function () {
    useManpleasureTheme();

    app(CoreConfigRepository::class)->create([
        'channel' => core()->getCurrentChannelCode(),
        'locale' => app()->getLocale(),
        'general' => ['gdpr' => [
            'settings' => ['enabled' => 1],
            'agreement' => [
                'enabled' => 1,
                'agreement_label' => 'QA agreement label',
                'agreement_content' => '<p>QA agreement kept <strong>bold</strong></p><img src="x" onerror="window.qaAgreement=1"><script>window.qaAgreement=2</script><a href="javascript:window.qaAgreement=3">QA link</a>',
            ],
        ]],
    ]);

    $html = get(route('shop.customers.register.index'))->assertOk()->getContent();

    expect($html)->toContain('QA agreement kept <strong>bold</strong>')
        ->toContain('QA link')
        ->not->toContain('onerror="window.qaAgreement')
        ->not->toContain('<script>window.qaAgreement')
        ->not->toContain('javascript:window.qaAgreement');
});
