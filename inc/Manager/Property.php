<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require_once SIRREAL_PATH . '/inc/Manager/Base.php';

class SIRCR_Realogy_Manager_Property extends SIRCR_Realogy_Manager_Base {

	protected static $_instance;

	public static function get_instance() {
		if( null === self::$_instance ) 
			self::$_instance = new self();

		return self::$_instance;
	}

	public function __construct() {
		$this->post_type = 'property';
		$this->meta_id = 'idweb';
	}

	public function get_photos( $media ) {
		return $this->get_listing_media( 'all', 'Listing Photo', $media );
	}

	public function get_listing_media( $index, $category, $media ) {
		// Filter by category
		$items = array_filter($media, function( $item ) use ($category) {
			return $item['category'] == $category;
		});

		// if not found return
		if(count($items) === 0)
			return '';

		// Sort by sequence
		usort($items, function($a, $b) {
			return $a['sequenceNumber'] - $b['sequenceNumber'];
		});

		// return all urls
		if($index === 'all') {
			$result = array_map(function($item) {
				return $item['url'];
			}, $items);

			return $result;
		}

		// return single item url
		return !empty($items[$index]) ? $items[$index]['url'] : '';
	}

	public function get_amenities( $features ) {
		$amenities = array_map(function( $feature ){
			return $feature['description'];
		}, $features);

		return implode(',', $amenities);
	}

	public function get_life_styles( $features ) {

		$common = array(
			'Beach',
			'CountryLiving',
			'Eco-friendly',
			'Equestrian',
			'Farm & Ranch',
			'Gated Community',
			'Golf',
			'Historic',
			'Metropolitan',
			'Mountain',
			'Retirement',
			'Waterfront'
		);

		$lifestyles = [];
		foreach($features as $feature) {
			if(in_array($feature['description'], $common)) {
				$lifestyles[] = $feature['description'];
			} elseif( $feature['description'] == 'Country Living' ) {
				$lifestyles[] = 'CountryLiving';
			} elseif( stripos($feature['description'], 'Eco-Friendly') !== false ) {
				$lifestyles[] = 'Eco-friendly';
			} elseif( stripos($feature['description'], 'Equestrian') !== false ) {
				$lifestyles[] = 'Equestrian';
			} elseif( stripos($feature['description'], 'Farm') !== false ) {
				$lifestyles[] = 'Farm & Ranch';
			} elseif( stripos($feature['description'], 'Gated') !== false ) {
				$lifestyles[] = 'Gated Community';
			} elseif( stripos($feature['description'], '55+') !== false ) {
				$lifestyles[] = 'Retirement';
			} elseif( stripos($feature['description'], 'Ocean') !== false ) {
				$lifestyles[] = 'Beach';
			} elseif( stripos($feature['description'], 'Historic') !== false ) {
				$lifestyles[] = 'Historic';
			}
		}

		return $lifestyles;
	}

	public function get_lot_size( $size ) {
		if( strpos($size, 'SM') !== false ) {
			$size = (float) trim( str_replace('SM', '', $size) );
			return $size * 0.000247105;
		} else {
			return (float) trim( str_replace('AC', '', $size) );
		}
	}

