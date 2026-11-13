<?php

return [
    [
        'key' => 'sales.orders.workflow',
        'name' => 'admin::order-status.management',
        'route' => [
            'admin.sales.orders.workflow.update',
            'admin.sales.orders.awb.upload',
            'admin.sales.orders.awb.print',
            'admin.sales.orders.awb.image',
            'admin.sales.receipts.approve',
            'admin.sales.receipts.reject',
            'admin.sales.receipts.view',
        ],
        'sort' => 6,
    ],
];
