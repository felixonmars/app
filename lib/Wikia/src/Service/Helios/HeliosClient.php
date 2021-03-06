<?php

namespace Wikia\Service\Helios;

interface HeliosClient {

	/**
	 * A shortcut method for login requests.
	 *
	 * @throws ClientException
	 */

	public function login( $username, $password );

	/**
	 * A shortcut method for token invalidation requests.
	 *
	 * @param $token string - a token to be invalidated
	 * @param $userId integer - the current user id
	 *
	 * @return string - json encoded response
	 */
	public function invalidateToken( $token, $userId );

	/**
	 * A shortcut method for register requests.
	 */
	public function register( $username, $password, $email, $birthDate, $langCode );

	/**
	 * A shortcut method for info requests
	 */
	public function info( $token );
}
