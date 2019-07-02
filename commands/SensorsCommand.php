<?php

/*
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
*/
namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Request;

use Carbon\Carbon;
use User890104\MultiCurl;

class SensorsCommand extends SystemCommand
{
    protected $name = 'sensors';
    protected $description = 'Shows sensor readings';
    protected $usage = '/sensors';
    protected $version = '1.0.0';
    protected $enabled = true;
	
	private static $units = [
		'Temperature' => '°С',
		'Humidity' => '%',
		'Moisture' => '%',
		'Pressure' => ' hPa',
	];
	
	private static function getApiUrl($channel_id) {
		return sprintf('https://api.thingspeak.com/channels/%u/feeds.json?days=1&results=1', $channel_id);
	}
	
	private static function formatNumber($number, $unit = null) {
		return number_format($number, 1, '.', ' ') . (static::$units[$unit] ?? '');
	}
	
	private static function request(array $urls) {
        $mc = new MultiCurl;

        foreach ($urls as $url) {
            $mc->addRequest([
                CURLOPT_CONNECTTIMEOUT => 4,
                CURLOPT_TIMEOUT => 6,
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
            ]);
        }

        while ($mc->run()); // loop until all requests are complete

        return $mc->getResponses();
	}
	
	private static function getReadings() {
		$data = array_map(function($data) {
			$lastName = null;
			
			$sensors = implode('', array_filter(array_map(function($field) use ($data, &$lastName) {
				$key = 'field' . $field;
				
				if (!array_key_exists($key, $data['channel']) || is_null($data['feeds'][0][$key])) {
					return null;
				}
				
				$label = $data['channel'][$key];
				
				if (in_array($label, ['PM25stdev', 'PM10stdev', 'PM25sterr', 'PM10sterr'])) {
					return null;
				}
				
				$parts = explode(':', $label);
				$name = $parts[1] ?? $label;
				$displayName = $name === $lastName ? '' : ($name . ': ');
				$lastName = $name;
				
				return ($field > 1 ? ' / ' : '') . $displayName .
					static::formatNumber($data['feeds'][0][$key], $parts[0]);
			}, range(1, 8))));
			
			if (strlen($sensors) === 0) {
				return '';
			}
			
			return ($data['channel']['description'] ?? $data['channel']['name']) .
				' ' . $sensors . ' (' .
				Carbon::parse($data['feeds'][0]['created_at'])->diffForHumans(null, false, true) .
				')' . EOL;
		}, array_filter(array_map(function($response) {
			return json_decode($response['result'], true);
		}, array_filter(static::request([
			static::getApiUrl(132452), // init-Lab-Si7021-Outside
			static::getApiUrl(222701), // init-Lab-Si7021-Lecture
			static::getApiUrl(222702), // init-Lab-Si7021-Ruby
		]), function($response) {
			return $response['code'] === 200;
		})), function($data) {
			return count($data['feeds']);
		}));
		sort($data, SORT_STRING | SORT_FLAG_CASE);
		return implode('', $data);
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
			'text' => '`' . static::getReadings() . '`',
			'parse_mode' => 'Markdown',
			'reply_to_message_id' => $reply_to_message_id,
		]);
    }
}
