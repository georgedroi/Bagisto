<?php

return [
    /**
     * The fixed rate shown beside a price so the owner sees the approximate US dollar amount.
     */
    'usd_rate' => 4.5,

    /**
     * Each main category's attribute family and the prefix of the SKUs suggested for it.
     */
    'categories' => [
        'strokers-masturbators' => ['family' => 'manpleasure_strokers', 'sku_prefix' => 'STR'],
        'rings-delay' => ['family' => 'manpleasure_rings', 'sku_prefix' => 'RNG'],
        'pumps-trainers' => ['family' => 'manpleasure_pumps', 'sku_prefix' => 'PMP'],
        'prostate-anal' => ['family' => 'manpleasure_prostate', 'sku_prefix' => 'PRO'],
        'condoms' => ['family' => 'manpleasure_condoms', 'sku_prefix' => 'CON'],
        'lubricants-care' => ['family' => 'manpleasure_lubricants', 'sku_prefix' => 'LUB'],
        'accessories-storage' => ['family' => 'manpleasure_accessories', 'sku_prefix' => 'ACC'],
        'underwear-costumes' => ['family' => 'manpleasure_apparel', 'sku_prefix' => 'APP'],
    ],

    /**
     * The product type offered first for each sub-category; the admin can still change it.
     */
    'types' => [
        'manual-strokers' => 'Device',
        'automatic-strokers' => 'Device',
        'sleeves-compact' => 'Device',
        'cock-rings' => 'Device',
        'extenders' => 'Device',
        'delay-sprays' => 'Personal Care',
        'manual-pumps' => 'Device',
        'electric-pumps' => 'Device',
        'trainers-accessories' => 'Device',
        'prostate-massagers' => 'Device',
        'anal-toys' => 'Device',
        'regular-condoms' => 'Condom',
        'thin-condoms' => 'Condom',
        'lasting-condoms' => 'Condom',
        'water-based-lubricants' => 'Lubricant',
        'silicone-based-lubricants' => 'Lubricant',
        'cleaners-care' => 'Cleaner',
        'storage-travel' => 'Accessory',
        'replacement-parts' => 'Accessory',
        'chargers-cables' => 'Accessory',
        'mens-underwear' => 'Underwear',
        'womens-lingerie' => 'Lingerie',
        'costumes-cosplay' => 'Costume',
    ],

    /**
     * Fields the page draws itself, so they are not repeated in a family's detail fields.
     */
    'fixed_attributes' => [
        'sku', 'name', 'url_key', 'status', 'visible_individually', 'guest_checkout', 'new', 'featured',
        'short_description', 'description', 'weight', 'price', 'special_price', 'special_price_from',
        'special_price_to', 'cost', 'manage_stock', 'mp_product_type',
    ],

    /**
     * Text fields that hold a number, so the page and the server both check them.
     */
    'numeric_attributes' => [
        'mp_length_cm', 'mp_diameter_cm', 'mp_insertable_length_cm', 'mp_volume_ml', 'mp_width_mm', 'mp_pack_size',
    ],
];
