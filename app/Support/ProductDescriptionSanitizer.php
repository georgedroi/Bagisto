<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Cleans a product description before it is saved, because the storefront prints it as HTML.
 * It keeps the editor's text formatting and pictures stored on this site, and drops scripts,
 * event handlers and pictures from other hosts.
 */
class ProductDescriptionSanitizer
{
    /**
     * Tags and attributes the description editor produces.
     */
    protected const ALLOWED = 'p[style],div[style],span[style],br,strong,b,em,i,u,ul[style],ol[style],li[style],img[src|alt|style]';

    /**
     * Style properties kept: alignment and the picture sizing the editor sets.
     */
    protected const STYLES = ['text-align', 'max-width', 'width', 'height'];

    /**
     * Give the description with only the allowed markup left.
     */
    public function clean(?string $html): string
    {
        if (! $html) {
            return '';
        }

        $config = HTMLPurifier_Config::createDefault();

        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed', self::ALLOWED);
        $config->set('CSS.AllowedProperties', self::STYLES);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true]);
        $config->set('URI.DisableExternalResources', true);

        return trim((new HTMLPurifier($config))->purify($html));
    }
}
