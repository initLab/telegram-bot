<?php
namespace User890104;

use Exception;

class HldsStatus {
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
            '<code>' . $hostAndPort . '</code> | <strong>' . $info['Game'] . '</strong> (' . $info['Name'] .
            ') at <strong>' . $info['Map'] . '</strong> with ' . $info['Players'] . '/' . $info['MaxPlayers'] .
            ' players' . ($info['Bots'] > 0 ? (' (' . $info['Bots'] . ' bots)') : '') .
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
