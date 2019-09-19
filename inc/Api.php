<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class SIRCR_Realogy_Api {

	protected static $_instance;

	protected $_token;

	protected $_settings;

	public $url;

	public static function get_instance() {
		if( null === self::$_instance )
			self::$_instance = new self();

		return self::$_instance;
	}

	public function __construct() {
		$this->_settings = SIRCR_Realogy_Settings::get_instance();
		$this->url = $this->_settings->get( 'url' );
	}

	public function get_token( $force = false ) {
		if( null === $this->_token || $force ) {
			if( !empty( $_COOKIE['realogy_token'] ) && !$force ) {
				$this->_token = $_COOKIE['realogy_token'];
			} else {
				$response = wp_remote_post( $this->_settings->get( 'endpoint' ), [
					'body' => [
						'client_id' => $this->_settings->get( 'client_id' ),
						'client_secret' => $this->_settings->get( 'client_secret' ),
						'grant_type' => 'client_credentials',
						'scope' => $this->_settings->get( 'scope' )
					]
				]);

				$body = json_decode( wp_remote_retrieve_body( $response ) , true );
				$this->_token = ( !empty( $body['access_token'] ) ) ? $body['token_type'] . ' ' . $body['access_token'] : '';
				setcookie( 'realogy_token', $this->_token, time() + 3600, '/' );
			}
		}

		return $this->_token;
	}

	protected function _request( $resource ) {
		$token = $this->get_token();

		$response = wp_remote_get( $this->url . $resource, [
			'headers' => [
				'Ocp-Apim-Subscription-Key' => $this->_settings->get( 'subscription_key' ),
				'Authorization' => $token
			]
		]);

		return json_decode( wp_remote_retrieve_body( $response ), true );
	}

	public function get_agents() {
		$agents = $this->_request( '/agents/active?countryCode=CR' );
		return $agents;
	}

	public function get_agent( $id ) {
		$agent = $this->_request( "/agents/{$id}" );
		return $agent;
	}

	public function get_listings() {
		$listings = $this->_request( '/listings/active?countryCode=CR' );
		return $listings;
	}

	public function get_listing( $id ) {
		$listing = $this->_request( "/listings/{$id}" );
		return $listing;
	}

}