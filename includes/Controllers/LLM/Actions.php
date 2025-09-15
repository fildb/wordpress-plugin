<?php
/**
 * LLM Controller - AJAX handlers for LLM generation
 *
 * @package FiloDataBrokerPlugin
 */

namespace FiloDataBrokerPlugin\Controllers\LLM;

use FiloDataBrokerPlugin\Core\Settings;
use FiloDataBrokerPlugin\Core\Generator;

defined( 'ABSPATH' ) || exit;

/**
 * Actions class for handling LLM-related AJAX requests
 */
class Actions {

	/**
	 * Settings instance
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Generator instance
	 *
	 * @var Generator
	 */
	private $generator;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->settings = new Settings();
		$this->generator = new Generator();
	}


	/**
	 * Get current settings
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response|\WP_Error REST response
	 */
	public function get_settings( $request = null ) {
		$settings = $this->settings->get_settings();
		return rest_ensure_response( $settings );
	}

	/**
	 * Save settings
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response|\WP_Error REST response
	 */
	public function save_settings( $request ) {
		$settings = $request->get_json_params();

		if ( ! is_array( $settings ) ) {
			return new \WP_Error( 'invalid_data', 'Invalid settings data', array( 'status' => 400 ) );
		}

		$result = $this->settings->save_settings( $settings );

		if ( $result ) {
			return rest_ensure_response( array( 'message' => 'Settings saved successfully' ) );
		} else {
			return new \WP_Error( 'save_failed', 'Failed to save settings', array( 'status' => 500 ) );
		}
	}

	/**
	 * Get available post types
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response|\WP_Error REST response
	 */
	public function get_post_types( $request = null ) {
		$post_types = $this->settings->get_post_types_with_counts();
		return rest_ensure_response( $post_types );
	}

	/**
	 * Get generation status
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response|\WP_Error REST response
	 */
	public function get_status( $request = null ) {
		$status = $this->generator->get_generation_status();
		return rest_ensure_response( $status );
	}

	/**
	 * Generate LLM file
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response|\WP_Error REST response
	 */
	public function generate_file( $request = null ) {
		// Increase time limit for generation
		set_time_limit( 300 );

		$settings = $this->settings->get_settings();
		$result = $this->generator->generate_llms_file( $settings );

		if ( is_wp_error( $result ) ) {
			return $result;
		} else {
			return rest_ensure_response( array( 'message' => 'LLM file generated successfully' ) );
		}
	}
}