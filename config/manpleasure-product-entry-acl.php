<?php

return [
    [
        'key' => 'catalog.product_entry',
        'name' => 'admin::product-entry.menu',
        'route' => [
            'admin.catalog.product_entry.index',
            'admin.catalog.product_entry.suggest_sku',
            'admin.catalog.product_entry.store',
            'admin.catalog.product_entry.update',
            'admin.catalog.product_entry.description_image',
            'admin.seller_workspace.edit_product',
            'admin.seller_workspace.add_product',
            'admin.seller_center.edit_product',
            'admin.seller_center.add_product',
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
        'key' => 'catalog.product_stock',
        'name' => 'admin::product-pricing.stock-modal-title',
        'route' => [
            'admin.catalog.product_stock.show',
            'admin.catalog.product_stock.update',
            'admin.catalog.product_stock.alert',
        ],
        'sort' => 8,
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
