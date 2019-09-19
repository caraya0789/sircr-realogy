<?php 

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once SIRREAL_PATH . '/inc/Manager/Base.php';

class SIRCR_Realogy_Manager_Agent extends SIRCR_Realogy_Manager_Base {

	protected static $_instance;

	public static function get_instance() {
		if( null === self::$_instance ) 
			self::$_instance = new self();

		return self::$_instance;
	}

	public function __construct() {
		$this->post_type = 'agent';
		$this->meta_id = 'staffid';
	}

	public function get_languages_spoken( $languagesSpoken ) {
		$languages = array_map(function( $lang ) {
			return $lang['name'];
		}, $languagesSpoken);

		return implode(',', $languages);
	}

	public function map_fields( $agent ) {
		$summary = !empty($agent['agentSummary']) ? $agent['agentSummary'] : '';

		if(empty($agent['roles'][0]['role']) || stripos( $agent['roles'][0]['role'], 'sales' ) === false) {
			if(empty($summary['defaultPhotoURL']))
				return false;
		}

		return [
			'es' => [
			    "lastUpdateOn" => (!empty($agent['lastUpdateOn'])) ? $agent['lastUpdateOn'] : '',
				"staffid" => !empty($summary['id']) ? strtoupper( $summary['id'] ) : '',
				"description" => !empty($agent['remarks']) ? $this->get_remarks_by_lang( $agent['remarks'], 'es' ) : '',
				"name" => !empty($summary['name']) ? $summary['name'] : '',
				"photo" => !empty($summary['defaultPhotoURL']) ? 'https:' . $summary['defaultPhotoURL'] : '',
				"position" => !empty($agent['roles'][0]) ? $agent['roles'][0]['role'] : '',
				"languages" => !empty($agent['languagesSpoken']) ? $this->get_languages_spoken($agent['languagesSpoken']) : '',
				"phone" => !empty($summary['businessPhone']) ? $summary['businessPhone'] : '',
				"email" => !empty($summary['emailAddress']) ? $summary['emailAddress'] : ''
			],
			'en' => [
				"staffid" => !empty($summary['id']) ? $summary['id'] : '',
				"description" => !empty($agent['remarks']) ? $this->get_remarks_by_lang( $agent['remarks'], 'en' ) : '',
				"name" => !empty($summary['name']) ? $summary['name'] : ''
			]
		];
	}

	public function save_post_es( $agent_es ) {
		$agent_es = array_change_key_case($agent_es);

		// Build the new post data
		$postarr = array(
			'post_status' => 'publish',
			'post_type' => 'agent',
			'post_title' => $agent_es['name'],
			'meta_input' => $agent_es
		);

		// Do we need to update or create a new listing?
		$existing = $this->get_post( $agent_es['staffid'], 'es' );

		if( $existing ) 
			$postarr[ 'ID' ] = $existing[0]->ID;

		// set a language flag we can query later 
		$postarr[ 'meta_input' ][ 'lang' ] = 'es';
		// Save the new post
		$postid = wp_insert_post( $postarr, true );
		
		return $postid;
	}

	public function save_post_en( $agent_en ) {
		// Build the new post data
		$postarr = array(
			'post_status' => 'publish',
			'post_type' => 'agent',
			'post_title' => $agent_en['name'],
			'meta_input' => $agent_en
		);

		// Find the spanish sibling... This will be a translation of.
		$existing_es = $this->get_post( $agent_en['staffid'], 'es' );
		if(!$existing_es) 
			return;

		// Do we need to update or create?
		$existing_en = $this->get_post( $agent_en['staffid'], 'en' );

		if( $existing_en ) 
			$postarr[ 'ID' ] = $existing_en[0]->ID;;

		// The language flag
		$postarr[ 'meta_input' ][ 'lang' ] = 'en';
		$postarr[ 'meta_input' ] = $this->merge_meta( $existing_es[0]->ID, $postarr[ 'meta_input' ] );

		// Save
		$postid = wp_insert_post( $postarr, true );

		return $postid;
	}	

}