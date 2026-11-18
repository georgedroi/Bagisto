<?php

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Stevebauman\Purify\PurifyManager;
use Webkul\Core\Core;
use Webkul\Theme\Sections\StaticContent;

/**
 * Render actual static Blade content fragments without a database, environment loading or authentication changes.
 */
function contentBoundaryRender(string $source, array $values = []): string
{
    $source = preg_replace('/<(\/?)x-shop::form\.control-group\.label\b/', '<$1label', $source);
    $compiler = new BladeCompiler(new Filesystem, sys_get_temp_dir());
    $__env = app('view');
    extract($values, EXTR_SKIP);
    ob_start();

    try {
        eval('?>'.$compiler->compileString($source));

        return ob_get_contents();
    } finally {
        ob_end_clean();
    }
}

/**
 * Give each immediate static content container around an output expression in the real Blade source.
 */
function contentBoundaryFragments(string $file, string $expression): array
{
    $source = file_get_contents(dirname(__DIR__, 2).'/'.$file);
    $offset = 0;
    $fragments = [];

    while (($position = strpos($source, $expression, $offset)) !== false) {
        $offset = $position + strlen($expression);

        if (in_array($source[$offset] ?? '', ['"', "'"])) {
            continue;
        }

        preg_match_all('/<(x-shop::form\.control-group\.label|div|p|label|span|a|td|option)\b[^>]*>/s', substr($source, 0, $position), $tags, PREG_OFFSET_CAPTURE);
        $tag = end($tags[0]);
        $name = end($tags[1])[0];
        $end = strpos($source, '</'.$name.'>', $position);

        if ($tag === false || $end === false) {
            throw new RuntimeException('No static container for '.$expression);
        }

        $fragments[] = substr($source, $tag[1], $end + strlen($name) + 3 - $tag[1]);
    }

    if ($fragments === []) {
        throw new RuntimeException('No output expression in '.$file);
    }

    return $fragments;
}

/**
 * Require visible content to survive HTML parsing entirely inside a Vue compilation boundary.
 */
function expectContentBoundary(string $html, string $needle): void
{
    $document = new DOMDocument;
    @$document->loadHTML('<?xml encoding="utf-8"?><div id="boundary-root">'.$html.'</div>');
    $xpath = new DOMXPath($document);
    $nodes = $xpath->query('//text()[contains(., "'.$needle.'")]');

    expect($nodes->length)->toBeGreaterThan(0);

    foreach ($nodes as $node) {
        expect($xpath->query('ancestor::*[@v-pre]', $node)->length)->toBeGreaterThan(0);
    }
}

beforeEach(function () {
    $this->previousContainer = Container::getInstance();
    $this->previousFacade = Facade::getFacadeApplication();
    $this->root = dirname(__DIR__, 2);
    $purify = require $this->root.'/config/purify.php';
    $purify['configs']['default']['Cache.DefinitionImpl'] = null;
    $purify['serializer'] = [];
    $this->app = new Application($this->root);
    $this->app->instance('config', new Repository([
        'app' => ['debug' => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN)],
        'purify' => $purify,
        'filesystems' => ['default' => 'local'],
    ]));
    $this->app->register(FilesystemServiceProvider::class);
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($this->app);
    $this->app->instance('purify', new PurifyManager($this->app));
    $factory = new Factory(new EngineResolver, new FileViewFinder(new Filesystem, []), new Dispatcher($this->app));
    $factory->setContainer($this->app);
    $this->app->instance('view', $factory);
    $this->app->instance('url', new class
    {
        /**
         * Resolve links in isolated fragments without an HTTP application.
         */
        public function route(string $name, mixed $parameters = [], bool $absolute = true): string
        {
            return '/qa';
        }
    });
    require_once $this->root.'/packages/Webkul/Core/src/Http/helpers.php';
});

afterEach(function () {
    Facade::clearResolvedInstances();
    Facade::setFacadeApplication($this->previousFacade);
    Container::setInstance($this->previousContainer);
});

it('isolates legacy product rich text after HTML parsing in both themes', function (string $theme, string $field) {
    $file = $theme === 'default'
        ? 'packages/Webkul/Shop/src/Resources/views/products/view.blade.php'
        : 'resources/themes/manpleasure/views/products/view.blade.php';
    $product = (object) [$field => '<h2>Boundary sentinel {{ 7*191 }}</h2><p><strong>Gentle care</strong></p><table><tr><td>Size</td></tr></table>'];

    foreach (contentBoundaryFragments($file, '{!! clean_content((string) $product->'.$field.') !!}') as $fragment) {
        $html = contentBoundaryRender($fragment, compact('product'));
        expectContentBoundary($html, 'Boundary sentinel');
        expect($html)->toContain('<strong>Gentle care</strong>')->toContain('<td>Size</td>')->not->toContain('7*191');
    }
})->with(['default', 'manpleasure'])->with(['description', 'short_description']);

