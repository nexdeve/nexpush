<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FCM Server Key (Legacy) or Service Account (V1)
    |--------------------------------------------------------------------------
    | Get from: Firebase Console → Project Settings → Cloud Messaging
    */
    'fcm_server_key' => env('NEXPUSH_FCM_SERVER_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID (for FCM V1 API)
    |--------------------------------------------------------------------------
    */
    'fcm_project_id' => env('NEXPUSH_FCM_PROJECT_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Use Legacy API (true) or V1 API (false)
    |--------------------------------------------------------------------------
    | Legacy is simpler but Google may deprecate it.
    | V1 requires a service account JSON file.
    */
    'use_legacy_api' => env('NEXPUSH_USE_LEGACY', true),

    /*
    |--------------------------------------------------------------------------
    | NexPush API Key
    |--------------------------------------------------------------------------
    | Used to authenticate requests from your Flutter app to your backend.
    | Set same value in Flutter NexPushConfig.serverKey
    */
    'api_key' => env('NEXPUSH_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Default Notification Channel (Android)
    |--------------------------------------------------------------------------
    */
    'default_channel_id'   => env('NEXPUSH_CHANNEL_ID', 'nexpush_channel'),
    'default_channel_name' => env('NEXPUSH_CHANNEL_NAME', 'App Notifications'),

    /*
    |--------------------------------------------------------------------------
    | Log Channel
    |--------------------------------------------------------------------------
    */
    'log_channel' => env('NEXPUSH_LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    */
    'route_prefix' => 'api/nexpush',
];
