<?php

use Webkul\Stripe\Payment\Stripe;

return [
    'stripe' => [
        'class' => Stripe::class,
        'code' => 'stripe',
        'title' => 'Stripe',
        'description' => 'Stripe Payment Gateway',
        'active' => false,
        'generate_invoice' => false,
        'sort' => 5,
    ],
];
