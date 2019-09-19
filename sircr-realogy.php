<?php
/**
 * Plugin Name:     SIR Realogy API
 * Plugin URI:      http://www.sircostarica.com
 * Description:     Data Syncronization between WordPress and the Realogy Consumer API
 * Author:          Cristian Araya J.
 * Author URI:      https://codeskill.io
 * Text Domain:     sircr-realogy
 * Domain Path:     /languages
 * Version:         0.1.0
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define( 'SIRREAL_VERSION', '0.1.0' );
define( 'SIRREAL__FILE__', __FILE__ );
define( 'SIRREAL_PATH', plugin_dir_path(SIRREAL__FILE__) );
define( 'SIRREAL_URL', plugin_dir_url(SIRREAL__FILE__) );

class SIRCR_Realogy {

	protected static $_instance;

	protected $_token;

	public static function get_instance() {
		if(null === self::$_instance)
			self::$_instance = new self();

		return self::$_instance;
	}

	public function __construct() {
		$this->_includes();
	}

	protected function _includes() {
		// Carbon fields
		require_once SIRREAL_PATH . '/inc/Api.php';
		require_once SIRREAL_PATH . '/inc/Manager.php';
		require_once SIRREAL_PATH . '/inc/Settings.php';
	}

	protected function _init() {
		$this->settings = SIRCR_Realogy_Settings::get_instance();
		$this->api = SIRCR_Realogy_Api::get_instance();
		$this->manager = SIRCR_Realogy_Manager::get_instance();
	}

	public function hooks() {
		$this->_init();
		
		add_action( 'admin_menu', [ $this, 'menu' ]);
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );

		add_action( 'wp_ajax_sircr_realogy_get_properties', [ $this, 'ajax_get_properties' ] );
		add_action( 'wp_ajax_sircr_realogy_save_property', [ $this, 'ajax_save_property' ] );
		add_action( 'wp_ajax_sircr_realogy_update_property', [ $this, 'ajax_update_property' ] );
		add_action( 'wp_ajax_sircr_realogy_get_agents', [ $this, 'ajax_get_agents' ] );
		add_action( 'wp_ajax_sircr_realogy_save_agent', [ $this, 'ajax_save_agent' ] );
		add_action( 'wp_ajax_sircr_realogy_update_agent', [ $this, 'ajax_update_agent' ] );
		add_action( 'wp_ajax_sircr_realogy_disable_old_properties', [ $this, 'ajax_disable_old_properties' ] );
		add_action( 'wp_ajax_sircr_realogy_disable_old_agents', [ $this, 'ajax_disable_old_agents' ] );
	}

	public function menu() {
		add_menu_page( 'Realogy API', 'Realogy API', 'manage_options', 'sircr_realogy_api', [ $this, 'page_realogy_api' ], '', 81 );
		add_submenu_page( 'sircr_realogy_api', 'Re-fetch All', 'Re-fetch All', 'manage_options', 'sircr_realogy_api_fetch', [ $this, 'page_realogy_api_fetch' ] );
		add_submenu_page( 'sircr_realogy_api', 'Update Data', 'Update Data', 'manage_options', 'sircr_realogy_api_update', [ $this, 'page_realogy_api_update' ] );
		add_submenu_page( 'sircr_realogy_api', 'Amenities', 'Amenities', 'manage_options', 'sircr_realogy_amenities', [ $this, 'page_realogy_amenities' ] );
	}

	public function assets( $hook ) {
		switch( $hook ) {
			case 'realogy-api_page_sircr_realogy_api_fetch':
				wp_enqueue_script( 'sircr-realogy-js', SIRREAL_URL . '/assets/js/refetch.js', ['jquery'], SIRREAL_VERSION, true );
				wp_enqueue_style( 'sircr-realogy-css', SIRREAL_URL . '/assets/css/style.css', [], SIRREAL_VERSION );
			break;
			case 'realogy-api_page_sircr_realogy_api_update':
				wp_enqueue_script( 'sircr-realogy-js', SIRREAL_URL . '/assets/js/update.js', ['jquery'], SIRREAL_VERSION, true );
				wp_enqueue_style( 'sircr-realogy-css', SIRREAL_URL . '/assets/css/style.css', [], SIRREAL_VERSION );
			break;
		}
		wp_localize_script( 'sircr-realogy-js', 'sircr_realogy', [ 'ajax_url' => admin_url( 'admin-ajax.php' ) ] );
	}

	public function page_realogy_api() {
		$token = $this->api->get_token( true );
		$listings = $this->api->get_listings();
		$listing = $this->api->get_listing( $listings[0]['entityId'] );
		$agents = $this->api->get_agents();
		$agent = $this->api->get_agent( $agents[0]['entityId'] );
		?>
		<div class="wrap">
			<h2>Realogy API Settings</h2>
			<p>Last Updated: <?php echo date('F j, Y - H:i:s', get_option( 'sircr_realogy_last_update' )) ?></p>
			<p>
				<a class="button" href="<?php echo admin_url( 'admin.php?page=sircr_realogy_api_fetch' ) ?>">Re-Fecth All Data</a>
				<a class="button" href="<?php echo admin_url( 'admin.php?page=sircr_realogy_api_update' ) ?>">Update Data</a>
				<a class="button" href="<?php echo admin_url( 'admin.php?page=crb_carbon_fields_container_api_settings.php' ) ?>">API Settings</a>
			</p>
			<h3>API Status</h3>
			<p><strong>Enviroment:</strong> <?php echo $this->settings->is_staging() ? 'Staging' : 'Production'; ?></p>
			<ul style="padding-left: 30px; list-style: disc;">
				<li>Token URL: <?php echo $this->settings->get( 'endpoint' ) ?></li>
				<li>Client ID: <?php echo $this->settings->get( 'client_id' ) ?></li>
				<li>Client Secret: <?php echo $this->settings->get( 'client_secret' ) ?></li>
				<li>Subcription Key: <?php echo $this->settings->get( 'subscription_key' ) ?></li>
				<li>Scope: <?php echo $this->settings->get( 'scope' ) ?></li>
				<li>URL: <?php echo $this->settings->get( 'url' ) ?></li>
			</ul>
			<p>Can Generate token: <?php echo ($token) ? '<span style="color:green">Working</span>' : '<span style="color:red">Not Working</span>'; ?></p>
			<p>Can Get Listings: <?php echo empty($listings['statusCode']) ? '<span style="color:green">Working</span>' : '<span style="color:red">Not Working: '.$listings['message'].'</span>'; ?></p>
			<p>Can Get Listing Detail: <?php echo !empty($listing['listingSummary']) ? '<span style="color:green">Working</span>' : '<span style="color:red">Not Working: '.$listing['message'].'</span>'; ?></p>
			<p>Can Get Agents: <?php echo empty($agents['statusCode']) ? '<span style="color:green">Working</span>' : '<span style="color:red">Not Working: '.$agents['message'].'</span>'; ?></p>
			<p>Can Get Agent Detail: <?php echo !empty($agent['agentSummary']) ? '<span style="color:green">Working</span>' : '<span style="color:red">Not Working: '.$agent['message'].'</span>'; ?></p>
			<h3>API Results: Get Listings</h3>
			<pre style="overflow:scroll; height:100px; background:#fff; width:1000px">
<?php var_dump($listings[0]) ?>
			</pre>
			<h3>API Results: Listing Detail</h3>
			<pre style="overflow:scroll; height:400px; background:#fff; width:1000px">
<?php var_dump($listing['listingSummary']) ?>
			</pre>
			<h3>API Results: Get Agents</h3>
			<pre style="overflow:scroll; height:100px; background:#fff; width:1000px">
<?php var_dump($agents[0]) ?>
			</pre>
			<h3>API Results: Agents Detail</h3>
			<pre style="overflow:scroll; height:400px; background:#fff; width:1000px">
<?php var_dump($agent['agentSummary']) ?>
			</pre>
		</div>
		<?php
	}

	public function page_realogy_api_fetch() {
		?>
		<div class="wrap">
			<h2>Re-Fetch All properties and agents data</h2>
			<div class="sircr-realogy-progress-bar js-progress-bar">
				<div class="sircr-realogy-progress js-progress"></div>
			</div>
			<div class="sircr-realogy-log js-log"></div>
			<p><a href="#" class="button button-primary js-refetch">Start</a></p>
		</div>
		<?php
	}

	public function page_realogy_api_update() {
		?>
		<div class="wrap">
			<h2>Update data</h2>
			<div class="sircr-realogy-progress-bar js-progress-bar">
				<div class="sircr-realogy-progress js-progress"></div>
			</div>
			<div class="sircr-realogy-log js-log"></div>
			<p><a href="#" class="button button-primary js-update">Update Now</a></p>
		</div>
		<?php
	}

	public function page_realogy_amenities() {
		if(isset($_GET['refetch'])) {
			$this->manager->refetch_amenities();
			header('Location: '.admin_url('admin.php?page=sircr_realogy_amenities'));
		}

		if(!empty($_POST)) {
			$this->manager->save_amenities_translations($_POST['translations']);
			header('Location: '.admin_url('admin.php?page=sircr_realogy_amenities'));
		}

		$amenities = $this->manager->get_amenities();
		$translations = $this->manager->get_amenities_translations();
		?>
		<div class="wrap">
			<h2>Manage Amanities &nbsp; <a class="button" href="<?php echo admin_url('admin.php?page=sircr_realogy_amenities&refetch') ?>">Fetch Amenities</a></h2>

			<?php if(count($amenities) > 0): ?>
			<form action="" method="post">
				<table class="wp-list-table widefat striped">
					<thead>
						<tr>
							<th>Active</th>
							<th>Amenity</th>
							<th>English</th>
							<th>Spanish</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($amenities as $k => $am): ?>
						<tr>
							<td><input type="checkbox" name="translations[<?php echo $am ?>][active]" value="1" <?php echo ($translations[$am]['active']) ? 'checked' : '' ?> /></td>
							<td><?php echo $am ?></td>
							<td><input type="text" class="regular-text" name="translations[<?php echo $am ?>][en]" value="<?php echo trim($translations[$am]['en']) ?>" /></td>
							<td><input type="text" class="regular-text" name="translations[<?php echo $am ?>][es]" value="<?php echo trim($translations[$am]['es']) ?>" /></td>
						</tr>
						<?php endforeach ?>
					</tbody>
				</table>
				<p><button class="button button-primary">Save Changes</button></p>
			</form>
			<?php else: ?>
			<p>No amenities found</p>
			<?php endif; ?>
		</div>
		<?php
	}

	public function ajax_get_properties() {
		header('Content-Type: application/json');
		$listings = $this->api->get_listings();
		echo json_encode( $listings );
		wp_die();
	}

	public function ajax_save_property() {
		header('Content-Type: application/json');
		$result = $this->manager->save_property( $_GET['id'] );
		echo json_encode( $result );
		wp_die();
	}

	public function ajax_update_property() {
		header('Content-Type: application/json');
		$result = $this->manager->update_property( $_GET['id'], $_GET['updatedOn'] );
		echo json_encode( $result );
		wp_die();
	}

	public function ajax_get_agents() {
		header('Content-Type: application/json');
		$agents = $this->api->get_agents();
		echo json_encode( $agents );
		wp_die();
	}

	public function ajax_save_agent() {
		header('Content-Type: application/json');
		$result = $this->manager->save_agent( $_GET['id'] );
		echo json_encode( $result );
		wp_die();
	}

	public function ajax_update_agent() {
		header('Content-Type: application/json');
		$result = $this->manager->update_agent( $_GET['id'], $_GET['updatedOn'] );
		echo json_encode( $result );
		wp_die();
	}

	public function ajax_disable_old_properties() {
		header('Content-Type: application/json');
		$this->manager->disable_old_posts( $_POST['properties'], 'property' );
		echo json_encode( ['success' => 1] );
		wp_die();
	}

	public function ajax_disable_old_agents() {
		header('Content-Type: application/json');
		$this->manager->disable_old_posts( $_POST['agents'], 'agent' );
		echo json_encode( ['success' => 1] );
		wp_die();
	}

}

function sircr_realogy_get_instance() {
	return SIRCR_Realogy::get_instance();
}

add_action( 'plugins_loaded', [ sircr_realogy_get_instance(), 'hooks' ] );