	public function map_fields( $listing ) {
		if(empty($listing['listingSummary']))
			return false;

		$summary = $listing['listingSummary'];
		
		$photos = $this->get_photos( $listing['media'] );
		
		return [
			'es' => [
				"IdWeb" => (!empty($summary['RFGListingId'])) ? $summary['RFGListingId'] : '',
			    "IdAccount" => (!empty($summary['listingId'])) ? strtoupper( $summary['listingId'] ) : '',
			    "lastUpdateOn" => (!empty($summary['lastUpdateOn'])) ? $summary['lastUpdateOn'] : '',
			    "DateNewSource" => (!empty($summary['listedOn'])) ? $summary['listedOn'] : '',
			    "Address" => (!empty($summary['propertyAddress']['streetAddress'])) ? $summary['propertyAddress']['streetAddress'] : '',
			    "AddrDisplay" => (!empty($summary['propertyName'])) ? $summary['propertyName'] : '',
			    "name" => (!empty($summary['propertyName'])) ? $summary['propertyName'] : '',
			    "AddrNeighborhood" => '', 
			    "AddrCity" => (!empty($summary['propertyAddress']['city'])) ? $summary['propertyAddress']['city'] : '',
			    "Subdivision" => (!empty($summary['propertyAddress']['district'])) ? $summary['propertyAddress']['district'] : '',
			    "AddrCounty" => '',
			    "AddrState" => (!empty($summary['propertyAddress']['stateProvince'])) ? $summary['propertyAddress']['stateProvince'] : '',
			    "AddrZip" => (!empty($summary['propertyAddress']['postalCode'])) ? $summary['propertyAddress']['postalCode'] : '',
			    "AddrCountry" => (!empty($summary['propertyAddress']['country'])) ? $summary['propertyAddress']['country'] : '',
			    "Latitude" => (!empty($summary['propertyAddress']['latitude'])) ? $summary['propertyAddress']['latitude'] : '',
			    "Longitude" => (!empty($summary['propertyAddress']['longitude'])) ? $summary['propertyAddress']['longitude'] : '',
			    "Price" => (!empty($summary['listPrice']['amount'])) ? $summary['listPrice']['amount'] : '',
			    "LocalPrice" => (!empty($summary['listPrice']['amount'])) ? $summary['listPrice']['amount'] : '',
			    "LocalCurrency" => (!empty($summary['listPrice']['currencyCode'])) ? $summary['listPrice']['currencyCode'] : '',
			    "PriceDisplay" => (!empty($summary['listPrice']['amount'])) ? '$' . number_format((double) $summary['listPrice']['amount'], 2) : '$0.00',
			    "Bedroom" => (!empty($summary['noOfBedrooms'])) ? $summary['noOfBedrooms'] : '',
			    "BathsFull" =>  (!empty($listing['fullBath'])) ? $listing['fullBath'] : '',
			    "BathsPartial" => (!empty($listing['halfBath'])) ? $listing['halfBath'] : '',
			    "CodePropertyType" => (!empty($summary['propertyType'])) ? $summary['propertyType'] : '',
			    "LotAcreage" => (!empty($summary['lotSize'])) ? $this->get_lot_size( $summary['lotSize'] ) : '',
			    "LotSizeUnit" =>  (!empty($summary['lotSize'])) ? (stripos( $summary['lotSize'], 'SM' ) !== false ? 'SM' : 'AC') : '',
			    "LotSizeDisplay" => (!empty($summary['lotSize'])) ? $summary['lotSize'] : '',
			    "BuiltYear" => !empty($listing['yearBuilt']) ? $listing['yearBuilt'] : '',
			    "SqFeetInterior" => (!empty($summary['squareFootage'])) ? $summary['squareFootage'] : '',
			    "SqFeetInteriorDisplay" => (!empty($summary['squareFootage'])) ? $summary['squareFootage'] : '',
			    "CommentsLong" => !empty($listing['remarks']) ? $this->get_remarks_by_lang( $listing['remarks'], 'es' ) : '',
			    "OpenHouse1Date" => '',
			    "OpenHouse1Time" => '',
			    "OpenHouse2Date" => '',
			    "OpenHouse2Time" => '',
			    "SchoolDistrictId" => '',
			    "SchoolElementary" => '',
			    "SchoolMiddle" => '',
			    "SchoolHigh" => '',
			    'Photo1' => !empty($summary['defaultPhotoURL']) ? 'https:' . $summary['defaultPhotoURL'] : '',
			    'Photos' => (!empty($listing['media'])) ? $this->get_listing_media( 'all', 'Listing Photo', $listing['media'] ) : '',
			    "Floorplan1" => (!empty($listing['media'])) ? $this->get_listing_media( 0, 'Floor Plan', $listing['media'] ) : '',
			    "Floorplan2" => (!empty($listing['media'])) ? $this->get_listing_media( 1, 'Floor Plan', $listing['media'] ) : '',
			    "Floorplan3" => (!empty($listing['media'])) ? $this->get_listing_media( 2, 'Floor Plan', $listing['media'] ) : '',
			    "Floorplan4" => (!empty($listing['media'])) ? $this->get_listing_media( 3, 'Floor Plan', $listing['media'] ) : '',
			    "Floorplan5" => (!empty($listing['media'])) ? $this->get_listing_media( 4, 'Floor Plan', $listing['media'] ) : '',
			    "VirtualTour" => (!empty($listing['media'])) ? $this->get_listing_media( 0, 'Virtual Tour', $listing['media'] ) : '',
			    "VideoTour" => (!empty($listing['media'])) ? $this->get_listing_media( 0, 'Video Walk Through', $listing['media'] ) : '',
			    "AdvertiserHomepageURL" => 'http://www.sircostarica.com',
			    "AdvertiserListingURL" => (!empty($summary['listingURL'])) ? $summary['listingURL'] : '',
			    "AdvertiserLogo" => '',
			    "AdvertiserName" => "Costa Rica Sotheby's International Realty",
			    "Agent1Id" => !empty($summary['agents'][0]['RFGStaffId']) ? $summary['agents'][0]['RFGStaffId'] : '',
			    "Agent1_id" => !empty($summary['agents'][0]['id']) ? $summary['agents'][0]['id'] : '',
			    "Agent1Name" => !empty($summary['agents'][0]['name']) ? $summary['agents'][0]['name'] : '',
			    "Agent1Email" => !empty($summary['agents'][0]['emailAddress']) ? $summary['agents'][0]['emailAddress'] : '',
			    "Agent1PhonePrimary" => !empty($summary['agents'][0]['businessPhone']) ? $summary['agents'][0]['businessPhone'] : '',
			    "Agent1Photo" => !empty($summary['agents'][0]['defaultPhotoURL']) ? 'https:' . $summary['agents'][0]['defaultPhotoURL'] : '',
			    "Agent1Logo" => '',
			    "Agent2Id" => !empty($summary['agents'][1]['RFGStaffId']) ? $summary['agents'][1]['RFGStaffId'] : '',
			    "Agent2_id" => !empty($summary['agents'][1]['id']) ? $summary['agents'][1]['id'] : '',
			    "Agent2Name" => !empty($summary['agents'][1]['name']) ? $summary['agents'][1]['name'] : '',
			    "Agent2Email" => !empty($summary['agents'][1]['emailAddress']) ? $summary['agents'][1]['emailAddress'] : '',
			    "Agent2PhonePrimary" => !empty($summary['agents'][1]['businessPhone']) ? $summary['agents'][1]['businessPhone'] : '',
			    "Agent2Photo" => !empty($summary['agents'][1]['defaultPhotoURL']) ? 'https:' . $summary['agents'][1]['defaultPhotoURL'] : '',
			    "Agent2Logo" => '',
			    "CodeListingType" => $summary['listingType'] == 'ForSale' ? 'S' : 'R',
			    "Amenities" => !empty($listing['propertyFeatures']) ? $this->get_amenities( $listing['propertyFeatures'] ) : '',
			    "LifeStyles" => !empty($listing['propertyFeatures']) ? $this->get_life_styles( $listing['propertyFeatures'] ) : ''
			],
			'en' => [
				"IdWeb" => (!empty($summary['RFGListingId'])) ? $summary['RFGListingId'] : '',
			    "CommentsLong" => !empty($listing['remarks']) ? $this->get_remarks_by_lang( $listing['remarks'], 'en' ) : '',
			    "LifeStyles" => !empty($listing['propertyFeatures']) ? $this->get_life_styles( $listing['propertyFeatures'] ) : ''
			]
		];
	}

