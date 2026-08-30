<?php

declare(strict_types=1);

// Точка входа Behat: подключает автозагрузчик Composer и переменные окружения тестов.
require __DIR__ . '/../../vendor/autoload.php';

$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'testing';
$_ENV['APP_DEBUG'] = $_SERVER['APP_DEBUG'] = 'false';
$_ENV['DB_CONNECTION'] = $_SERVER['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = ':memory:';
$_ENV['CACHE_STORE'] = $_SERVER['CACHE_STORE'] = 'array';
$_ENV['SESSION_DRIVER'] = $_SERVER['SESSION_DRIVER'] = 'array';
$_ENV['QUEUE_CONNECTION'] = $_SERVER['QUEUE_CONNECTION'] = 'sync';
$_ENV['CENTRIFUGO_ENABLED'] = $_SERVER['CENTRIFUGO_ENABLED'] = 'false';
$_ENV['RABBITMQ_ENABLED'] = $_SERVER['RABBITMQ_ENABLED'] = 'false';

foreach (['LEADERBOARD', 'BALANCE', 'RATE_LIMITER', 'SESSIONS'] as $driver) {
    $key = "ZATEYA_{$driver}_DRIVER";
    $_ENV[$key] = $_SERVER[$key] = 'array';
}
