<?php

it('leaves no template expression however the delimiters are written', function (string $html) {
    $clean = html_entity_decode(clean_content($html), ENT_QUOTES | ENT_HTML5);

    expect(preg_match('/\{\{.*\}\}/s', $clean))->toBe(0)
        ->and(preg_match('/\{!!.*!!\}/s', $clean))->toBe(0)
        ->and($clean)->toContain('Keep this line');
})->with([
    'multi-line' => ["<p>Keep this line {{\n7*191\n}}</p>"],
    'multi-line entity encoded' => ["<p>Keep this line &#123;&#123;\n7*191\n&#125;&#125;</p>"],
    'split across tags' => ['<p>Keep this line</p><p>{{ 7*191 /*</p><p>*/ }}</p>'],
    'reassembled by a removed directive' => ['<p>Keep this line {@if{ 7*191 }}</p>'],
    'reassembled by a removed php tag' => ['<p>Keep this line {<?php ?>{ 7*191 }}</p>'],
    'reassembled by a removed comment' => ['<p>Keep this line {<!-- x -->{ 7*191 }}</p>'],
    'reassembled by a removed expression' => ['<p>Keep this line {{{ }}{ 7*191 }}} {!{!! !!}! 7*191 !!}</p>'],
    'nested delimiters' => ['<p>Keep this line {{{{ 7*191 }}}} {{ {{ 7*191 }} }}</p>'],
    'raw echo across lines' => ["<p>Keep this line {!!\n7*191\n!!}</p>"],
]);

it('keeps ordinary rich text unchanged', function () {
    $html = '<h2 class="qa-heading">Shipping guide</h2>'
        .'<p style="text-align:center;">Read our <a href="https://example.com/returns" title="Returns">returns policy</a> and <strong>discreet</strong> <em>packing</em> notes.</p>'
        .'<ul><li>First point</li><li>Second point</li></ul>'
        .'<p><img src="/storage/qa/guide.png" alt="Guide" width="320" height="200"></p>'
        .'<table class="qa-table"><thead><tr><th>Zone</th><th>Days</th></tr></thead><tbody><tr><td>West Malaysia</td><td>2</td></tr></tbody></table>'
        .'<blockquote>Prices show the cost of 10 { items } in RM, } and { alone.</blockquote>';

    expect(clean_content($html))->toBe($html)
        ->and(clean_content(clean_content($html)))->toBe($html);
});

it('still removes scripts, event handlers and unsafe links', function () {
    $clean = clean_content('<p onclick="alert(1)">Text</p><script>alert(2)</script><a href="javascript:alert(3)">link</a><img src="x" onerror="alert(4)">');

    expect($clean)->toContain('<p>Text</p>')
        ->not->toContain('onclick')
        ->not->toContain('<script')
        ->not->toContain('javascript:')
        ->not->toContain('onerror');
});
