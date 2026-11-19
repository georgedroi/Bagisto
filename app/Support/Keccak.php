<?php

namespace App\Support;

/**
 * Compute legacy Keccak-256 for Ethereum address checksums using unsigned lane pairs.
 */
class Keccak
{
    /**
     * Hash an ASCII hexadecimal Ethereum address without its prefix.
     */
    public static function addressHash(string $value): string
    {
        if (! preg_match('/\A[0-9a-f]{40}\z/', $value)) {
            throw new \InvalidArgumentException('Expected a lowercase hexadecimal address.');
        }

        $bytes = $value."\x01".str_repeat("\0", 94)."\x80";
        $state = array_fill(0, 25, [0, 0]);

        for ($i = 0; $i < 17; $i++) {
            $state[$i] = array_values(unpack('V2', substr($bytes, $i * 8, 8)));
        }

        $rotations = [0, 1, 62, 28, 27, 36, 44, 6, 55, 20, 3, 10, 43, 25, 39, 41, 45, 15, 21, 8, 18, 2, 61, 56, 14];
        $constants = [
            '0000000000000001', '0000000000008082', '800000000000808a', '8000000080008000',
            '000000000000808b', '0000000080000001', '8000000080008081', '8000000000008009',
            '000000000000008a', '0000000000000088', '0000000080008009', '000000008000000a',
            '000000008000808b', '800000000000008b', '8000000000008089', '8000000000008003',
            '8000000000008002', '8000000000000080', '000000000000800a', '800000008000000a',
            '8000000080008081', '8000000000008080', '0000000080000001', '8000000080008008',
        ];

        foreach ($constants as $constant) {
            $columns = array_fill(0, 5, [0, 0]);

            for ($x = 0; $x < 5; $x++) {
                for ($y = 0; $y < 5; $y++) {
                    $columns[$x] = self::xor($columns[$x], $state[$x + 5 * $y]);
                }
            }

            $permuted = $state;

            for ($x = 0; $x < 5; $x++) {
                $delta = self::xor($columns[($x + 4) % 5], self::rotate($columns[($x + 1) % 5], 1));

                for ($y = 0; $y < 5; $y++) {
                    $index = $x + 5 * $y;
                    $permuted[$y + 5 * ((2 * $x + 3 * $y) % 5)] = self::rotate(self::xor($state[$index], $delta), $rotations[$index]);
                }
            }

            for ($x = 0; $x < 5; $x++) {
                for ($y = 0; $y < 5; $y++) {
                    for ($word = 0; $word < 2; $word++) {
                        $state[$x + 5 * $y][$word] = ($permuted[$x + 5 * $y][$word] ^ (~$permuted[($x + 1) % 5 + 5 * $y][$word] & $permuted[($x + 2) % 5 + 5 * $y][$word])) & 0xFFFFFFFF;
                    }
                }
            }

            $state[0] = self::xor($state[0], [hexdec(substr($constant, 8)), hexdec(substr($constant, 0, 8))]);
        }

        return bin2hex(implode('', array_map(fn ($lane) => pack('V2', ...$lane), array_slice($state, 0, 4))));
    }

    /**
     * XOR two unsigned 64-bit lane pairs.
     */
    private static function xor(array $left, array $right): array
    {
        return [$left[0] ^ $right[0], $left[1] ^ $right[1]];
    }

    /**
     * Rotate an unsigned 64-bit lane without platform signed right shifts.
     */
    private static function rotate(array $lane, int $bits): array
    {
        if ($bits >= 32) {
            $lane = [$lane[1], $lane[0]];
            $bits -= 32;
        }

        if ($bits === 0) {
            return $lane;
        }

        return [
            (($lane[0] << $bits) | ($lane[1] >> (32 - $bits))) & 0xFFFFFFFF,
            (($lane[1] << $bits) | ($lane[0] >> (32 - $bits))) & 0xFFFFFFFF,
        ];
    }
}
