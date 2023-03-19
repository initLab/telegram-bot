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

use Exception;

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Entities\Update;
use Longman\TelegramBot\Entities\InlineKeyboardButton;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\User;
use Longman\TelegramBot\Entities\Message;

use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

use User890104\Fauna;

require __DIR__ . '/../lib/functions.php';

class FaunaCommand extends UserCommand
{
    protected $name = 'fauna';
    protected $description = 'Interacts with init Lab\'s Fauna application';
    protected $usage = '/fauna';
    protected $version = '1.0.0';
    protected $enabled = true;

	private static $provider = null;
	private static $token = null;

	private static function getProvider(array $config)
	{
		if (is_null(static::$provider)) {
			static::$provider = new Fauna([
				'clientId'     => $config['oauth_client_id'],
				'clientSecret' => $config['oauth_client_secret'],
				'redirectUri'  => BOT_BASE_URL . '/oauth.php',
			]);
		}

		return static::$provider;
	}

	private static function getFilename(User $sender, $type = 'access_token') {
	    $dir = __DIR__ . '/../storage/fauna';

	    if (!file_exists($dir)) {
	        mkdir($dir);
	    }

		return $dir . '/' . $sender->getId() . '_' . $type . '.txt';
	}

	private static function deleteParam(User $sender, $type = 'access_token') {
		$filename = static::getFilename($sender, $type);

		if (file_exists($filename)) {
			unlink($filename);
		}
	}

	private static function setParam(User $sender, $data, $type = 'access_token') {
		file_put_contents(static::getFilename($sender, $type), $data);
	}

