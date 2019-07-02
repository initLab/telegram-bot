<?php
use Longman\TelegramBot\TelegramLog;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $logger = new Logger('debug');
    $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/debug.log'));

    $update_logger = new Logger('update');
    $update_logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/update.log'));

    TelegramLog::initialize($logger, $update_logger);
}
catch (Exception $e) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Unable to initialise logging: ' . $e->getMessage();
    exit;
}

require __DIR__ . '/settings.php';
