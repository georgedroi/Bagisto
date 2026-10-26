<?php

return [
    [
        'key' => 'catalog.product_entry',
        'name' => 'admin::product-entry.menu',
        'route' => [
            'admin.catalog.product_entry.index',
            'admin.catalog.product_entry.suggest_sku',
            'admin.catalog.product_entry.store',
        ],
        'sort' => 5,
    ], [
        'key' => 'catalog.product_pricing',
        'name' => 'admin::product-pricing.modal-title',
        'route' => [
            'admin.catalog.product_pricing.show',
            'admin.catalog.product_pricing.update',
        ],
        'sort' => 7,
    ], [
        'key' => 'catalog.media_bank',
        'name' => 'admin::product-editor.media-bank',
        'route' => [
            'admin.media.index',
            'admin.media.assets.store',
            'admin.media.assets.delete',
        ],
        'sort' => 6,
    ],
];
