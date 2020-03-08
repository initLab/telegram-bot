<?php
define('BOT_BASE_URL', 'https://example.com/telegram/bot');

define('TELEGRAM_API_KEY', '1234567890:ABCDEFGHIJKLMNOPQRSTUVWXYZ');
define('TELEGRAM_BOT_NAME', 'telegramBot');

define('ADMINISTRATOR_IDS', [
    12345678,
]);

define('DB_HOST', 'localhost');
define('DB_USER', 'telegram_bot');
define('DB_PASS', 'example');
define('DB_NAME', 'telegram_bot');

define('FAUNA_OAUTH_CLIENT_ID', 'example');
define('FAUNA_OAUTH_CLIENT_SECRET', 'example');

define('STATUS_DOOR_URL', 'https://example.com/door/status.json');
define('STATUS_LIGHTS_URL', 'https://example.com/lights/status.json');
define('STATUS_MQTT', [
	'url' => 'http://example.com/status',
	'timeout' => 20,
	'sensors' => [
		'sensor-1' => [
			'name' => 'Example 1',
		],
		'sensor-2' => [
			'name' => 'Example 2',
		],
	],
]);
define('STATUS_MUSIC_URL', 'http://example.com/status');
define('STATUS_USERS_URL', 'https://example.com/users/present.json');
