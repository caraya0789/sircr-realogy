<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

use Carbon_Fields\Container;
use Carbon_Fields\Field;

class SIRCR_Realogy_Settings {

	protected static $_instance;

	public static function get_instance() {
		if( null === self::$_instance )
			self::$_instance = new self();

		return self::$_instance;
	}

	public function __construct() {
		$this->_includes();
		$this->_init();
	}

	public function _includes() {
		require_once SIRREAL_PATH . '/vendor/autoload.php';
	}

	public function _init() {
		\Carbon_Fields\Carbon_Fields::boot();

		add_action( 'carbon_fields_register_fields', [ $this, 'page_settings' ] );
	}

	public function page_settings() {
		// wp_die( $parent );
		Container::make( 'theme_options', 'API Settings' )
		 	->set_page_parent( 'sircr_realogy_api' )
		    ->add_fields( array(

		    	Field::make( 'radio', 'sircr_realogy_api_type', 'API Environment' )
				    ->add_options([
				        'test' => 'Staging',
				        'prod' => 'Production'
				    ]),

		    	Field::make( 'separator', 'sircr_realogy_section_staging', 'Staging' ),

		        Field::make( 'text', 'sircr_realogy_test_api_auth_endpoint', 'Token URL' ),
		        
		        Field::make( 'text', 'sircr_realogy_test_api_auth_client_id', 'Client ID' ),

		        Field::make( 'text', 'sircr_realogy_test_api_auth_client_secret', 'Client Secret' ),

		        Field::make( 'text', 'sircr_realogy_test_api_auth_subscription_key', 'Subscription Key' ),

		        Field::make( 'text', 'sircr_realogy_test_api_auth_scope', 'Scope' ),

		        Field::make( 'text', 'sircr_realogy_test_api_auth_url', 'API URL' ),

		        Field::make( 'separator', 'sircr_realogy_section_production', 'Production' ),

		        Field::make( 'text', 'sircr_realogy_api_auth_endpoint', 'Token URL' ),
		        
		        Field::make( 'text', 'sircr_realogy_api_auth_client_id', 'Client ID' ),

		        Field::make( 'text', 'sircr_realogy_api_auth_client_secret', 'Client Secret' ),

		        Field::make( 'text', 'sircr_realogy_api_auth_subscription_key', 'Subscription Key' ),

		        Field::make( 'text', 'sircr_realogy_api_auth_scope', 'Scope' ),

		        Field::make( 'text', 'sircr_realogy_api_auth_url', 'API URL' )

		    ));
	}

	public function is_staging() {
		$env = get_option( '_sircr_realogy_api_type' );
		return (!$env || $env == 'test');
	}

	public function get( $key ) {
		$prefix = $this->is_staging() ? '_sircr_realogy_test_api_auth_' : '_sircr_realogy_api_auth_';
		return get_option( $prefix . $key );
	}

}