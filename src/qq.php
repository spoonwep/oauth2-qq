<?php namespace spoonwep\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use spoonwep\OAuth2\Client\qqResourceOwner;

class Qq extends AbstractProvider
{
	use BearerAuthorizationTrait;

	/**
	 * @var
	 */
	public $openid;

	/**
	 * @var string
	 */
	public $domain = "https://graph.qq.com";

	/**
	 * Get authorization url to begin OAuth flow
	 *
	 * @return string
	 */
	public function getBaseAuthorizationUrl ()
	{
		return $this->domain . '/oauth2.0/authorize';
	}

	/**
	 * Get access token url to retrieve token
	 * @param array $params
	 * @return string
	 */
	public function getBaseAccessTokenUrl (array $params)
	{
		return $this->domain . '/oauth2.0/token';
	}

	/**
	 * Get provider url to fetch user details
	 * @param AccessToken $token
	 * @return string
	 */
	public function getResourceOwnerDetailsUrl (AccessToken $token)
	{
		$OpenidJson   = $this->fetchOpenid($token);
		$openId       = json_decode($OpenidJson, TRUE);
		$this->openid = $openId['openid'];

		return $this->domain . '/user/get_user_info?access_token=' . $token . '&oauth_consumer_key=' . $this->clientId . '&openid=' . $openId['openid'];
	}

	/**
	 * Get openid url to fetch it
	 * @param AccessToken $token
	 * @return string
	 */
	protected function getOpenidUrl (AccessToken $token)
	{
		return $this->domain . '/oauth2.0/me?access_token=' . $token;
	}

	/**
	 * Get openid
	 * @param AccessToken $token
	 * @return mixed
	 */
	protected function fetchOpenid (AccessToken $token)
	{
		$url     = $this->getOpenidUrl($token);
		$request = $this->getAuthenticatedRequest(self::METHOD_GET, $url, $token);
        $data    = $this->getSpecificResponse($request);
        if (strpos($data, "callback") !== FALSE) {
            preg_match('/{(.*)}/', $data, $data);
            $data = $data[0];
        }

		return $data;
	}

	/**
	 * get accesstoken
	 *
	 * The Content-type of server's returning is 'text/html;charset=utf-8'
	 * so it has to be rewritten
	 *
	 * @param mixed $grant
	 * @param array $options
	 * @return AccessToken
	 */
	public function getAccessToken ($grant, array $options = [])
	{
		$grant = $this->verifyGrant($grant);

		$params = [
			'client_id'     => $this->clientId,
			'client_secret' => $this->clientSecret,
			'redirect_uri'  => $this->redirectUri,
		];

		$params   = $grant->prepareRequestParameters($params, $options);
		$request  = $this->getAccessTokenRequest($params);
		$response = $this->getSpecificResponse($request);
		$prepared = $this->prepareAccessTokenResponse($response);
		$token    = $this->createAccessToken($prepared, $grant);

		return $token;
	}

	/**
	 * @param RequestInterface $request
	 * @return mixed
	 * @throws IdentityProviderException
	 */
	protected function getSpecificResponse (RequestInterface $request)
	{
        $response = $this->getResponse($request);
		$parsed   = $this->parseSpecificResponse($response);

		$this->checkResponse($response, $parsed);

		return $parsed;
	}

	/**
	 * A specific parseResponse function
	 * @param ResponseInterface $response
	 * @return mixed
	 */
	protected function parseSpecificResponse (ResponseInterface $response)
	{
		return (string)$response->getBody();
	}

	/**
	 * Check a provider response for errors.
	 *
	 * @throws IdentityProviderException
	 * @param  ResponseInterface $response
	 * @param  string $data Parsed response data
	 * @return void
	 */
	protected function checkResponse (ResponseInterface $response, $data)
	{
		if (isset($data['error'])) {
			throw new IdentityProviderException($data['error_description'], $response->getStatusCode(), $response);
		}
	}

	/**
	 * Get the default scopes used by this provider.
	 *
	 * This should not be a complete list of all scopes, but the minimum
	 * required for the provider user interface!
	 *
	 * @return array
	 */
	protected function getDefaultScopes ()
	{
		return [];
	}

	/**
	 * Generate a user object from a successful user details request.
	 * @param array $response
	 * @param AccessToken $token
	 * @return qqResourceOwner
	 */
	protected function createResourceOwner (array $response, AccessToken $token)
	{
		return new qqResourceOwner($response);
	}
}