	public function update_lifestyles_es( $postid, $lifestyles ) {
		$lifestyles_es = [];
		foreach($lifestyles as $lf) {
			$lf_obj = get_term_by( 'name', $lf, 'lifestyle' );
			if($lf_obj && !in_array($lf_obj->term_id, $lifestyles_es))
				$lifestyles_es[] = $lf_obj->term_id;
		}

		wp_set_object_terms( $postid, $lifestyles_es, 'lifestyle', false );
	}

	public function update_lifestyles_en( $postid, $lifestyles ) {
		global $sitepress;

		$lifestyles_en = [];
		foreach($lifestyles as $lf) {
			$lf_obj = get_term_by( 'name', $lf, 'lifestyle' );
			if($lf_obj) {
				$lf_en = apply_filters( 'wpml_object_id', $lf_obj->term_id, 'lifestyle', FALSE, 'en' );
				if($lf_en) {
					if(!in_array($lf_en, $lifestyles_en))
						$lifestyles_en[] = $lf_en;
				} else {
					if(!in_array($lf_obj->term_id, $lifestyles_en))
						$lifestyles_en[] = $lf_obj->term_id;
				}
			}
		}

		wp_set_object_terms( $postid, $lifestyles_en, 'lifestyle', false );
	}

	public function save_post_es( $property_es ) {
		$property_es = array_change_key_case($property_es);
		// Build the new post data
		$postarr = array(
			'post_status' => 'publish',
			'post_type' => 'property',
			'post_title' => $property_es['addrdisplay'],
			'meta_input' => $property_es
		);
		// Do we need to update or create a new listing?
		$existing = $this->get_post( $property_es['idweb'], 'es' );

		if( $existing ) 
			$postarr[ 'ID' ] = $existing[0]->ID;

		// set a language flag we can query later 
		$postarr[ 'meta_input' ][ 'lang' ] = 'es';
		// Save the new post
		$postid = wp_insert_post( $postarr, true );

		$this->update_lifestyles_es( $postid, $property_es['lifestyles'] );

		return $postid;
	}

	public function save_post_en( $property_en ) {// Build the new post data
		$property_en = array_change_key_case($property_en);
		$postarr = array(
			'post_status' => 'publish',
			'post_type' => 'property',
			'meta_input' => $property_en
		);
		// Find the spanish sibling... This will be a translation of.
		$existing_es = $this->get_post( $property_en['idweb'], 'es' );
		if(!$existing_es)
			return;

		// Do we need to update or create?
		$existing_en = $this->get_post( $property_en['idweb'], 'en' );

		if( $existing_en ) 
			$postarr[ 'ID' ] = $existing_en[0]->ID;

		// The language flag
		$postarr[ 'meta_input' ][ 'lang' ] = 'en';
		$postarr[ 'meta_input' ] = $this->merge_meta( $existing_es[0]->ID, $postarr[ 'meta_input' ] );
		
		$postarr[ 'post_title' ] = $postarr[ 'meta_input' ][ 'addrdisplay' ];
		// Save
		$postid = wp_insert_post( $postarr, true );

		$this->update_lifestyles_en( $postid, $property_en['lifestyles'] );

		return $postid;
	}

}