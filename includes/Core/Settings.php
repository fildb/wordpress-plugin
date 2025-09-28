<?php
/**
 * Settings - Core settings management
 *
 * @package FiloDataBrokerPlugin
 */

namespace FiloDataBrokerPlugin\Core;

use FiloDataBrokerPlugin\Core\ContentCrawler;

defined( 'ABSPATH' ) || exit;

/**
 * Settings class for managing plugin settings
 */
class Settings {

	/**
	 * Content Crawler instance
	 *
	 * @var ContentCrawler
	 */
	private $content_crawler;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->content_crawler = new ContentCrawler();
	}

	/**
	 * Get default settings
	 *
	 * @return array Default settings
	 */
	public function get_default_settings() {
		return array(
			'auto_update'         => true,
			'post_types'          => array( 'post', 'page' ),
			'include_excerpts'    => true,
			'include_meta'        => false,
			'include_taxonomies'  => false,
			'max_posts_per_type'  => 50,
		);
	}

	/**
	 * Get current settings
	 *
	 * @return array Current settings
	 */
	public function get_settings() {
		$settings = get_option( 'fdb_llm_settings', array() );
		return wp_parse_args( $settings, $this->get_default_settings() );
	}

	/**
	 * Save settings
	 *
	 * @param array $settings Settings to save
	 * @return bool True on success
	 */
	public function save_settings( $settings ) {
		error_log( '[FDB Settings] Raw settings received: ' . wp_json_encode( $settings ) );

		// Sanitize settings
		$sanitized_settings = array(
			'auto_update'         => ! empty( $settings['auto_update'] ),
			'post_types'          => array_map( 'sanitize_key', (array) ( isset( $settings['post_types'] ) ? $settings['post_types'] : array() ) ),
			'include_excerpts'    => ! empty( $settings['include_excerpts'] ),
			'include_meta'        => ! empty( $settings['include_meta'] ),
			'include_taxonomies'  => ! empty( $settings['include_taxonomies'] ),
			'max_posts_per_type'  => absint( isset( $settings['max_posts_per_type'] ) ? $settings['max_posts_per_type'] : 50 ),
		);

		// Ensure max_posts_per_type is within reasonable limits
		$sanitized_settings['max_posts_per_type'] = max( 1, min( 1000, $sanitized_settings['max_posts_per_type'] ) );

		error_log( '[FDB Settings] Sanitized settings: ' . wp_json_encode( $sanitized_settings ) );

		// WordPress update_option returns false if the value is unchanged
		// So we need to check if the settings actually changed or if there was an error
		$current_settings = get_option( 'fdb_llm_settings', array() );
		$current_settings = wp_parse_args( $current_settings, $this->get_default_settings() );

		error_log( '[FDB Settings] Current settings: ' . wp_json_encode( $current_settings ) );

		// If settings are identical, consider it a success
		if ( $this->settings_are_equal( $current_settings, $sanitized_settings ) ) {
			error_log( '[FDB Settings] Settings are identical, returning true' );
			return true;
		}

		// Try to update the option
		error_log( '[FDB Settings] Attempting to update option' );
		$result = update_option( 'fdb_llm_settings', $sanitized_settings );
		error_log( '[FDB Settings] update_option result: ' . ( $result ? 'true' : 'false' ) );

		// Double-check that the settings were actually saved if update_option returned false
		if ( ! $result ) {
			error_log( '[FDB Settings] update_option returned false, double-checking...' );
			$saved_settings = get_option( 'fdb_llm_settings', array() );
			$saved_settings = wp_parse_args( $saved_settings, $this->get_default_settings() );

			error_log( '[FDB Settings] Settings after attempted save: ' . wp_json_encode( $saved_settings ) );

			// If the settings match what we tried to save, it worked despite returning false
			$final_result = $this->settings_are_equal( $saved_settings, $sanitized_settings );
			error_log( '[FDB Settings] Final comparison result: ' . ( $final_result ? 'true' : 'false' ) );
			return $final_result;
		}

		error_log( '[FDB Settings] Settings saved successfully' );
		return $result;
	}

	/**
	 * Compare two settings arrays for equality
	 *
	 * @param array $settings1 First settings array
	 * @param array $settings2 Second settings array
	 * @return bool True if settings are equal
	 */
	private function settings_are_equal( $settings1, $settings2 ) {
		// Normalize both arrays to have the same keys in the same order
		$keys = array_keys( $this->get_default_settings() );

		$normalized1 = array();
		$normalized2 = array();

		foreach ( $keys as $key ) {
			$normalized1[ $key ] = isset( $settings1[ $key ] ) ? $settings1[ $key ] : null;
			$normalized2[ $key ] = isset( $settings2[ $key ] ) ? $settings2[ $key ] : null;
		}

		// Use wp_json_encode for consistent comparison (handles arrays properly)
		return wp_json_encode( $normalized1 ) === wp_json_encode( $normalized2 );
	}

	/**
	 * Get available post types with counts
	 *
	 * @return array Post types with metadata
	 */
	public function get_post_types_with_counts() {
		$post_types = $this->content_crawler->get_available_post_types();
		$result     = array();

		foreach ( $post_types as $post_type => $post_type_obj ) {
			$count = wp_count_posts( $post_type );
			$result[] = array(
				'name'  => $post_type,
				'label' => $post_type_obj->labels->name,
				'count' => $count->publish ?? 0,
			);
		}

		return $result;
	}

}