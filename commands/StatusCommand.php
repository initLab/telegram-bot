<?php

/*
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/
namespace Longman\TelegramBot\Commands\UserCommands;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;

use Exception;

class StatusCommand extends UserCommand
{
    protected $name = 'status';
    protected $description = 'Show current info about init Lab';
    protected $usage = '/status';
    protected $version = '1.0.0';
    protected $enabled = true;

	const GUEST_NAME = 'Mystery Labber';

	const UNITS = [
		'temperature' => '°C',
		'humidity' => '%',
		'battery' => '%',
		'pressure' => 'hPa',
		'power' => 'W',
		'energy' => 'kWh',
	];

	private static function formatNum($num, $decimals = 0) {
		return number_format($num, $decimals, '.', '');
	}

	private static function getJson($url) {
		$ch = curl_init($url);

		if ($ch === false) {
			throw new Exception('Unable to create a download manager');
		}

		if (curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => 1,
			CURLOPT_TIMEOUT => 4,
		]) === false) {
			curl_close($ch);
			throw new Exception('Unable to set download options');
		}

		$data = curl_exec($ch);

		$errno = curl_errno($ch);

		if ($errno !== 0) {
			$error = curl_error($ch);
			curl_close($ch);
			throw new Exception($error . ' (code=' . $errno . ')');
		}

		if ($data === false) {
			curl_close($ch);
			throw new Exception('Unable to download data');
		}

		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ($code >= 400) {
			throw new Exception('Failed to download the data: HTTP code ' . $code);
		}

		$data = json_decode($data, true);

		if (is_null($data)) {
			throw new Exception('Failed to decode the response');
		}

		return $data;
	}
/*
	private static function formatDoorStatus($url) {
		try {
			$data = static::getJson($url);
		}
		catch (Exception $e) {
			return 'Door status not available: ' . $e->getMessage();
		}

		return 'Door: ' . htmlspecialchars($data['latch']) . ' and ' . htmlspecialchars($data['door']);
	}
*/
	private static function formatUsers($url) {
		try {
			$data = static::getJson($url);
		}
		catch (Exception $e) {
			return 'User list not available: ' . $e->getMessage();
		}

		$count = count($data);

		if ($count === 0) {
			return 'No one in the lab';
		}

		$result = 'People in the lab: ' . count($data) . PHP_EOL;
		$guests = 0;

		foreach ($data as $user) {
			if (is_null($user['id'])) {
				++$guests;
				continue;
			}

			$result .= htmlspecialchars($user['username']);

			if (!empty($user['twitter'])) {
				$result .= ' <a href="https://twitter.com/' . rawurlencode($user['twitter']) . '">[Twitter]</a>';
			}

			if (!empty($user['github'])) {
				$result .= ' <a href="https://github.com/' . rawurlencode($user['github']) . '">[GitHub]</a>';
			}

			if (!empty($user['url'])) {
				$result .= ' <a href="' . htmlspecialchars($user['url']) . '">[Web]</a>';
			}

			$result .= PHP_EOL;
		}

		if ($guests > 0) {
			$result .= 'Anonymous labber';

			if ($guests > 1) {
				$result .= ' x ' . $guests;
			}
		}

		return $result;
	}

	private static function formatMqttStatus(array $config) {
		try {
			$data = static::getJson($config['url']);
		}
		catch (Exception $e) {
			return 'MQTT status not available: ' . $e->getMessage();
		}

		$result = [];

		foreach ($config['sensors'] as $sensor => $sensorData) {
			$values = [];

			foreach (static::UNITS as $unit => $unitSymbol) {
				$key = $sensor . '/' . $unit;

				if (!array_key_exists($key, $data)) {
					continue;
				}

				$currValue = $data[$key];
				$delta = max(0, microtime(true) - $currValue['timestamp'] / 1000);

                $label = match ($unit) {
                    'temperature' => '🌡️',
                    'humidity' => '💧',
                    'battery' => '🔋',
                    default => ucfirst($unit),
                };

				$message = $label . ' ' . static::formatNum(floatval($currValue['value']), 1) . $unitSymbol;

				if ($delta > $config['timeout']) {
					$message .= ' (' . static::formatNum($delta) . 's ago)';
				}

				$values[] = $message;
			}

			if (count($values) === 0) {
				continue;
			}

			$result[] = $sensorData['name'] . ': ' . implode(' ', $values);
		}

		return implode(PHP_EOL, $result);
	}

	private static function isMetadataValid($key, array $dict) {
		if (!array_key_exists($key, $dict)) {
			return false;
		}

		if (in_array($dict[$key], [
			'<unknown>',
		])) {
			return false;
		}

		return true;
	}

	private static function formatMusic($url) {
		try {
			$data = static::getJson($url);
		}
		catch (Exception $e) {
			return 'Music status not available: ' . $e->getMessage();
		}

		if (array_key_exists('error', $data)) {
			return 'Music status error: ' . $data['error'];
		}

		$states = [
			'play' => 'playing',
			'pause' => 'paused',
			'stop' => 'stopped',
		];

		return 'Music: [' .
			$states[$data['status']['state']] . ']' .
			(array_key_exists('currentSong', $data) ? (
				' ' . (
					static::isMetadataValid('Artist', $data['currentSong']) ?
					(htmlspecialchars($data['currentSong']['Artist']) . ' - ') : ''
				) . htmlspecialchars($data['currentSong']['Title']) . (
					static::isMetadataValid('Album', $data['currentSong']) ?
					(' (' . htmlspecialchars($data['currentSong']['Album']) . (
						static::isMetadataValid('Date', $data['currentSong']) ?
						(', ' . htmlspecialchars($data['currentSong']['Date'])) : ''
					) . ')') : ''
				)
			) : '');
	}

	private static function getStatus(array $config) {
		return
//			static::formatDoorStatus($config['door_url']) . PHP_EOL . PHP_EOL .
			static::formatMqttStatus($config['mqtt']) . PHP_EOL . PHP_EOL .
			static::formatMusic($config['music_url']) . PHP_EOL . PHP_EOL .
			static::formatUsers($config['users_url']);
	}

    public function execute()
    {
        $update = $this->getUpdate();
        $message = $this->getMessage();

        $chat_id = $message->getChat()->getId();
		$message_id = $message->getMessageId();
		$reply_to_message_id = $chat_id < 0 ? $message_id : null;

		$config = $this->telegram->getCommandConfig('status');

		return Request::sendMessage([
			'chat_id' => $chat_id,
			'text' => static::getStatus($config),
			'parse_mode' => 'HTML',
			'disable_web_page_preview' => true,
			'reply_to_message_id' => $reply_to_message_id,
		]);
    }
}