	private static function getUrl($url) {
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
		]);
		$result = curl_exec($ch);
		$errno = curl_errno($ch);
		if ($errno !== CURLE_OK) {
			throw new Exception(curl_error($ch), $errno);
		}
		curl_close($ch);
		return $result;
	}

	private static function getJson($url) {
		if (substr($url, 0, 4) !== 'http' && !is_readable($url)) {
			throw new Exception('Unable to open file');
		}

		try {
			$data = static::getUrl($url);
		}
		catch (Exception $e) {
			if ($e->getCode() !== 3) {
				throw $e;
			}

			$data = file_get_contents($url);

			if ($data === false) {
				throw new Exception('Unable to read file');
			}
		}

		$data = json_decode($data, true);

		if ($data === false) {
			throw new Exception('Error decoding data');
		}

		return $data;
	}

	private static function buildCbData(array $data = [])
	{
		return http_build_query(array_merge([
			'cmd' => 'fauna',
		], $data));
	}

	private static function getStatusMessage(User $sender, $config)
	{
        $auth = static::checkAuth($sender, $config);

        if ($auth !== true) {
            return $auth;
        }

        $provider = static::getProvider($config);

        $request = $provider->getAuthenticatedRequest(
            Fauna::METHOD_GET,
            'https://fauna.initlab.org/api/doors.json',
            static::$token);

        try {
            $doors = $provider->getResponse($request);
        }
        catch (Exception $e) {
            return [
                'text' => 'Action failed: ' . $e->getMessage(),
            ];
        }

        $doors = json_decode($doors->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

		$icons = [
			'locked' => '🔒',
			'unlocked' => '🔓',
			'unknown' => '❓',
			'open' => '🚪',
		];

        $markup = [
            'label' => static fn(string $text) => new InlineKeyboardButton([
                'text' => $text,
                'callback_data' => static::buildCbData(),
            ]),
            'lock' => static fn(string $doorId) => new InlineKeyboardButton([
                'text' => $icons['locked'] . ' Lock',
                'callback_data' => static::buildCbData([
                    'action' => 'lock',
                    'doorId' => $doorId,
                ]),
            ]),
            'open' => static fn(string $doorId) => new InlineKeyboardButton([
                'text' => $icons['open'] . ' Open',
                'callback_data' => static::buildCbData([
                    'action' => 'open',
                    'doorId' => $doorId,
                ]),
            ]),
            'unlock' => static fn(string $doorId) => new InlineKeyboardButton([
                'text' => $icons['unlocked'] . ' Unlock',
                'callback_data' => static::buildCbData([
                    'action' => 'unlock',
                    'doorId' => $doorId,
                ]),
            ]),
        ];

        $kb = new InlineKeyboard([
            'inline_keyboard' => array_merge(...array_map(static fn(array $door) => [[
                $markup['label']($door['name'])
            ], array_map(static fn(string $action) =>
                $markup[$action]($door['id']),
            $door['supported_actions'])], $doors)),
        ]);

		return [
			'text' => 'Choose an action',
			'reply_markup' => $kb,
		];
	}

	private static function getState(User $sender)
    {
        return file_get_contents(static::getFilename($sender, 'state'));
    }

	private static function getAccessToken(User $sender, array $config) {
		try {
			$data = static::getJson(static::getFilename($sender));
		}
		catch (Exception $e) {
			static::deleteParam($sender);
			throw new Exception('Unable to load access token');
		}

		$accessToken = new AccessToken($data);

		if ($accessToken->hasExpired()) {
			try {
				$accessToken = static::getProvider($config)->getAccessToken('refresh_token', [
					'refresh_token' => $accessToken->getRefreshToken(),
				]);
				static::setParam($sender, json_encode($accessToken));
			}
			catch (IdentityProviderException $e) {
				// Failed to refresh the access token.
				static::deleteParam($sender);
				throw new Exception('Unable to refresh access token');;
			}
		}

		return $accessToken;
	}

	private static function startAuth(User $sender, array $config) {
		$provider = static::getProvider($config);
		/** @var Fauna $provider */
		$url = $provider->getAuthorizationUrl();
        static::setParam($sender, $provider->getState(), 'state');
        return $url;
	}

	private static function checkAuth(User $sender, array $config) {
		if (!is_null(static::$token)) {
			return true;
		}

		try {
			$token = static::getAccessToken($sender, $config);
		}
		catch (Exception $e) {
			return [
				'text' => 'You are not logged in to Fauna.',
				'reply_markup' => new InlineKeyboard([
					'inline_keyboard' => [
						[
							new InlineKeyboardButton([
								'url' => static::startAuth($sender, $config),
								'text' => 'Login',
							]),
						],
					],
				]),
			];
		}

		static::$token = $token;

		return true;
	}

	private static function executeCommand(User $sender, $action, $doorId, array $config)
	{
        $auth = static::checkAuth($sender, $config);

        if ($auth !== true) {
            return $auth;
        }

        $provider = static::getProvider($config);

		$request = $provider->getAuthenticatedRequest(
			Fauna::METHOD_POST,
			sprintf('https://fauna.initlab.org/api/doors/%s/%s', $doorId, $action),
			static::$token);

		try {
			$doors = $provider->getResponse($request);
		}
		catch (Exception $e) {
			return [
				'text' => 'Action failed: ' . $e->getMessage(),
			];
		}

		return true;
	}

	public static function processStart(User $sender, Message $message, $data, array $config) {
		$code = urlBase64Encode(substr($data, 0, 32));
		$state = substr($data, 32);
		$savedState = static::getState($sender);

		if ($state !== $savedState) {
            throw new Exception('State does not match');
		}

		static::deleteParam($sender, 'state');

		try {
			// Try to get an access token using the authorization code grant.
			$accessToken = static::getProvider($config)->getAccessToken('authorization_code', [
				'code' => $code,
			]);

			static::setParam($sender, json_encode($accessToken));

			$text = 'Login successful!';
		}
		catch (IdentityProviderException $e) {
			// Failed to get the access token or user details.
			$text = 'There was a problem accessing your data: ' . $e->getMessage() . '. Please try again. /fauna';
		}
		catch (Exception $e) {
			var_dump($e);
			$text = 'Unclassified error: ' . $e->getMessage() . '. Please try again. /fauna';
		}

		$chat_id = $message->getChat()->getId();
		$reply_to_message_id = $chat_id < 0 ? $message->getMessageId() : null;

		return Request::sendMessage(compact('chat_id', 'text', 'reply_to_message_id'));
	}

	public static function processCallback(User $sender, Message $message, array $data, array $config)
	{
		if (!array_key_exists('action', $data)) {
			return [
				'text' => 'No action provided',
			];
		}

        $action = $data['action'];

        if (!in_array($action, ['open', 'lock', 'unlock'])) {
            return [
                'text' => 'Unknown command ' . $action,
            ];
        }

        // action is open, lock or unlock
        if (!array_key_exists('doorId', $data)) {
            return [
                'text' => 'No door ID provided',
            ];
        }

        $doorId = $data['doorId'];
        $result = static::executeCommand($sender, $action, $doorId, $config);

        $text = 'Action successful!';

        if ($result !== true) {
            Request::sendMessage(array_merge([
                'chat_id' => $sender->getId(),
            ], $result));

            $text = 'Command ' . $action . ' failed!';

            return compact('text');
        }

		$messageData = static::getStatusMessage($sender, $config);

		if ($message->getText() !== $messageData['text']) {
			try {
				Request::editMessageText(array_merge([
					'chat_id' => $message->getChat()->getId(),
					'message_id' => $message->getMessageId(),
				], $messageData));
			}
			catch (TelegramException $e) {
				// fail silently if edit fails
			}
		}

		return compact('text');
	}

    public function execute()
    {
        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
		$sender = $message->getFrom();
		$sender_id = $sender->getId();
		$message_id = $message->getMessageId();
		$reply_to_message_id = $chat_id < 0 ? $message_id : null;
		$replyData = compact('chat_id', 'reply_to_message_id');
		$text = trim($message->getText(true));
		$config = $this->getConfig();

		if ($text === '') {
			// Current status
			return Request::sendMessage(array_merge($replyData, static::getStatusMessage($sender, $config)));
		}

        [
            $action,
            $firstArgument,
        ] = explode(' ', $text);

		// Self deauth

		if ($action === 'deauth') {
			static::deleteParam($sender, 'state');
			static::deleteParam($sender);
			return Request::sendMessage(array_merge($replyData, [
				'text' => 'Deauthentication successful',
			]));
		}

		if (!in_array($action, ['open', 'lock', 'unlock'])) {
			return Request::sendMessage(array_merge($replyData, [
				'text' => 'Invalid command',
			]));
		}

		$response = Request::sendMessage(array_merge($replyData, [
			'parse_mode' => 'Markdown',
			//'text' => 'Executing command *' . $text . '* on behalf of ' . $resourceOwner['name'] . ' (' . $resourceOwner['username'] . ')',
			'text' => 'Executing command *' . $action . '*',
		]));

		$replyData['reply_to_message_id'] = $chat_id < 0 ? $response->getResult()->getMessageId() : null;

		$result = static::executeCommand($sender, $action, $firstArgument, $config);

		if ($result !== true) {
			return Request::sendMessage(array_merge($replyData, $result));
		}

		return Request::sendMessage(array_merge($replyData, [
			'text' => 'Action successful',
		]));
	}
}