it('isolates desktop and mobile product attribute text in both themes', function (string $theme, string $field) {
    $file = $theme === 'default'
        ? 'packages/Webkul/Shop/src/Resources/views/products/view.blade.php'
        : 'resources/themes/manpleasure/views/products/view.blade.php';
    $customAttributeValue = [$field => 'Boundary sentinel {{ 7*191 }}'];
    $expression = $field === 'label' ? "{{ \$customAttributeValue['label'] }}" : "{{ \$customAttributeValue['value'] }}";

    $expressions = [$expression];

    if ($field === 'value' && str_contains(file_get_contents($this->root.'/'.$file), "{{ \$customAttributeValue['value'] ?? '-' }}")) {
        $expressions[] = "{{ \$customAttributeValue['value'] ?? '-' }}";
    }

    foreach ($expressions as $output) {
        foreach (contentBoundaryFragments($file, $output) as $fragment) {
            expectContentBoundary(contentBoundaryRender($fragment, compact('customAttributeValue')), 'Boundary sentinel');
        }
    }
})->with(['default', 'manpleasure'])->with(['label', 'value']);

it('isolates and sanitizes legacy static homepage content without losing normal formatting', function () {
    $source = file_get_contents($this->root.'/packages/Webkul/Shop/src/Resources/views/home/index.blade.php');
    $case = '@case (\\Webkul\\Theme\\Enums\\SectionTypeEnum::STATIC_CONTENT->value)';
    $start = strpos($source, $case) + strlen($case);
    $fragment = substr($source, $start, strpos($source, '@break', $start) - $start);
    $data = ['html' => '<h2>Boundary sentinel {{ 7*191 }}</h2><p><a href="/help">Help</a></p><img src="/storage/probe.png" onerror="window.boundaryProbe=1">', 'css' => ''];
    $section = new class
    {
        /**
         * Use the real section sanitizer without persistence.
         */
        public function getTypeInstance(): StaticContent
        {
            return new StaticContent;
        }
    };
    $html = contentBoundaryRender($fragment, compact('data', 'section'));

    expectContentBoundary($html, 'Boundary sentinel');
    expect($html)->not->toContain('onerror')->toContain('<a href="/help">Help</a>');
});

it('escapes the default checkout message while retaining line breaks and isolating template syntax', function () {
    $file = 'packages/Webkul/Shop/src/Resources/views/checkout/success.blade.php';
    $source = file_get_contents($this->root.'/'.$file);
    preg_match('/<p[^>]*>\s*@if \(! empty\(\$order->checkout_message\)\)[\s\S]*?<\/p>/', $source, $match);
    $order = (object) ['checkout_message' => "Boundary sentinel {{ 7*191 }}\n<img src=x onerror=window.boundaryProbe=1>"];
    $html = contentBoundaryRender($match[0], compact('order'));

    expectContentBoundary($html, 'Boundary sentinel');
    expect($html)->toContain('<br')->toContain('&lt;img')->not->toContain('<img');
});

it('isolates and sanitizes both cookie policy description outputs', function (string $expression) {
    $this->app->instance(Core::class, new class
    {
        /**
         * Supply synthetic configuration text without a database or customer data.
         */
        public function getConfigData(string $key): string
        {
            return '<strong>Boundary sentinel {{ 7*191 }}</strong><img src=x onerror=window.boundaryProbe=1>';
        }
    });

    foreach (contentBoundaryFragments('packages/Webkul/Shop/src/Resources/views/components/layouts/cookie/consent.blade.php', $expression) as $fragment) {
        $html = contentBoundaryRender($fragment, ['key' => 'basic_interaction']);
        expectContentBoundary($html, 'Boundary sentinel');
        expect($html)->toContain('<strong>')->not->toContain('onerror');
    }
})->with([
    "core()->getConfigData('general.gdpr.cookie_consent.strictly_necessary')",
    "core()->getConfigData('general.gdpr.cookie_consent.' . \$key)",
]);

