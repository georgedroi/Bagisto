<?php

namespace Webkul\Shipping\Support;

class ZoneRateTable
{
    /**
     * Zones keyed by name, each with its country and the matchers that select it.
     *
     * @var array<string, array{country: string, ranges: array, texts: array}>
     */
    protected array $zones = [];

    /**
     * Rate rows in the order they were written.
     *
     * @var array<int, array{zone: string, min: float, max: float|null, rate: float}>
     */
    protected array $rates = [];

    /**
     * Create a new table from the two configuration texts.
     */
    public function __construct(?string $zones, ?string $rates)
    {
        $this->zones = $this->parseZones((string) $zones);

        $this->rates = $this->parseRates((string) $rates);
    }

    /**
     * Find the delivery fee for a destination and an order subtotal, or null when the destination is not served.
     *
     * @return array{zone: string, rate: float}|null
     */
    public function quote(?string $country, ?string $postcode, ?string $state, float $subtotal): ?array
    {
        $zone = $this->zoneFor($country, $postcode, $state);

        if ($zone === null) {
            return null;
        }

        $rate = $this->rateFor($zone, $subtotal);

        return $rate === null ? null : ['zone' => $zone, 'rate' => $rate];
    }

    /**
     * Find the zone that serves a destination. A zone with matchers wins over a country-wide zone.
     */
    public function zoneFor(?string $country, ?string $postcode, ?string $state): ?string
    {
        $country = strtoupper(trim((string) $country));

        if ($country === '') {
            return null;
        }

        $fallback = null;

        foreach ($this->zones as $name => $zone) {
            if ($zone['country'] !== $country) {
                continue;
            }

            if (empty($zone['ranges']) && empty($zone['texts'])) {
                $fallback ??= $name;

                continue;
            }

            if ($this->matches($zone, $postcode, $state)) {
                return $name;
            }
        }

        return $fallback;
    }

    /**
     * Find the delivery fee of a zone for an order subtotal, or null when no rate row covers it.
     */
    public function rateFor(string $zone, float $subtotal): ?float
    {
        $subtotal = round($subtotal, 2);

        foreach ($this->rates as $row) {
            if ($row['zone'] !== $zone || $subtotal < $row['min']) {
                continue;
            }

            if ($row['max'] === null || $subtotal <= $row['max']) {
                return $row['rate'];
            }
        }

        return null;
    }

    /**
     * Whether any zone is configured.
     */
    public function isEmpty(): bool
    {
        return empty($this->zones) || empty($this->rates);
    }

    /**
     * Whether a destination satisfies one of the zone's postcode ranges or state names.
     */
    protected function matches(array $zone, ?string $postcode, ?string $state): bool
    {
        $digits = preg_replace('/\D+/', '', (string) $postcode);

        if ($digits !== '') {
            $number = (int) $digits;

            foreach ($zone['ranges'] as [$from, $to]) {
                if ($number >= $from && $number <= $to) {
                    return true;
                }
            }
        }

        $state = $this->normalize($state);

        if ($state === '') {
            return false;
        }

        foreach ($zone['texts'] as $text) {
            if (str_contains($state, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse lines such as "east: MY: 87000-91999, Sabah" into zones.
     */
    protected function parseZones(string $text): array
    {
        $zones = [];

        foreach ($this->lines($text) as $line) {
            $parts = array_map('trim', explode(':', $line, 3));

            if (count($parts) < 2 || $parts[0] === '' || strlen($parts[1]) !== 2) {
                continue;
            }

            $zone = ['country' => strtoupper($parts[1]), 'ranges' => [], 'texts' => []];

            foreach (array_filter(array_map('trim', explode(',', $parts[2] ?? ''))) as $matcher) {
                if (preg_match('/^(\d+)\s*-\s*(\d+)$/', $matcher, $range)) {
                    $zone['ranges'][] = [(int) $range[1], (int) $range[2]];
                } elseif (ctype_digit($matcher)) {
                    $zone['ranges'][] = [(int) $matcher, (int) $matcher];
                } elseif ($this->normalize($matcher) !== '') {
                    $zone['texts'][] = $this->normalize($matcher);
                }
            }

            $zones[strtolower($parts[0])] = $zone;
        }

        return $zones;
    }

    /**
     * Parse lines such as "west, 200.01, 300, 18" into rate rows. A max of "*" or empty means no upper limit.
     */
    protected function parseRates(string $text): array
    {
        $rates = [];

        foreach ($this->lines($text) as $line) {
            $parts = array_map('trim', explode(',', $line));

            if (count($parts) < 4 || $parts[0] === '' || ! is_numeric($parts[1]) || ! is_numeric($parts[3])) {
                continue;
            }

            $max = $parts[2];

            if ($max !== '' && $max !== '*' && ! is_numeric($max)) {
                continue;
            }

            $rates[] = [
                'zone' => strtolower($parts[0]),
                'min' => round((float) $parts[1], 2),
                'max' => ($max === '' || $max === '*') ? null : round((float) $max, 2),
                'rate' => round((float) $parts[3], 2),
            ];
        }

        return $rates;
    }

    /**
     * Split a configuration text into meaningful lines, skipping blank lines and lines starting with #.
     *
     * @return array<int, string>
     */
    protected function lines(string $text): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R/', $text) ?: []),
            fn (string $line) => $line !== '' && ! str_starts_with($line, '#')
        ));
    }

    /**
     * Lowercase a value and keep letters and digits only, so "W.P. Labuan" and "wp labuan" compare equal.
     */
    protected function normalize(?string $value): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower((string) $value));
    }
}
