<?php

namespace App\Support;

/**
 * Make a client-supplied file name safe to keep as a display label.
 */
class UploadedFileName
{
    /**
     * Drop template delimiters and invisible control or format characters, collapse whitespace and cap the length.
     */
    public static function clean(string $name, int $limit = 255): string
    {
        $spaced = preg_replace('/\s+/u', ' ', $name) ?? '';

        $visible = preg_replace('/[{}\p{Cc}\p{Cf}]+/u', '', $spaced) ?? '';

        return mb_substr(trim(preg_replace('/ {2,}/', ' ', $visible) ?? ''), 0, $limit);
    }
}
