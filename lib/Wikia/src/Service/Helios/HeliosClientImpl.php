<?php
namespace Wikia\Service\Helios;
use Wikia\Util\GlobalStateWrapper;

/**
 * A client for Wikia authentication service.
 *
 * This is a naive implementation.
 */
class HeliosClientImpl implements HeliosClient
{
	const BASE_URI = "helios_base_uri";
	const CLIENT_ID = "client_id";
	const CLIENT_SECRET = "client_secret";

	protected $baseUri;
	protected $clientId;
	protected $clientSecret;
	protected $status;

	/**
	 * @Inject({
	 *   Wikia\Service\Helios\HeliosClientImpl::BASE_URI,
	 *   Wikia\Service\Helios\HeliosClientImpl::CLIENT_ID,
	 *   Wikia\Service\Helios\HeliosClientImpl::CLIENT_SECRET})
	 * The constructor.
	 * @param string $baseUri
	 * @param string $clientId
	 * @param string $clientSecret
	 */
	public function __construct( $baseUri, $clientId, $clientSecret )
	{
		$this->baseUri = $baseUri;
		$this->clientId = $clientId;
		$this->clientSecret = $clientSecret;
	}

	/**
	 * Returns the status of the last request.
	 */
	public function getStatus()
	{
		return $this->status;
	}

	/**
	 * The general method for handling the communication with the service.
	 */
	public function request( $resourceName, $getParams = [], $postData = [], $extraRequestOptions = [] )
	{
		// Crash if we cannot make HTTP requests.
		\Wikia\Util\Assert::true( \MWHttpRequest::canMakeRequests() );

		// Add client_id and client_secret to the GET data.
		$getParams['client_id'] = $this->clientId;
		$getParams['client_secret'] = $this->clientSecret;

		// Request URI pre-processing.
		$uri = "{$this->baseUri}{$resourceName}?" . http_build_query($getParams);

		// Request options pre-processing.
		$options = [
			'method'          => 'GET',
			'timeout'         => 5,
			'postData'        => $postData,
			'noProxy'         => true,
			'followRedirects' => false,
			'returnInstance'  => true,
			'internalRequest' => true,
		];

		$options = array_merge( $options, $extraRequestOptions );

		/*
		 * MediaWiki's MWHttpRequest class heavily relies on Messaging API
		 * (wfMessage()) which happens to rely on the value of $wgLang.
		 * $wgLang is set after $wgUser. On per-request authentication with
		 * an access token we use MWHttpRequest before wgUser is created so
		 * we need $wgLang to be present. With GlobalStateWrapper we can set
		 * the global variable in the local, function's scope, so it is the
		 * same as the already existing $wgContLang.
		 */
		global $wgContLang;
		$wrapper = new GlobalStateWrapper( [ 'wgLang' => $wgContLang ] );

		// Request execution.
		/** @var \MWHttpRequest $request */
		$request = $wrapper->wrap( function() use ( $options, $uri ) {
			return \Http::request( $options['method'], $uri, $options );
		} );

		$this->status = $request->status;

		$response = $request->getContent();
		$output = json_decode( $response );

		if ( !$output ) {
			$data[ "response" ] = $response;
			throw new ClientException ( 'Invalid Helios response.', 0, null, $data );
		}

		return $output;
	}

	/**
	 * A shortcut method for login requests.
	 *
	 * @throws ClientException
	 */
	public function login( $username, $password )
	{
		// Convert the array to URL-encoded query string, so the Content-Type
		// for the POST request is application/x-www-form-urlencoded.
		// It would be multipart/form-data which is not supported
		// by the Helios service.
		$postData = http_build_query([
			'username'	=> $username,
			'password'	=> $password
		]);

		$response = $this->request(
			'token',
			[ 'grant_type'	=> 'password' ],
			$postData,
			[ 'method'	=> 'POST' ]
		);

		return $response;
	}

	/**
	 * A shortcut method for info requests
	 */
	public function info( $token )
	{
		return $this->request(
			'info',
			[ 'code' => $token ]
		);
	}

	/**
	 * A shortcut method for refresh token requests.
	 */
	public function refreshToken( $token )
	{
		return $this->request(
			'token',
			[
				'grant_type'	=> 'refresh_token',
				'refresh_token'	=> $token
			]
		);
	}

	/**
	 * A shortcut method for token invalidation requests.
	 *
	 * @param $token string - a token to be invalidated
	 *
	 * @return string - json encoded response
	 */
	public function invalidateToken( $token )
	{
		return $this->request(
			'token',
			[ 'code' => $token ],
			[],
			[ 'method' => 'DELETE' ]
		);
	}

	/**
	* A shortcut method for register requests.
	*/
	public function register( $username, $password, $email, $birthdate, $langCode )
	{
			// Convert the array to URL-encoded query string, so the Content-Type
			// for the POST request is application/x-www-form-urlencoded.
			// It would be multipart/form-data which is not supported
			// by the Helios service.
			$postData = http_build_query( [
				'username'  => $username,
				'password'  => $password,
				'email'     => $email,
				'birthdate' => $birthdate,
				'langCode'  => $langCode,
			] );

			return $this->request(
				'users',
				[],
				$postData,
				[ 'method'	=> 'POST' ]
			);
	}

}
