<?php
/**
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
use Longman\TelegramBot\Entities\InlineKeyboardMarkup;
use Longman\TelegramBot\Entities\InlineKeyboardButton;

use Longman\TelegramBot\Exception\TelegramException;

use Longman\TelegramBot\Commands\UserCommands\FaunaCommand;

use Exception;

/**
 * Callback query command
 */
class CallbackqueryCommand extends SystemCommand
{
    /**#@+
     * {@inheritdoc}
     */
    protected $name = 'callbackquery';
    protected $description = 'Reply to callback query';
    protected $version = '1.0.0';
    /**#@-*/
	
	private static function answer($callback_query_id, array $data)
	{
		return Request::answerCallbackQuery(array_merge(compact('callback_query_id'), $data));
	}

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $update = $this->getUpdate();
        $callback_query = $update->getCallbackQuery();
        $callback_query_id = $callback_query->getId();
		$sender = $callback_query->getFrom();
		$message = $callback_query->getMessage();
		
		parse_str($callback_query->getData(), $data);
		
		$answerData = [];
		
		if (is_array($data) && array_key_exists('cmd', $data)) {
			$cmd = $data['cmd'];
			unset($data['cmd']);
			
			$config = $this->telegram->getCommandConfig($cmd);

			switch ($cmd) {
				case 'fauna':
					$answerData = FaunaCommand::processCallback($sender, $message, $data, $config);
					break;
				default:
					$answerData = [
						'text' => 'Service ' . $cmd . ' not supported',
						'show_alert' => true,
					];
					break;
			}
		}
		else {
			$answerData = [
				'text' => 'Missing cmd parameter',
				'show_alert' => true,
			];
		}
		
		return static::answer($callback_query_id, $answerData);
    }
}
