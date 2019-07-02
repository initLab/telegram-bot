<?php
require __DIR__ . '/../lib/functions.php';

if (
	!array_key_exists('code', $_GET) ||
	!array_key_exists('state', $_GET) ||
	strlen($_GET['state']) !== 10
) {
	echo 'Missing parameter';
	exit;
}

$code = urlBase64Decode($_GET['code']);

if (strlen($code) !== 32) {
	echo 'Incorrect code length';
	exit;
}

require __DIR__ . '/../config/settings.php';

header('Location: https://www.telegram.me/' . TELEGRAM_BOT_NAME . '?' . http_build_query([
	'start' => urlBase64Encode('fauna:' . $code . $_GET['state']),
]));
