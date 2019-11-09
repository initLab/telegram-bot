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
			CURLOPT_TIMEOUT => 2,
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
	
	private static function formatDoorStatus($url) {
		try {
			$data = static::getJson($url);
		}
		catch (Exception $e) {
			return 'Door status not available: ' . $e->getMessage();
		}

		return 'Door: ' . htmlspecialchars($data['latch']) . ' and ' . htmlspecialchars($data['door']);
	}
	
	private static function formatLightsStatus($url) {
		try {
			$data = static::getJson($url);
		}
		catch (Exception $e) {
			return 'Lights status not available: ' . $e->getMessage();
		}
		
		return 'Lights: ' . htmlspecialchars($data['status']) . ', policy: ' . htmlspecialchars($data['policy']);
	}
	
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
			
			$result .= htmlspecialchars($user['name']) . ' (' . htmlspecialchars($user['username']) . ')';
			
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
	
	private static function formatWeather($url) {
		try {
			$data = static::getJson($url);
		}
		catch (Exception $e) {
			return 'Weather not available: ' . $e->getMessage();
		}
		
		if ($data['status'] !== 0) {
			return 'Weather status error (code ' . $data['status'] . ')';
		}
		
		return
			'Temperature: ' . static::formatNum($data['temp_in'], 1) . '°С in / ' . static::formatNum($data['temp_out'], 1) . '°С out' . PHP_EOL .
			'Humidity: ' . static::formatNum($data['hum_in']) . '% in / ' . static::formatNum($data['hum_out']) . '% out' . PHP_EOL .
			'Atmospheric pressure: ' . static::formatNum($data['pressure'], 1) . ' hPa';
	}
	
	private static function formatMqttWeather($url) {
		try {
			$data = static::getJson($url);
		}
		catch (Exception $e) {
			return 'Weather not available: ' . $e->getMessage();
		}
		
		if (count($data) < 6) {
			return 'Weather status error: no data';
		}
		
		return
			'Outside: Temperature: ' . static::formatNum($data['sensor-outside-espurna/temperature']['value'], 1) . '°С / Humidity: ' . static::formatNum($data['sensor-outside-espurna/humidity']['value'], 1) . '%' . PHP_EOL .
			'Lecture room: Temperature: ' . static::formatNum($data['sensor-lecture-room-espurna/temperature']['value'], 1) . '°С / Humidity: ' . static::formatNum($data['sensor-lecture-room-espurna/humidity']['value'], 1) . '%' . PHP_EOL .
			'Ruby room: Temperature: ' . static::formatNum($data['sensor-ruby-room-espurna/temperature']['value'], 1) . '°С / Humidity: ' . static::formatNum($data['sensor-ruby-room-espurna/humidity']['value'], 1) . '%' . PHP_EOL;
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
	
	private static function formatAc($url) {
		try {
			$data = static::getJson($url);
		}
		catch (Exception $e) {
			return 'AC status not available: ' . $e->getMessage();
		}
		
		if (array_key_exists('error', $data)) {
			return 'AC status error: ' . $data['error'];
		}
		
		$modes = [
			'Cooling',
			'Drying',
			'Auto',
			'Heating',
		];
		
		$fanSpeeds = [
			3 => 'High',
			9 => 'Low',
			11 => 'Auto',
		];
		
		return 'Air conditioner: ' . ($data['on'] ? (
			'ON, Mode: ' .
			(
				array_key_exists($data['mode'], $modes) ?
					$modes[$data['mode']] :
					'Unknown'
			) .
			', Temp: ' . $data['temp'] . '°С, Fan: ' .
			(
				array_key_exists($data['fan'], $fanSpeeds) ?
					$fanSpeeds[$data['fan']] :
					'Unknown'
			)
		) : 'OFF');
	}
	
	private static function getStatus() {
		return
			static::formatDoorStatus('https://fauna.initlab.org/api/door/status.json') . PHP_EOL .
			static::formatLightsStatus('https://fauna.initlab.org/api/lights/status.json') . PHP_EOL .
			//static::formatWeather('https://spitfire.initlab.org/weather.json') . PHP_EOL .
			static::formatMqttWeather('http://185.117.82.20:9999/status') . PHP_EOL .
			static::formatMusic('http://spitfire.initlab.org:8989/status') . PHP_EOL .
			static::formatUsers('https://fauna.initlab.org/api/users/present.json');
	}
	
    public function execute()
    {
        $update = $this->getUpdate();
        $message = $this->getMessage();

        $chat_id = $message->getChat()->getId();
		$message_id = $message->getMessageId();
		$reply_to_message_id = $chat_id < 0 ? $message_id : null;
		
		return Request::sendMessage([
			'chat_id' => $chat_id,
			'text' => static::getStatus(),
			'parse_mode' => 'HTML',
			'disable_web_page_preview' => true,
			'reply_to_message_id' => $reply_to_message_id,
		]);
    }
}
