<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

abstract class SIRCR_Realogy_Manager_Base {

	public $post_type;

	public $meta_id;

	abstract public function map_fields( $entity ); 

	abstract public function save_post_es( $entity_es );
	
	abstract public function save_post_en( $entity_en );

	public function get_updated( $id, $updatedOn, $meta_id = false ) {
		$meta_id = !$meta_id ? $this->meta_id : $meta_id;

		$post = get_posts( array(
			'post_type' => $this->post_type,
			'post_status' => 'any',
			'meta_query' => array(
				array(
					'key' => $meta_id,
					'value' => $id
				),
				array(
					'key' => 'lastupdateon',
					'value' => $updatedOn
				),
				array(
					'key' => 'lang',
					'value' => 'es'
				)
			),
			'posts_per_page' => 1
		));

		if(!$post)
			return false;

		$post_en = $this->get_post( $id, 'en', $meta_id );

		return [
			'es' => $post[0]->ID,
			'en' => $post_en[0]->ID,
			'name' => $post[0]->post_title
		];
	}

	public function get_existing( $id, $meta_id = false ) {
		$meta_id = !$meta_id ? $this->meta_id : $meta_id;

		$post = $this->get_post( $id, 'es', $meta_id );
		if(!$post)
			return false;

		$post_en = $this->get_post( $id, 'en', $meta_id );

		return [
			'es' => $post[0]->ID,
			'en' => $post_en[0]->ID,
			'name' => $post[0]->post_title
		];
	}

	public function merge_meta( $existing, $meta ) {
		// API Translations include little data, in WordPress we need to replicate all date
		// So lets fetch the post meta from the spanish sibling
		$_existing_meta = get_post_meta( $existing );
		$existing_meta = [];
		foreach($_existing_meta as $key => $val) {
			$existing_meta[$key] = $val[0];
		}

		// Let's merge the 2 together, spanish keys will override spanish ones for this post.
		return array_merge( $existing_meta, $meta );
	}

	public function get_remarks_by_lang( $remarks, $lang ) {
		foreach($remarks as $rem) {
			if( stripos( $rem['languageCode'], $lang ) !== false)
				return (!empty($rem['htmlRemark'])) ? $rem['htmlRemark'] : nl2br( $rem['remark'] );
		}

		return (!empty($remarks[0]['htmlRemark'])) ? $remarks[0]['htmlRemark'] : nl2br( $remarks[0]['remark'] );
	}

	public function get_post( $id, $lang, $meta_id = false ) {
		$meta_id = (!$meta_id) ? $this->meta_id : $meta_id;

		return get_posts( array(
			'post_type' => $this->post_type,
			'post_status' => 'any',
			'meta_query' => array(
				array(
					'key' => $meta_id,
					'value' => $id
				),
				array(
					'key' => 'lang',
					'value' => $lang
				)
			),
			'posts_per_page' => 1
		));
	}

	public function update_translations( $esid, $enid ) {

        $wpml_element_type = apply_filters( 'wpml_element_type', $this->post_type );

        $original_post_language_info = apply_filters( 'wpml_element_language_details', null, [
        	'element_id' => $esid, 
        	'element_type' => $this->post_type
        ]);

        $set_language_args_es = array(
            'element_id'    => $esid,
            'element_type'  => $wpml_element_type,
            'trid'   => $original_post_language_info->trid,
            'language_code'   => 'es'
        );
        do_action( 'wpml_set_element_language_details', $set_language_args_es );
         
        $set_language_args_en = array(
            'element_id'    => $enid,
            'element_type'  => $wpml_element_type,
            'trid'   => $original_post_language_info->trid,
            'language_code'   => 'en',
            'source_language_code' => 'es'
        );
 
        do_action( 'wpml_set_element_language_details', $set_language_args_en );
	}

	public function save( $entity ) {
		$fields = $this->map_fields( $entity );

		if( false === $fields )
			return [];

		$original_post_id =  $this->save_post_es( $fields['es'] );
    	$translated_post_id = $this->save_post_en( $fields['en'] );

        $this->update_translations( $original_post_id, $translated_post_id );

		return [
			'es' => $original_post_id,
			'en' => $translated_post_id,
			'name' => $fields['es']['name']
		];
	}

}