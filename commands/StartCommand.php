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
use Longman\TelegramBot\Commands\UserCommands\FaunaCommand;

class StartCommand extends SystemCommand
{
    protected $name = 'start';
    protected $description = 'Sends a greeting message';
    protected $usage = '/start';
    protected $version = '1.0.0';
    protected $enabled = true;
    protected $public = true;
	
    public function execute()
    {
        $update = $this->getUpdate();
        $message = $this->getMessage();
		$text = $message->getText(true);
		$sender = $message->getFrom();
		
		if (
			strlen($text) === 64 &&
			trim($text, implode('', array_merge(
				range('A', 'Z'),
				range('a', 'z'),
				range('0', '9'),
				['-', '_']
			))) === ''
		) {
			// looks like url-base64
			$text = base64_decode(str_replace(['-', '_'], ['+', '/'], $text));
			$parts = explode(':', $text, 2);
			
			if (count($parts) === 1) {
				return Request::sendMessage([
					'chat_id' => $message->getChat()->getId(),
					'text' => 'Invalid start message',
					'reply_to_message_id' => $message->getMessageId(),
				]);
			}
			
			$cmd = $parts[0];
			$data = $parts[1];
			
			$config = $this->telegram->getCommandConfig($cmd);
			
			switch ($cmd) {
				case 'fauna':
					return FaunaCommand::processStart($sender, $message, $data, $config);
				default:
					return Request::sendMessage([
						'chat_id' => $message->getChat()->getId(),
						'text' => 'Service "' . $parts[0] . '" not supported',
						'reply_to_message_id' => $message->getMessageId(),
					]);
			}
		}
		
        return Request::sendMessage([
			'chat_id' => $message->getChat()->getId(),
			'text' => 'Hello, I\'m a bot that can accomplish various tasks. To see a list of commands, send /help',
			'reply_to_message_id' => $message->getMessageId(),
		]);
    }
}
