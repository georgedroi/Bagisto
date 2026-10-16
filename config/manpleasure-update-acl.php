<?php

return [
    [
        'key' => 'settings.system_updates',
        'name' => 'admin::system-updates.title',
        'route' => [
            'admin.settings.system_updates.index',
            'admin.settings.system_updates.check',
            'admin.settings.system_updates.install',
            'admin.settings.system_updates.rollback',
            'admin.settings.system_updates.status',
        ],
        'sort' => 20,
    ],
];
