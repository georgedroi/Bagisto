<?php

return [
    'trusted_proxies' => array_values(array_filter(
        array_map('trim', explode(',', env('TRUSTED_PROXIES', ''))),
        static function (string $proxy): bool {
            $parts = explode('/', $proxy);

            if (count($parts) > 2 || ! filter_var($parts[0], FILTER_VALIDATE_IP)) {
                return false;
            }

            if (count($parts) === 1) {
                return true;
            }

            $maximum = str_contains($parts[0], ':') ? 128 : 32;

            return ctype_digit($parts[1]) && (int) $parts[1] <= $maximum;
        },
    )),
];
