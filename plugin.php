<?php
use FiloDataBrokerPlugin\Core\Api;
use FiloDataBrokerPlugin\Admin\Menu;
use FiloDataBrokerPlugin\Core\Template;
use FiloDataBrokerPlugin\Assets\Frontend;
use FiloDataBrokerPlugin\Assets\Admin;
use FiloDataBrokerPlugin\Traits\Base;
use FiloDataBrokerPlugin\Core\Generator;

defined( 'ABSPATH' ) || exit;

/**
 * Class FiloDataBrokerPlugin
 *
 * The main class for the Coldmailar plugin, responsible for initialization and setup.
 *
 * @since 1.0.0
 */
final class FiloDataBrokerPlugin {

	use Base;

	/**
	 * Class constructor to set up constants for the plugin.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct() {
		define( 'FDBPLUGIN_VERSION', '1.0.0' );
		define( 'FDBPLUGIN_PLUGIN_FILE', __FILE__ );
		define( 'FDBPLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'FDBPLUGIN_URL', plugin_dir_url( __FILE__ ) );
		define( 'FDBPLUGIN_ASSETS_URL', FDBPLUGIN_URL . '/assets' );
		define( 'FDBPLUGIN_ROUTE_PREFIX', 'wordpress-plugin-boilerplate/v1' );
	}

	/**
	 * Main execution point where the plugin will fire up.
	 *
	 * Initializes necessary components for both admin and frontend.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init() {
		if ( is_admin() ) {
			Menu::get_instance()->init();
			Admin::get_instance()->bootstrap();
		}

		// Initialze core functionalities.
		Frontend::get_instance()->bootstrap();
		API::get_instance()->init();
		Template::get_instance()->init();

		add_action( 'init', array( $this, 'i18n' ) );
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'init', array( $this, 'init_llm_hooks' ) );
		add_action( 'fdb_auto_generate_llms', array( $this, 'auto_generate_llms' ) );
	}

	public function register_blocks() {
		register_block_type( __DIR__ . '/assets/blocks/block-1' );
	}


	/**
	 * Internationalization setup for language translations.
	 *
	 * Loads the plugin text domain for localization.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	/**
	 * Initialize LLM generation hooks
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init_llm_hooks() {
		$generator = new Generator();
		
		// Auto-generate on post save if enabled
		add_action( 'save_post', array( $this, 'maybe_auto_generate' ), 10, 2 );
		add_action( 'delete_post', array( $this, 'maybe_auto_generate_on_delete' ) );
	}

	/**
	 * Maybe auto-generate llms.txt on post save
	 *
	 * @param int $post_id Post ID
	 * @param \WP_Post $post Post object
	 * @return void
	 */
	public function maybe_auto_generate( $post_id, $post ) {
		// Skip for autosaves, revisions, and drafts
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || $post->post_status !== 'publish' ) {
			return;
		}

		$settings = get_option( 'fdb_llm_settings', array() );
		
		// Check if auto-update is enabled and this post type is included
		if ( ! empty( $settings['auto_update'] ) && in_array( $post->post_type, (array) ( $settings['post_types'] ?? array() ), true ) ) {
			// Schedule generation for next cron run to avoid slowing down post saves
			wp_schedule_single_event( time() + 60, 'fdb_auto_generate_llms' );
		}
	}

	/**
	 * Maybe auto-generate llms.txt on post deletion
	 *
	 * @param int $post_id Post ID
	 * @return void
	 */
	public function maybe_auto_generate_on_delete( $post_id ) {
		$post = get_post( $post_id );
		
		if ( ! $post ) {
			return;
		}

		$settings = get_option( 'fdb_llm_settings', array() );
		
		// Check if auto-update is enabled and this post type is included
		if ( ! empty( $settings['auto_update'] ) && in_array( $post->post_type, (array) ( $settings['post_types'] ?? array() ), true ) ) {
			// Schedule generation for next cron run
			wp_schedule_single_event( time() + 60, 'fdb_auto_generate_llms' );
		}
	}

	/**
	 * Auto-generate LLM file via cron
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function auto_generate_llms() {
		$generator = new Generator();
		$settings = get_option( 'fdb_llm_settings', array() );
		
		if ( ! empty( $settings['auto_update'] ) ) {
			$generator->generate_llms_file( $settings );
		}
	}

	/**
	 * Internationalization setup for language translations.
	 *
	 * Loads the plugin text domain for localization.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function i18n() {
		load_plugin_textdomain( 'fdb-wp-plugin', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}
