<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2015/3/6
 * Time: 15:47
 */
namespace spoonwep\OAuth2\Client\Provider;

use League\OAuth2\Client\Entity\User;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;

class qq extends AbstractProvider
{

	public $responseType = 'string';

	public $domain = "https://graph.qq.com";

	public $method = "GET";

	public $clientId;

	public $clientSecret;

	public $openid;

	public function __construct ($options = [])
	{
		parent::__construct();
		foreach ($options as $option => $value) {
			if (property_exists($this, $option)) {
				$this->{$option} = $value;
			}
		}
	}

	public function urlAuthorize ()
	{
		return $this->domain . '/oauth2.0/authorize';
	}

	public function urlAccessToken ()
	{
		return $this->domain . '/oauth2.0/token';
	}

	protected function fetchUserDetails (AccessToken $token)
	{
		$url = $this->urlUserDetails($token);

		$data = $this->fetchProviderData($url);

		return $data;
	}

	public function urlUserDetails (AccessToken $token)
	{
		$OpenidJson   = $this->fetchOpenid($token);
		$openId       = json_decode($OpenidJson, TRUE);
		$this->openid = $openId['openid'];

		return $this->domain . '/user/get_user_info?access_token=' . $token->accessToken . '&oauth_consumer_key=' . $this->clientId . '&openid=' . $openId['openid'];
	}

	protected function fetchOpenid (AccessToken $token)
	{
		$url  = $this->urlOpenid($token);
		$data = $this->fetchProviderData($url);

		if (strpos($data, "callback") !== FALSE) {
			$data = str_replace("callback( ", "", $data);
			$data = str_replace(");", "", $data);
		}

		return $data;
	}

	protected function urlOpenid (AccessToken $token)
	{
		return $this->domain . '/oauth2.0/me?access_token=' . $token->accessToken;
	}

	public function userDetails ($response, AccessToken $token)
	{
		$user = new User();

		$user->exchangeArray([
			'uid'                => $this->openid,
			'nickname'           => $response->nickname,
			'figureurl'          => $response->figureurl,
			'figureurl_1'        => $response->figureurl_qq_1,
			'figureurl_2'        => $response->figureurl_2,
			'figureurl_qq_1'     => $response->figureurl_qq_1,
			'figureurl_qq_2'     => $response->figureurl_qq_2,
			'gender'             => $response->gender,
			'is_yellow_vip'      => $response->is_yellow_vip,
			'vip'                => $response->vip,
			'yellow_vip_level'   => $response->yellow_vip_level,
			'level'              => $response->level,
			'is_yellow_year_vip' => $response->is_yellow_year_vip
		]);

		return $user;
	}

}