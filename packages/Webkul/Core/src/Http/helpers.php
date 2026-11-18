<?php

use Stevebauman\Purify\Facades\Purify;
use Webkul\Core\Facades\Acl;
use Webkul\Core\Facades\Core;
use Webkul\Core\Facades\Menu;
use Webkul\Core\Facades\SystemConfig;

if (! function_exists('core')) {
    /**
     * Core helper.
     *
     * @return Webkul\Core\Core
     */
    function core()
    {
        return Core::getFacadeRoot();
    }
}

if (! function_exists('menu')) {
    /**
     * Menu helper.
     *
     * @return Webkul\Core\Menu
     */
    function menu()
    {
        return Menu::getFacadeRoot();
    }
}

if (! function_exists('acl')) {
    /**
     * Acl helper.
     *
     * @return Webkul\Core\Acl
     */
    function acl()
    {
        return Acl::getFacadeRoot();
    }
}

if (! function_exists('system_config')) {
    /**
     * System Config helper.
     *
     * @return Webkul\Core\SystemConfig
     */
    function system_config()
    {
        return SystemConfig::getFacadeRoot();
    }
}

if (! function_exists('clean_path')) {
    /**
     * Clean path.
     */
    function clean_path(string $path): string
    {
        return collect(explode('/', $path))
            ->filter(fn ($segment) => ! empty($segment))
            ->join('/');
    }
}

if (! function_exists('clean_content')) {
    /**
     * Clean content. Purifying decodes entities and removing one token can join the pieces of another, so the
     * content is purified and stripped of template syntax, across lines, until a pass removes nothing. Content that
     * never settles is dropped. Pages must still print the result inside v-pre: this is not the Vue boundary.
     */
    function clean_content(string $content): string
    {
        $patterns = [
            '/\{\{.*?\}\}/s',
            '/\{!!.*?!!\}/s',
            '/@(php|if|else|endif|foreach|endforeach|for|endfor|while|endwhile|switch|endswitch|case|break|continue|include|extends|section|endsection|yield|push|endpush|stack|endstack)/',
            '/<\?php.*?\?>/s',
        ];

        $cleaned = $content;

        $settled = false;

        for ($pass = 0; $pass < 10 && ! $settled; $pass++) {
            $purified = Purify::clean($cleaned);

            $cleaned = preg_replace($patterns, '', $purified) ?? '';

            $settled = $cleaned === $purified;
        }

        if (! $settled) {
            return '';
        }

        $cleaned = str_replace(
            ['{{', '}}', '{!!', '!!}'],
            ['&#123;&#123;', '&#125;&#125;', '&#123;!!', '!!&#125;'],
            $cleaned
        );

        return $cleaned;
    }
}

if (! function_exists('array_permutation')) {
    function array_permutation($input)
    {
        $results = [];

        foreach ($input as $key => $values) {
            if (empty($values)) {
                continue;
            }

            if (empty($results)) {
                foreach ($values as $value) {
                    $results[] = [$key => $value];
                }
            } else {
                $append = [];

                foreach ($results as &$result) {
                    $result[$key] = array_shift($values);

                    $copy = $result;

                    foreach ($values as $item) {
                        $copy[$key] = $item;
                        $append[] = $copy;
                    }

                    array_unshift($values, $result[$key]);
                }

                $results = array_merge($results, $append);
            }
        }

        return $results;
    }
}
