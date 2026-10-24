<?php

return [
    [
        'key' => 'sales.carriers.zonerate',
        'name' => 'shipping::app.zonerate.title',
        'info' => 'shipping::app.zonerate.info',
        'sort' => 3,
        'fields' => [
            [
                'name' => 'active',
                'title' => 'shipping::app.zonerate.status',
                'type' => 'boolean',
                'channel_based' => true,
                'locale_based' => false,
            ],
            [
                'name' => 'title',
                'title' => 'shipping::app.zonerate.method-title',
                'type' => 'text',
                'depends' => 'active:1',
                'validation' => 'required_if:active,1',
                'channel_based' => true,
                'locale_based' => true,
            ],
            [
                'name' => 'description',
                'title' => 'shipping::app.zonerate.description',
                'type' => 'textarea',
                'depends' => 'active:1',
                'channel_based' => true,
                'locale_based' => true,
            ],
            [
                'name' => 'zones',
                'title' => 'shipping::app.zonerate.zones',
                'info' => 'shipping::app.zonerate.zones-info',
                'type' => 'textarea',
                'depends' => 'active:1',
                'validation' => 'required_if:active,1',
                'channel_based' => true,
                'locale_based' => false,
            ],
            [
                'name' => 'rates',
                'title' => 'shipping::app.zonerate.rates',
                'info' => 'shipping::app.zonerate.rates-info',
                'type' => 'textarea',
                'depends' => 'active:1',
                'validation' => 'required_if:active,1',
                'channel_based' => true,
                'locale_based' => false,
            ],
        ],
    ],
];
