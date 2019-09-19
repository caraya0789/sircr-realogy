<?php

require_once realpath( __DIR__ . '/../../../wp-load.php');

function _log( $message ) {
	echo $message . "\n";
}

$api = SIRCR_Realogy_Api::get_instance();
$manager = SIRCR_Realogy_Manager::get_instance();

_log( 'Getting Properties' );

$listings = $api->get_listings();
$totalProperties = count($listings);

_log( 'Got ' . $totalProperties . ' Properties Back' );

if($totalProperties > 0) {

	$savedProperties = [];

	foreach($listings as $k => $listing) {
		$result = $manager->update_property( $listing['entityId'], $listing['lastUpdateOn'] );

		if( !empty($result['status']) ) {

			if('updated' === $result['status'])
				_log( ($k+1) . '/' . $totalProperties . ' Property Updated: ' . $result['name'] );
			else if('unchanged' === $result['status'])
				_log( ($k+1) . '/' . $totalProperties . ' Property Un-changed: ' . $result['name'] );
			else if('added' === $result['status'])
				_log( ($k+1) . '/' . $totalProperties . ' Property Created: ' . $result['name'] );

			if(!empty($result['es']))
				$savedProperties[] = $result['es'];
			if(!empty($result['en']))
				$savedProperties[] = $result['en'];

		} else {
			_log( ($k+1) . '/' . $totalProperties . ' Property Not Found: ' . $listing['entityId'] );
		}
	}

	_log('Disabling old properties');
	$manager->disable_old_posts( $savedProperties, 'property' );
}

_log( 'Getting Agents' );

$agents = $api->get_agents();
$totalAgents = count($agents);

_log( 'Got ' . $totalAgents . ' Agents Back' );

if($totalAgents > 0) {

	$savedAgents = [];

	foreach($agents as $k => $agent) {
		$result = $manager->update_agent( $agent['entityId'], $agent['lastUpdateOn'] );

		if( !empty($result['status']) ) {

			if('updated' === $result['status'])
				_log( ($k+1) . '/' . $totalAgents . ' Agent Updated: ' . $result['name'] );
			else if('unchanged' === $result['status'])
				_log( ($k+1) . '/' . $totalAgents . ' Agent Un-changed: ' . $result['name'] );
			else if('added' === $result['status'])
				_log( ($k+1) . '/' . $totalAgents . ' Agent Created: ' . $result['name'] );

			if(!empty($result['es']))
				$savedAgents[] = $result['es'];
			if(!empty($result['en']))
				$savedAgents[] = $result['en'];

		} else {
			_log( ($k+1) . '/' . $totalAgents . ' Agent Not Found: ' . $agent['entityId'] );
		}
	}

	_log('Disabling old agents');
	$manager->disable_old_posts( $savedAgents, 'agent' );
	
}

_log('All Done!');
