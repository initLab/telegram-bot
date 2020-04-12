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
	
	private static function formatVisibility($val) {
		return $val ? 'private' : 'public';
	}
	
	private static function formatVac($val) {
		return ($val ? 'with' : 'without') . ' VAC';
	}

	public static function getStatus($host, $port = 27015) {
	    $hostAndPort = $host . ':' . $port;

        try {
    	    $client = new QueryClient($host, $port, 1, 1);

			$info = $client->info();
			$players = $client->players();
		}
		catch (Exception $e) {
			return '[' . $hostAndPort . '] ' . $e->getMessage();
		}

		return
            '[' . $hostAndPort . '] <strong>' . $info['Game'] . '</strong> (' . $info['Name'] .
            ') at map ' . $info['Map'] . ' with ' . $info['Players'] . ' of ' . $info['MaxPlayers'] .
            ' players (' . $info['Bots'] . ' bots) (' . static::formatVisibility($info['Visibility']) .
            ' ' . static::formatServerType($info['ServerType']) . ' server v.' . $info['Version'] .
            ' on ' . static::formatEnvironment($info['Environment']) . ' ' .
            static::formatVac($info['VAC']) . ')' .
            (
                $info['Players'] > 0 ? (
                    PHP_EOL . implode(PHP_EOL, array_map(function($player) {
                        return '#' . $player['Index'] . ' ' . $player['Name'] . ' (' .
                            $player['Score'] . ' frags)';
                    }, $players))
                ) : ''
            );
	}

	public static function getStatusMultiple(array $servers)
    {
        return implode(PHP_EOL . PHP_EOL, array_map(function($server) {
            return static::getStatus($server['host'], $server['port'] ?? 27015);
        }, $servers));
    }
}
