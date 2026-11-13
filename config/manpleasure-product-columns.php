<?php

/*
|--------------------------------------------------------------------------
| Product list columns
|--------------------------------------------------------------------------
|
| The columns an admin can show or hide on Catalog > Products, in the order
| they appear. The product name, SKU and picture, and the row actions, are
| always shown. "width" is the CSS grid track, "default" whether the column
| is shown until the admin chooses otherwise, and "sort" the datagrid column
| a header click sorts by (null when the value cannot be sorted).
|
*/

return [
    'category' => ['label' => 'admin::executive-layout.category-type', 'width' => '1.1fr', 'default' => true, 'sort' => null],
    'inventory' => ['label' => 'admin::executive-layout.inventory', 'width' => '.8fr', 'default' => true, 'sort' => 'quantity'],
    'price' => ['label' => 'admin::executive-layout.price', 'width' => '.9fr', 'default' => true, 'sort' => 'price'],
    'special_price' => ['label' => 'admin::product-columns.col-special-price', 'width' => '1fr', 'default' => false, 'sort' => 'special_price'],
    'cost' => ['label' => 'admin::product-columns.col-cost', 'width' => '.8fr', 'default' => false, 'sort' => 'cost'],
    'margin' => ['label' => 'admin::product-columns.col-margin', 'width' => '.7fr', 'default' => false, 'sort' => null],
    'status' => ['label' => 'admin::executive-layout.status', 'width' => '.7fr', 'default' => true, 'sort' => 'status'],
    'family' => ['label' => 'admin::product-columns.col-family', 'width' => '1.1fr', 'default' => false, 'sort' => null],
    'flags' => ['label' => 'admin::product-columns.col-flags', 'width' => '.9fr', 'default' => false, 'sort' => null],
    'weight' => ['label' => 'admin::product-columns.col-weight', 'width' => '.7fr', 'default' => false, 'sort' => 'weight'],
    'images' => ['label' => 'admin::product-columns.col-images', 'width' => '.7fr', 'default' => false, 'sort' => null],
    'product_id' => ['label' => 'admin::product-columns.col-id', 'width' => '.5fr', 'default' => false, 'sort' => 'product_id'],
    'description' => ['label' => 'admin::product-columns.col-description', 'width' => '2fr', 'default' => false, 'sort' => null],
    'created' => ['label' => 'admin::product-columns.col-created', 'width' => '1fr', 'default' => false, 'sort' => 'created_at'],
    'updated' => ['label' => 'admin::product-columns.col-updated', 'width' => '1fr', 'default' => false, 'sort' => 'updated_at'],
];
