<?php
/**
 * README
 * This file is intended to unset the webhook.
 * Uncommented parameters must be filled
 */

if (PHP_SAPI !== 'cli') {
	exit;
}

require __DIR__ . '/../config/init.php';

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram(TELEGRAM_API_KEY, TELEGRAM_BOT_NAME);

    // Delete webhook
    $result = $telegram->deleteWebhook();

    if ($result->isOk()) {
        echo $result->getDescription();
    }
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    echo $e->getMessage();
}
