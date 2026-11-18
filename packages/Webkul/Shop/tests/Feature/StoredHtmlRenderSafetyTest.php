<?php

use App\Support\ProductDescriptionSanitizer;
use Webkul\Attribute\Models\Attribute;
use Webkul\Category\Models\Category;
use Webkul\CMS\Repositories\PageRepository;
use Webkul\Faker\Helpers\Product as ProductFaker;
use Webkul\Product\Repositories\ProductAttributeValueRepository;

use function Pest\Laravel\get;

beforeEach(function () {
    config(['responsecache.enabled' => false]);
});

/**
 * Bodies already in the database: what the pre-fix clean_content() left from a crafted payload, and markup written without any cleaning.
 */
dataset('stored legacy html', [
    'left by the old single-pass filter' => ['<img src="/qa-missing.png" alt="<a title="onerror=window.__qa_xss=1;//">x</a>'],
    'written without cleaning' => ['<p>QA</p><img src="/qa-missing.png" onerror="window.__qa_xss=1"><script>window.__qa_xss=1</script>'],
]);

/**
 * Use the production storefront theme for the current request.
 */
function useStoredHtmlTheme(string $theme): void
{
    if ($theme !== 'manpleasure') {
        return;
    }

    $channel = clone core()->getCurrentChannel();
    $channel->theme = 'manpleasure';

    core()->setCurrentChannel($channel);
}

/**
 * Elements in the page that would run the QA marker as script: an event handler attribute or a script body.
 *
 * @return array<int, string>
 */
function executableQaMarkers(string $html): array
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8"?>'.$html);
    $found = [];

    foreach ((new DOMXPath($document))->query('//*') as $element) {
        foreach ($element->attributes as $attribute) {
            if (str_starts_with(strtolower($attribute->name), 'on') && str_contains($attribute->value, '__qa_xss')) {
                $found[] = $element->nodeName.'['.$attribute->name.']';
            }
        }

        if ($element->nodeName === 'script' && str_contains($element->textContent, '__qa_xss')) {
            $found[] = 'script';
        }
    }

    return $found;
}

it('shows a stored CMS page body without runnable script', function (string $theme, string $stored) {
    useStoredHtmlTheme($theme);

    $page = app(PageRepository::class)->create([
        'page_title' => 'QA stored html',
        'url_key' => 'qa-stored-html',
        'html_content' => '<p>Placeholder</p>',
        'meta_title' => 'QA',
        'meta_keywords' => '',
        'meta_description' => '',
        'channels' => [core()->getCurrentChannel()->id],
        'locale' => app()->getLocale(),
    ]);

    $page->translations()->update(['html_content' => $stored]);

    $html = get(route('shop.cms.page', 'qa-stored-html'))->assertOk()->getContent();

    expect(executableQaMarkers($html))->toBeEmpty();
})->with(['default', 'manpleasure'])->with('stored legacy html');

it('shows a stored category description without runnable script', function (string $theme, string $stored) {
    useStoredHtmlTheme($theme);

    $category = Category::factory()->create(['status' => 1, 'display_mode' => 'products_and_description']);
    $category->translateOrNew(app()->getLocale())->fill([
        'name' => 'QA stored category',
        'slug' => 'qa-stored-category',
        'description' => $stored,
    ]);
    $category->save();

    $html = get(route('shop.product_or_category.index', 'qa-stored-category'))->assertOk()->getContent();

    expect(executableQaMarkers($html))->toBeEmpty();
})->with(['default', 'manpleasure'])->with('stored legacy html');

it('shows stored product descriptions without runnable script', function (string $theme, string $stored) {
    useStoredHtmlTheme($theme);

    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    foreach (['description', 'short_description'] as $code) {
        app(ProductAttributeValueRepository::class)->updateOrCreate(
            ['product_id' => $product->id, 'attribute_id' => Attribute::query()->where('code', $code)->firstOrFail()->id, 'channel' => null, 'locale' => app()->getLocale()],
            ['text_value' => $stored],
        );
    }

    $html = get(route('shop.product_or_category.index', $product->fresh()->url_key))->assertOk()->getContent();

    expect(executableQaMarkers($html))->toBeEmpty();
})->with(['default', 'manpleasure'])->with('stored legacy html');

it('keeps normal stored formatting when it is shown', function () {
    $stored = '<h2 style="text-align:center;">QA heading</h2><p><strong>Bold</strong> <a href="https://example.com/a" title="t">link</a></p><table><tbody><tr><td>cell</td></tr></tbody></table>';

    $page = app(PageRepository::class)->create([
        'page_title' => 'QA formatting',
        'url_key' => 'qa-stored-formatting',
        'html_content' => $stored,
        'meta_title' => 'QA',
        'meta_keywords' => '',
        'meta_description' => '',
        'channels' => [core()->getCurrentChannel()->id],
        'locale' => app()->getLocale(),
    ]);

    get(route('shop.cms.page', 'qa-stored-formatting'))->assertOk()->assertSee($stored, false);
});

it('keeps a description from the product entry form unchanged when it is shown', function () {
    $stored = app(ProductDescriptionSanitizer::class)->clean('<p style="text-align:center;"><strong>Bold</strong> <em>soft</em></p><ul><li>one</li></ul><img src="/storage/qa.png" alt="pic" style="max-width:100%;height:auto;">');

    $product = (new ProductFaker)->getSimpleProductFactory()->create();

    app(ProductAttributeValueRepository::class)->updateOrCreate(
        ['product_id' => $product->id, 'attribute_id' => Attribute::query()->where('code', 'description')->firstOrFail()->id, 'channel' => null, 'locale' => app()->getLocale()],
        ['text_value' => $stored],
    );

    expect($stored)->toContain('max-width:100%');

    get(route('shop.product_or_category.index', $product->fresh()->url_key))->assertOk()->assertSee(str_replace(' />', '>', $stored), false);
});
