<?php

return [
    'repository' => env('MANPLEASURE_UPDATE_REPOSITORY', 'georgedroi/Bagisto'),
    'token' => env('MANPLEASURE_UPDATE_TOKEN'),
    'owner_ids' => array_values(array_filter(array_map('intval', explode(',', env('MANPLEASURE_UPDATE_OWNER_IDS', '1'))))),
    'deploy_enabled' => (bool) env('MANPLEASURE_UPDATE_DEPLOY_ENABLED', false),
    'request_directory' => env('MANPLEASURE_UPDATE_REQUEST_DIRECTORY', '/var/spool/manpleasure-updater/requests'),
    'status_directory' => env('MANPLEASURE_UPDATE_STATUS_DIRECTORY', '/var/lib/manpleasure-updater/public'),
];
