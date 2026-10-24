<?php

return [
    'flatrate' => [
        'code' => 'flatrate',
        'title' => 'Flat Rate',
        'description' => 'Flat Rate Shipping',
        'active' => true,
        'default_rate' => '10',
        'type' => 'per_unit',
        'class' => 'Webkul\Shipping\Carriers\FlatRate',
    ],

    'zonerate' => [
        'code' => 'zonerate',
        'title' => 'Delivery (J&T Express)',
        'description' => 'Delivery fee by destination and order subtotal',
        'active' => false,
        'zones' => "east: MY: 87000-91999, 93000-98999, Sabah, Sarawak, Labuan\nwest: MY",
        'rates' => "west, 0, 200, 15\nwest, 200.01, 300, 18\nwest, 300.01, *, 0\neast, 0, 200, 30\neast, 200.01, 300, 40\neast, 300.01, *, 0",
        'class' => 'Webkul\Shipping\Carriers\ZoneRate',
    ],

    'free' => [
        'code' => 'free',
        'title' => 'Free Shipping',
        'description' => 'Free Shipping',
        'active' => true,
        'default_rate' => '0',
        'class' => 'Webkul\Shipping\Carriers\Free',
    ],
];
