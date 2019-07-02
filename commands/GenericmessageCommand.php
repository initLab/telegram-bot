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

use Longman\TelegramBot\Entities\ReplyKeyboardMarkup;
use Longman\TelegramBot\Entities\ReplyKeyboardHide;

class GenericmessageCommand extends SystemCommand
{
    protected $name = 'text';
    protected $description = 'Show text';
    protected $usage = '/text <text>';
    protected $version = '1.0.0';
    protected $enabled = true;
	
    public function execute()
    {
        $update = $this->getUpdate();
        $message = $this->getMessage();
		
		$chat_id = $message->getChat()->getId();
		$from_id = $message->getFrom()->getId();
		$message_id = $message->getMessageId();
		$reply_to_message_id = $chat_id < 0 ? $message_id : null;

		$type = $message->getType();

		switch ($type) {
			case 'Message':
				switch (strtolower($text)) {
					default:
						$text = 'Hi there!';
					break;
				}

				if (strlen($text) === 0) {
					return;
				}

				return Request::sendMessage([
					'chat_id' => $chat_id,
					'text' => $text,
					'reply_to_message_id' => $reply_to_message_id,
				]);
			break;
			default:
				return Request::emptyResponse();
			break;
		}
    }
}
