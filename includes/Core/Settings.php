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
		// Sanitize settings
		$sanitized_settings = array(
			'auto_update'         => ! empty( $settings['auto_update'] ),
			'post_types'          => array_map( 'sanitize_key', (array) ( $settings['post_types'] ?? array() ) ),
			'include_excerpts'    => ! empty( $settings['include_excerpts'] ),
			'include_meta'        => ! empty( $settings['include_meta'] ),
			'include_taxonomies'  => ! empty( $settings['include_taxonomies'] ),
			'max_posts_per_type'  => absint( $settings['max_posts_per_type'] ?? 50 ),
		);

		// Ensure max_posts_per_type is within reasonable limits
		$sanitized_settings['max_posts_per_type'] = max( 1, min( 1000, $sanitized_settings['max_posts_per_type'] ) );

		return update_option( 'fdb_llm_settings', $sanitized_settings );
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