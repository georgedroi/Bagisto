<?php

return [
    'zonerate' => [
        'status' => 'Status',
        'title' => 'Zone Rate Shipping',
        'info' => 'Delivery fee by destination zone and order subtotal. Only destinations that match a zone can check out.',
        'method-title' => 'Title',
        'description' => 'Description',
        'zones' => 'Zones',
        'zones-info' => 'One zone per line: name: COUNTRY: postcode ranges or state names. A zone with matchers wins over a country-wide zone. Example: east: MY: 87000-91999, 93000-98999, Sabah, Sarawak, Labuan (next line) west: MY. Lines starting with # are ignored.',
        'rates' => 'Rates',
        'rates-info' => 'One rate per line: zone, minimum subtotal, maximum subtotal, fee. Use * as the maximum for no upper limit. Amounts are in the base currency (MYR) and the subtotal is the items total before discounts. Example: west, 0, 200, 15',
    ],
];
