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

class HlCommand extends UserCommand
{
    protected $name = 'hl';
    protected $description = 'Show Half-Life server status';
    protected $usage = '/hl';
    protected $version = '1.0.0';
    protected $enabled = true;
	
    public function execute()
    {
        $update = $this->getUpdate();
        $message = $this->getMessage();

        $chat_id = $message->getChat()->getId();
		$message_id = $message->getMessageId();
		$reply_to_message_id = $chat_id < 0 ? $message_id : null;
		
		return Request::sendMessage([
			'chat_id' => $chat_id,
			'text' => HldsStatus::getStatus('hl.6bez10.info'),
			'reply_to_message_id' => $reply_to_message_id,
			'parse_mode' => 'HTML',
		]);
    }
}
