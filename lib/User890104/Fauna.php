<?php
namespace User890104;

use Exception;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Fauna extends AbstractProvider {
	use BearerAuthorizationTrait;

	const BASE_URL = 'https://fauna.initlab.org';
	
	public function getBaseAuthorizationUrl() {
		return static::BASE_URL . '/oauth/authorize';
	}
	
	public function getBaseAccessTokenUrl(array $params) {
		return static::BASE_URL . '/oauth/token';
	}
	
	public function getResourceOwnerDetailsUrl(AccessToken $token) {
		return static::BASE_URL . '/api/current_user.json';
	}
	
	protected function getDefaultScopes() {
		return [
		    'public',
		    'account_data_read',
		    'door_handle_control',
		    'door_latch_control',
        ];
	}
	
	protected function getScopeSeparator() {
		return ' ';
	}
	
	protected function parseResponse(ResponseInterface $response) {
		$code = $response->getStatusCode();
		
		if ($code >= 400) {
			throw new Exception('HTTP Error: ' . $response->getReasonPhrase() . ' (' . $code . ')');
		}
		
		return parent::parseResponse($response);
	}
	
	protected function checkResponse(ResponseInterface $response, $data) {
		if (isset($data['error'])) {
			throw new Exception('OAuth Error: ' . $data['error'] . '//' . var_export($data, true));
		}
	}
	
	protected function createResourceOwner(array $response, AccessToken $token) {
		return $response;
	}
	
	protected function getRandomState($length = 10) {
		return parent::getRandomState($length);
	}
}
