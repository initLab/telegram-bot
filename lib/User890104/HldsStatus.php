<?php
namespace User890104;

use Exception;

class HldsStatus {
	private static function formatServerType($type) {
		switch ($type) {
			case 'd':
				return 'dedicated';
			case 'l':
				return 'non-dedicated';
			case 'p':
				return 'SourceTV relay (proxy)';
		}
		
		return 'unknown';
	}
	
	private static function formatEnvironment($type) {
		switch ($type) {
			case 'l':
				return 'Linux';
			case 'w':
				return 'Windows';
			case 'm':
			case 'o':
				return 'Mac OS';
		}
		
		return 'unknown';
	}
	
	private static function formatBoolean($val) {
		return $val ? 'Yes' : 'No';
	}
	
	private static function getJson($url) {
		$data = file_get_contents($url);
		
		if ($data === false) {
			throw new Exception('Error getting data');
		}
		
		$data = json_decode($data, true);
		
		if ($data === false) {
			throw new Exception('Error decoding data');
		}
		
		if (array_key_exists('error', $data)) {
			throw new Exception('Error: ' . $data['error']);
		}
		
		return $data['result'];
	}
	
	public static function getStatus($host, $port = 27015) {
        try {
    	    $client = new QueryClient($host, $port, 1, 1);

			$info = $client->info();
			$players = $client->players();
		}
		catch (Exception $e) {
			return '[' . $host . ':' . $port . '] ' . $e->getMessage();
		}
		
		return
			'<strong>Host:</strong> ' . $host . ':' . $port . PHP_EOL .
			'<strong>Game:</strong> ' . $info['Game'] . PHP_EOL .
			'<strong>Server Name:</strong> ' . $info['Name'] . PHP_EOL .
			'<strong>Map:</strong> ' . $info['Map'] . PHP_EOL .
			'<strong>Players:</strong> ' . $info['Players'] . ' / ' . $info['MaxPlayers'] .
				($info['Bots'] > 0 ? (' (' . $info['Bots'] . ' bots)') : '') . PHP_EOL .
			'<strong>Server type:</strong> ' . static::formatServerType($info['ServerType']) . PHP_EOL .
			'<strong>Server OS:</strong> ' . static::formatEnvironment($info['Environment']) . PHP_EOL .
			'<strong>Public server:</strong> ' . static::formatBoolean($info['Visibility']) . PHP_EOL .
			'<strong>VAC active:</strong> ' . static::formatBoolean($info['VAC']) . PHP_EOL .
			'<strong>Version:</strong> ' . $info['Version'] . PHP_EOL .
			($info['Players'] > 0 ? (PHP_EOL . '<strong>Online players:</strong>' . PHP_EOL . implode(PHP_EOL, array_map(function($player) {
				return '<strong>' . $player['Name'] . '</strong> (' . $player['Score'] . ' frags)';
			}, $players))) : '');
	}

	public static function getStatusMultiple(array $servers)
    {
        return implode(PHP_EOL, array_map(function($server) {
            return static::getStatus($server['host'], $server['port'] ?? 27015);
        }, $servers));
    }
}
