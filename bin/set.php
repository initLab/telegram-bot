<?php
/**
 * README
 * This file is intended to set the webhook.
 * Uncommented parameters must be filled
 */

if (PHP_SAPI !== 'cli') {
	exit;
}

require __DIR__ . '/../config/init.php';

// Define the URL to your hook.php file
$hook_url = BOT_BASE_URL . '/hook.php?' . http_build_query([
	'key' => TELEGRAM_API_KEY,
]);

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram(TELEGRAM_API_KEY, TELEGRAM_BOT_NAME);
    // Set webhook
    $result = $telegram->setWebhook($hook_url);
    if ($result->isOk()) {
        echo $result->getDescription();
    }
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    echo $e->getMessage();
}
