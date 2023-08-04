<?php
/**
 * README
 * This configuration file is intended to run the bot with the webhook method.
 * Uncommented parameters must be filled
 *
 * Please note that if you open this file with your browser you'll get the "Input is empty!" Exception.
 * This is a normal behaviour because this address has to be reached only by the Telegram servers.
 */

require __DIR__ . '/../config/init.php';

if (!isset($_GET['key']) || $_GET['key'] !== TELEGRAM_API_KEY) {
	http_response_code(403);
	exit;
}

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram(TELEGRAM_API_KEY, TELEGRAM_BOT_NAME);

    // Add commands paths containing your custom commands
    $telegram->addCommandsPaths([
		__DIR__ . '/../commands/',
	]);

    // Enable admin users
    $telegram->enableAdmins(ADMINISTRATOR_IDS);

    // Enable MySQL
    $telegram->enableMySql([
	   'host'     => DB_HOST,
	   'user'     => DB_USER,
	   'password' => DB_PASS,
	   'database' => DB_NAME,
	]);

    // Set custom Upload and Download paths
    $telegram->setDownloadPath(__DIR__ . '/../storage/download');
    $telegram->setUploadPath(__DIR__ . '/../storage/upload');

    // Here you can set some command specific parameters
    // e.g. Google geocode/timezone api key for /date command
    $telegram->setCommandConfig('fauna', [
		'oauth_client_id' => FAUNA_OAUTH_CLIENT_ID,
		'oauth_client_secret' => FAUNA_OAUTH_CLIENT_SECRET,
	]);

    $telegram->setCommandConfig('games', [
		'servers' => GAMES_SERVERS,
	]);

    $telegram->setCommandConfig('status', [
		'door_url' => STATUS_DOOR_URL,
		'mqtt' => STATUS_MQTT,
		'music_url' => STATUS_MUSIC_URL,
		'users_url' => STATUS_USERS_URL,
	]);

    // Requests Limiter (tries to prevent reaching Telegram API limits)
    $telegram->enableLimiter();

    // Handle telegram webhook request
    $telegram->handle();

} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // Silence is golden!
    //echo $e;
    // Log telegram errors
    Longman\TelegramBot\TelegramLog::error($e);
} catch (Longman\TelegramBot\Exception\TelegramLogException $e) {
    // Silence is golden!
    // Uncomment this to catch log initialisation errors
    //echo $e;
}
