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
		define( 'FIDABR_PLUGIN_VERSION', '1.0.0' );
		define( 'FIDABR_PLUGIN_PLUGIN_FILE', __FILE__ );
		define( 'FIDABR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		define( 'FIDABR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		define( 'FIDABR_PLUGIN_ASSETS_URL', FIDABR_PLUGIN_URL . '/assets' );
		define( 'FIDABR_PLUGIN_ROUTE_PREFIX', 'fidabr/v1' );
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

		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'init', array( $this, 'init_llm_hooks' ) );
		add_action( 'fidabr_auto_generate_llms', array( $this, 'auto_generate_llms' ) );
	}

	public function register_blocks() {
		// register_block_type( __DIR__ . '/assets/blocks/block-1' );
	}


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

		$settings = get_option( 'fidabr_llm_settings', array() );
		
		// Check if auto-update is enabled and this post type is included
		if ( ! empty( $settings['auto_update'] ) && in_array( $post->post_type, (array) ( $settings['post_types'] ?? array() ), true ) ) {
			// Schedule generation for next cron run to avoid slowing down post saves
			wp_schedule_single_event( time() + 60, 'fidabr_auto_generate_llms' );
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

		$settings = get_option( 'fidabr_llm_settings', array() );
		
		// Check if auto-update is enabled and this post type is included
		if ( ! empty( $settings['auto_update'] ) && in_array( $post->post_type, (array) ( $settings['post_types'] ?? array() ), true ) ) {
			// Schedule generation for next cron run
			wp_schedule_single_event( time() + 60, 'fidabr_auto_generate_llms' );
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
		$settings = get_option( 'fidabr_llm_settings', array() );
		
		if ( ! empty( $settings['auto_update'] ) ) {
			$generator->generate_llms_file( $settings );
		}
	}

	/**
	 * Internationalization setup for language translations.
	 *
	 * Note: Since WordPress 4.6, load_plugin_textdomain() is no longer needed
	 * for plugins hosted on WordPress.org. WordPress automatically loads
	 * translations when the text domain matches the plugin slug.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function i18n() {
		// WordPress automatically loads translations since 4.6 for WordPress.org hosted plugins
		// when text domain matches plugin folder name
	}
}
