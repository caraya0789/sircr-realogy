<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class SIRCR_Realogy_Manager {

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

	protected function _includes() {
		require_once SIRREAL_PATH . '/inc/Manager/Property.php';
		require_once SIRREAL_PATH . '/inc/Manager/Agent.php';
	}

	protected function _init() {
		$this->api = SIRCR_Realogy_Api::get_instance();
		$this->property = SIRCR_Realogy_Manager_Property::get_instance();
		$this->agent = SIRCR_Realogy_Manager_Agent::get_instance();
	}

	protected function _touch() {
		update_option( 'sircr_realogy_last_update', current_time('U') );
	}

	public function save_property( $property_id ) {
		$this->_touch();
		$listing = $this->api->get_listing( $property_id );
		$result = $this->property->save( $listing );

		return $result;		
	}

	public function update_property( $property_id, $lastUpdatedOn ) {
		$this->_touch();
		$property_id = strtoupper($property_id);

		$existing = $this->property->get_updated( $property_id, $lastUpdatedOn, 'idaccount' );
		
		if($existing) {
			$existing['status'] = 'unchanged';
			return $existing;
		}

		$listing = $this->api->get_listing( $property_id );
		$existing = $this->property->get_existing( $property_id, 'idaccount' );
		if($existing) {
			$result = $this->property->save( $listing );
			if($result)
				$result['status'] = 'updated';

			return $result;
		}

		$listing = $this->api->get_listing( $property_id );
		$result = $this->property->save( $listing );
		if($result)
			$result['status'] = 'created';
		
		return $result;
	}

	public function save_agent( $agent_id ) {
		$this->_touch();
		$agent = $this->api->get_agent( $agent_id );
		$result = $this->agent->save( $agent );
		return $result;	
	}

	public function update_agent( $agent_id, $lastUpdatedOn ) {
		$this->_touch();
		$agent_id = strtoupper($agent_id);

		$existing = $this->agent->get_updated( $agent_id, $lastUpdatedOn );
		if($existing) {
			$existing['status'] = 'unchanged';
			return $existing;
		}

		$agent = $this->api->get_agent( $agent_id );
		$existing = $this->agent->get_existing( $agent_id );
		if($existing) {
			$result = $this->agent->save( $agent );
			if($result)
				$result['status'] = 'updated';

			return $result;
		}

		$result = $this->agent->save( $agent );
		if($result)
			$result['status'] = 'created';
		
		return $result;
	}

	public function disable_old_posts( $posts, $post_type ) {
		$this->_touch();
		$posts = implode(',', $posts);

		global $wpdb;
		$wpdb->update( $wpdb->prefix . 'posts', [ 'post_status' => 'draft' ], [ 'post_type' => $post_type ] );
		
		$wpdb->query( "UPDATE {$wpdb->prefix}posts SET post_status = 'publish' WHERE id IN ({$posts})" );
	}

	public function disable_old_properties( $properties ) {
		$this->_touch();
		$this->disable_old_posts( $properties, 'property' );
	}

	public function disable_old_agents( $agents ) {
		$this->_touch();
		$this->disable_old_posts( $agents, 'agent' );
	}

	public function get_amenities() {
		return get_option( 'sircr_realogy_amenities', [] );
	}

	public function get_amenities_translations() {
		return get_option( 'sircr_realogy_amenities_translations', [] );
	}

	public function save_amenities_translations( $translations ) {
		update_option( 'sircr_realogy_amenities_translations', $translations );
	}

	public function refetch_amenities() {
		$amenities = get_option( 'sircr_realogy_amenities', [] );
		$properties = get_posts([
			'post_type' => 'property',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'meta_key' => 'lang',
			'meta_value' => 'es'
		]);

		foreach($properties as $prop) {
			$propAm = get_post_meta( $prop->ID, 'amenities', true );
			if(empty($propAm))
				continue;

			$propAmArr = explode(',', $propAm);
			foreach($propAmArr as $p) {
				$clean_p = strtolower( trim($p) );
				if(!in_array($clean_p, $amenities))
					$amenities[] = $clean_p;
			}
		}

		sort($amenities);

		update_option( 'sircr_realogy_amenities', $amenities, false );
	}

}