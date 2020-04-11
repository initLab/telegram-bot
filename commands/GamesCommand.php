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

use User890104\HldsStatus;

class GamesCommand extends UserCommand
{
    protected $name = 'games';
    protected $description = 'Show game servers status';
    protected $usage = '/games';
    protected $version = '1.0.0';
    protected $enabled = true;
	
    public function execute()
    {
        $update = $this->getUpdate();
        $message = $this->getMessage();

        $chat_id = $message->getChat()->getId();
		$message_id = $message->getMessageId();
		$reply_to_message_id = $chat_id < 0 ? $message_id : null;

        $config = $this->telegram->getCommandConfig('games');

        return Request::sendMessage([
			'chat_id' => $chat_id,
			'text' => HldsStatus::getStatusMultiple($config['servers']),
			'reply_to_message_id' => $reply_to_message_id,
			'parse_mode' => 'HTML',
		]);
    }
}
