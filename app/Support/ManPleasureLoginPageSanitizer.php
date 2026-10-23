<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

class ManPleasureLoginPageSanitizer
{
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
            .'button[type|class|style|disabled],'
            .'label[for|class],'
            .'input[type|name|value|placeholder|class|id|disabled|readonly],'
            .'textarea[name|placeholder|class|id|rows|disabled|readonly],'
            .'select[name|class|id|disabled],'
            .'option[value|selected],'
            .'form[action|method|class|id],'
            .'table[class],thead,tbody,tr,th,td'
        );

        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'data' => false,
        ]);

        $config->set('HTML.SafeIframe', false);
        $config->set('CSS.AllowTricky', false);

        $purifier = new HTMLPurifier($config);

        return $purifier->purify($html);
    }

    public function cleanCss(?string $css): string
    {
        if (! $css) {
            return '';
        }

        // CSS is stored separately. Reject executable or externally imported
        // content before allowing it to be saved.
        $css = preg_replace(
            '~</?style\b|@import\b|expression\s*\(|javascript\s*:|behavior\s*:|-moz-binding\b~i',
            '',
            $css
        );

        return $css ?? '';
    }
}
