<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Allow presentational branding while excluding executable content and remote resources.
 */
class ManPleasureLoginPageSanitizer
{
    /**
     * Ids a design may not use, because the login page, its form, its scripts or its Vue app already rely on them.
     */
    protected const RESERVED_IDS = '/^(app|email|password|visibilityIcon|remember|_token|.*csrf.*|.*captcha.*|.*two[-_]?factor.*|.*2fa.*|otp.*)$/i';

    /**
     * Clean branding HTML without allowing a competing credential form, ids that collide with the login page,
     * unsafe link targets or template expressions the login page's Vue app would run.
     */
    public function cleanHtml(?string $html): string
    {
        if (! $html) {
            return '';
        }

        $config = HTMLPurifier_Config::createDefault();

        $config->set(
            'HTML.Allowed',
            'div[class|id|style],section[class|id|style],'
            .'header[class|id|style],footer[class|id|style],'
            .'main[class|id|style],article[class|id|style],'
            .'h1[class|id|style],h2[class|id|style],'
            .'h3[class|id|style],h4[class|id|style],'
            .'p[class|id|style],span[class|id|style],'
            .'strong,em,b,i,u,br,hr,'
            .'a[href|target|rel|class|style],'
            .'img[src|alt|title|width|height|class|style],'
            .'ul[class],ol[class],li[class],'
            .'table[class],thead,tbody,tr,th,td'
        );

        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
        ]);

        $config->set('HTML.SafeIframe', false);
        $config->set('CSS.AllowTricky', false);
        $config->set('Attr.EnableID', true);
        $config->set('Attr.IDBlacklistRegexp', self::RESERVED_IDS);
        $config->set('Attr.AllowedFrameTargets', ['_blank', '_self']);
        $config->set('Attr.AllowedRel', ['noopener', 'noreferrer', 'nofollow']);
        $config->set('URI.Host', parse_url(config('app.url'), PHP_URL_HOST));
        $config->set('URI.DisableExternalResources', true);
        $config->set('Cache.DefinitionImpl', null);
        $definition = $config->getHTMLDefinition(true);

        foreach (['section', 'header', 'footer', 'main', 'article'] as $element) {
            $definition->addElement($element, 'Block', 'Flow', 'Common');
        }

        $purifier = new HTMLPurifier($config);

        return TemplateExpressionFilter::purify($purifier, $html);
    }

    /**
     * Refuse executable or remote-loading CSS, accepting only local stored image URLs.
     */
    public function cleanCss(?string $css): string
    {
        if (! $css) {
            return '';
        }

        $css = preg_replace('~/\*.*?\*/~s', '', $css) ?? '';

        if (str_contains($css, '\\') || preg_match('~[<>]|@import\b|@font-face\b|expression\s*\(|(?:javascript|behavior)\s*:|-moz-binding\b|image(?:-set)?\s*\(|src\s*\(~i', $css)) {
            return '';
        }

        preg_match_all('~url\s*\(\s*(?:"([^"]*)"|\'([^\']*)\'|([^)]*))\s*\)~i', $css, $urls, PREG_SET_ORDER);

        if (preg_match_all('~url\s*\(~i', $css) !== count($urls)) {
            return '';
        }

        foreach ($urls as $match) {
            $url = trim(($match[1] ?? '') ?: (($match[2] ?? '') ?: ($match[3] ?? '')));

            if (! preg_match('~^/storage/(?:[a-z0-9_-]+/)*[a-z0-9_.-]+\.(?:png|jpe?g|webp)$~i', $url)) {
                return '';
            }
        }

        return $css;
    }
}