it('stabilizes recombined expressions or fails closed without returning executable delimiters', function (string $html) {
    $clean = clean_content($html);
    $decoded = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5);

    expect($decoded)->not->toMatch('/\{\{.*?\}\}|\{!!.*?!!\}/s')
        ->and(clean_content($clean))->toBe($clean);
})->with([
    'directive and entity recombination' => ["<p>Keep {@if&#123;\n7*191\n}}</p>"],
    'comment and directive recombination' => ['<p>Keep {<!-- -->@if{ 7*191 }}</p>'],
    'nested raw echoes' => ['<p>Keep {!!{!! 7*191 !!}!!}</p>'],
    'pass limit' => ['<p>Keep '.str_repeat('{', 30).' 7*191 '.str_repeat('}', 30).'</p>'],
]);

it('retains ordinary rich text and standalone braces under repeated shared cleanup', function () {
    $html = '<h2>Care guide</h2><p><a href="/help">Help</a> <strong>Daily</strong> { care }</p>'
        .'<ul><li>Gentle</li></ul><table><tr><td>Size</td></tr></table>';
    $once = clean_content($html);

    expect($once)->toContain('<h2>Care guide</h2>')->toContain('<a href="/help">Help</a>')
        ->toContain('<strong>Daily</strong>')->toContain('{ care }')->toContain('<td>Size</td>')
        ->and(clean_content($once))->toBe($once);
});

it('isolates shared mini-cart and return-policy configuration text', function (string $file, string $expression) {
    $this->app->instance(Core::class, new class
    {
        /**
         * Supply editable policy text without reading configuration records.
         */
        public function getConfigData(string $key): string
        {
            return 'Boundary sentinel {{ 7*191 }}';
        }
    });

    foreach (contentBoundaryFragments($file, $expression) as $fragment) {
        expectContentBoundary(contentBoundaryRender($fragment), 'Boundary sentinel');
    }
})->with([
    ['packages/Webkul/Shop/src/Resources/views/checkout/cart/mini-cart.blade.php', "{{ core()->getConfigData('sales.checkout.mini_cart.offer_info')}}"],
    ['packages/Webkul/Shop/src/Resources/views/customers/account/rma/terms.blade.php', "{{ core()->getConfigData('sales.rma.setting.return_policy') }}"],
]);

it('isolates editable return labels, statuses and saved product text', function (string $expression) {
    $sentinel = 'Boundary sentinel {{ 7*191 }}';
    $field = (object) ['customField' => (object) ['label' => $sentinel]];
    $rmaStatus = (object) ['title' => $sentinel, 'color' => '#112233'];
    $item = (object) ['orderItem' => (object) ['name' => $sentinel, 'product' => (object) ['url_key' => 'qa']], 'reason' => (object) ['title' => $sentinel]];
    $attribute = ['attribute_name' => $sentinel, 'option_label' => $sentinel];

    foreach (contentBoundaryFragments('packages/Webkul/Shop/src/Resources/views/customers/account/rma/view.blade.php', $expression) as $fragment) {
        expectContentBoundary(contentBoundaryRender($fragment, compact('field', 'rmaStatus', 'item', 'attribute')), 'Boundary sentinel');
    }
})->with([
    '{{ $field->customField->label }}',
    '{{ $rmaStatus->title }}',
    '{{ $item->orderItem->name }}',
    "{{ \$attribute['attribute_name'] }}",
    "{{ \$attribute['option_label'] }}",
    '{{ $item->reason->title }}',
]);

it('isolates editable return form labels and choices without disabling the controls', function (string $expression) {
    $sentinel = 'Boundary sentinel {{ 7*191 }}';
    $attribute = (object) ['label' => $sentinel, 'id' => 7, 'is_required' => '0'];
    $option = (object) ['name' => $sentinel, 'value' => 'qa'];
    $index = 0;
    $loop = (object) ['index' => 0];

    foreach (contentBoundaryFragments('packages/Webkul/Shop/src/Resources/views/customers/account/rma/create.blade.php', $expression) as $fragment) {
        expectContentBoundary(contentBoundaryRender($fragment, compact('attribute', 'option', 'index', 'loop')), 'Boundary sentinel');
    }
})->with(['{{ $attribute->label }}', '{{ $option->name }}']);

it('keeps an uploaded Media Bank filename out of the admin Vue compiler', function () {
    $asset = (object) ['original_name' => 'Boundary sentinel {{ 7*191 }}.png'];

    foreach (contentBoundaryFragments('app/Resources/views/media/index.blade.php', '{{ $asset->original_name }}') as $fragment) {
        expectContentBoundary(contentBoundaryRender($fragment, compact('asset')), 'Boundary sentinel');
    }
});
