<?php

namespace App\Support;

/**
 * Validate public mainnet receiving addresses and their network checksum.
 */
class CryptoAddress
{
    /**
     * Validate a supported public receiving address without a network request.
     */
    public static function valid(string $address, string $network): bool
    {
        if ($address === '' || strlen($address) > 90) {
            return false;
        }

        return match ($network) {
            'erc20' => self::ethereum($address),
            'trc20' => strlen($address) === 34 && str_starts_with($address, 'T') && self::base58($address, [0x41]),
            'btc' => str_starts_with(strtolower($address), 'bc1') ? self::segwit($address) : self::base58($address, [0, 5]),
            default => false,
        };
    }

    /**
     * Require ERC-55 checksum casing for Ethereum mainnet-style addresses.
     */
    private static function ethereum(string $address): bool
    {
        if (! preg_match('/\A0x[0-9a-fA-F]{40}\z/', $address)) {
            return false;
        }

        $lower = strtolower(substr($address, 2));
        $hash = Keccak::addressHash($lower);
        $checksummed = '0x';

        for ($i = 0; $i < 40; $i++) {
            $checksummed .= hexdec($hash[$i]) >= 8 ? strtoupper($lower[$i]) : $lower[$i];
        }

        return hash_equals($checksummed, $address);
    }

    /**
     * Decode Base58Check and validate a 20-byte payload and network version.
     */
    private static function base58(string $address, array $versions): bool
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $bytes = [];

        foreach (str_split($address) as $character) {
            $carry = strpos($alphabet, $character);

            if ($carry === false) {
                return false;
            }

            foreach ($bytes as &$byte) {
                $carry += $byte * 58;
                $byte = $carry & 255;
                $carry >>= 8;
            }
            unset($byte);

            while ($carry > 0) {
                $bytes[] = $carry & 255;
                $carry >>= 8;
            }
        }

        $decoded = str_repeat("\0", strspn($address, '1')).implode('', array_map(chr(...), array_reverse($bytes)));

        if (strlen($decoded) !== 25 || ! in_array(ord($decoded[0]), $versions, true)) {
            return false;
        }

        return hash_equals(substr(hash('sha256', hash('sha256', substr($decoded, 0, 21), true), true), 0, 4), substr($decoded, 21));
    }

    /**
     * Validate BIP-173/BIP-350 mainnet witness addresses and canonical padding.
     */
    private static function segwit(string $address): bool
    {
        if ($address !== strtolower($address) && $address !== strtoupper($address)) {
            return false;
        }

        $values = [];
        $alphabet = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';

        foreach (str_split(substr(strtolower($address), 3)) as $character) {
            $value = strpos($alphabet, $character);

            if ($value === false) {
                return false;
            }

            $values[] = $value;
        }

        if (count($values) < 7 || $values[0] > 16) {
            return false;
        }

        $checksum = 1;
        $generators = [0x3B6A57B2, 0x26508E6D, 0x1EA119FA, 0x3D4233DD, 0x2A1462B3];

        foreach ([3, 3, 0, 2, 3, ...$values] as $value) {
            $top = $checksum >> 25;
            $checksum = (($checksum & 0x1FFFFFF) << 5) ^ $value;

            for ($i = 0; $i < 5; $i++) {
                if (($top >> $i) & 1) {
                    $checksum ^= $generators[$i];
                }
            }
        }

        if ($checksum !== ($values[0] === 0 ? 1 : 0x2BC830A3)) {
            return false;
        }

        $bits = 0;
        $buffer = 0;
        $length = 0;

        foreach (array_slice($values, 1, -6) as $value) {
            $buffer = (($buffer << 5) | $value) & 0xFFF;
            $bits += 5;

            if ($bits >= 8) {
                $bits -= 8;
                $length++;
            }
        }

        return $bits < 5 && (($buffer << (8 - $bits)) & 255) === 0
            && $length >= 2 && $length <= 40
            && ($values[0] !== 0 || in_array($length, [20, 32], true));
    }
}
