<?php

namespace App\Support;

use HTMLPurifier;

/**
 * Purifies admin-written HTML and removes the template expressions an in-DOM Vue app or Blade would evaluate.
 * Purifying decodes entities such as &#123;, so expressions are removed after each pass until the markup stops changing.
 */
class TemplateExpressionFilter
{
    /**
     * Vue and Blade expressions, removed with everything they enclose, as Bagisto's clean_content() does.
     */
    protected const EXPRESSIONS = ['/\{\{.*?\}\}/s', '/\{!!.*?!!\}/s'];

    /**
     * Most purify-and-remove passes before giving up on markup that keeps changing.
     */
    protected const MAX_PASSES = 10;

    /**
     * Give the purified markup with no template expression left, or an empty string if it never settles.
     */
    public static function purify(HTMLPurifier $purifier, string $html): string
    {
        for ($pass = 0; $pass < self::MAX_PASSES; $pass++) {
            $previous = $html;

            $html = preg_replace(self::EXPRESSIONS, '', $purifier->purify($html)) ?? '';

            if ($html === $previous) {
                return trim($html);
            }
        }

        return '';
    }
}
