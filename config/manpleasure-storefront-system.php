<?php

return [
    [
        'key' => 'general.content.manpleasure',
        'name' => 'manpleasure.navigation-settings',
        'info' => 'manpleasure.navigation-settings-info',
        'sort' => 10,
        'fields' => [
            [
                'name' => 'navigation_enabled',
                'title' => 'manpleasure.navigation-enabled',
                'type' => 'boolean',
                'default' => true,
                'channel_based' => true,
                'locale_based' => false,
            ],
        ],
    ],
];
