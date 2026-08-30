<?php

declare(strict_types=1);

return [
    // Маркер организатора для административного интерфейса.
    'admin_token' => env('ADMIN_API_TOKEN', ''),

    // Кампания по умолчанию для входящих сообщений Telegram и VK.
    'default_campaign_slug' => env('DEFAULT_CAMPAIGN_SLUG', 'demo'),

    // Выбор реализаций портов: 'redis' — рабочий режим, 'array' — без внешних сервисов.
    'drivers' => [
        'leaderboard' => env('ZATEYA_LEADERBOARD_DRIVER', 'redis'),
        'balance' => env('ZATEYA_BALANCE_DRIVER', 'redis'),
        'rate_limiter' => env('ZATEYA_RATE_LIMITER_DRIVER', 'redis'),
        'sessions' => env('ZATEYA_SESSIONS_DRIVER', 'redis'),
        'redis_connection' => env('ZATEYA_REDIS_CONNECTION', 'default'),
    ],

    'realtime' => [
        'enabled' => env('CENTRIFUGO_ENABLED', false),
        'api_url' => env('CENTRIFUGO_API_URL', 'http://centrifugo:8000/api'),
        'api_key' => env('CENTRIFUGO_API_KEY', ''),
    ],

    'messaging' => [
        'enabled' => env('RABBITMQ_ENABLED', false),
        'host' => env('RABBITMQ_HOST', 'rabbitmq'),
        'port' => (int) env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
        'exchange' => env('RABBITMQ_EXCHANGE', 'zateya.domain-events'),
    ],

    'telegram' => [
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET', ''),
    ],

    'vk' => [
        'confirmation_token' => env('VK_CONFIRMATION_TOKEN', ''),
        'secret_key' => env('VK_SECRET_KEY', ''),
        'app_secret' => env('VK_APP_SECRET', ''),
    ],
];
