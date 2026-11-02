<?php

return [
    [
        'key' => 'sales.workspace',
        'name' => 'admin::seller-workspace.title',
        'route' => [
            'admin.seller_workspace.overview',
            'admin.seller_workspace.orders',
            'admin.seller_workspace.shipping',
            'admin.seller_workspace.fees',
            'admin.seller_center.index',
        ],
        'sort' => 8,
    ], [
        'key' => 'sales.rma.workspace',
        'name' => 'admin::seller-workspace.pages.returns',
        'route' => 'admin.seller_workspace.returns',
        'sort' => 8,
    ], [
        'key' => 'sales.invoices.workspace',
        'name' => 'admin::seller-workspace.pages.income',
        'route' => 'admin.seller_workspace.income',
        'sort' => 8,
    ], [
        'key' => 'customers.reviews.workspace',
        'name' => 'admin::seller-workspace.pages.reviews',
        'route' => 'admin.seller_workspace.reviews',
        'sort' => 8,
    ], [
        'key' => 'catalog.products.workspace',
        'name' => 'admin::seller-workspace.pages.products',
        'route' => [
            'admin.seller_workspace.products',
            'admin.seller_workspace.product_lifecycle',
            'admin.seller_center.products',
        ],
        'sort' => 8,
    ], [
        'key' => 'marketing.promotions.workspace',
        'name' => 'admin::seller-workspace.pages.promotions',
        'route' => 'admin.seller_workspace.promotions',
        'sort' => 8,
    ], [
        'key' => 'settings.workspace',
        'name' => 'admin::seller-workspace.pages.settings',
        'route' => 'admin.seller_workspace.settings',
        'sort' => 8,
    ],
];
