<?php
/**
 * LLM Controller - AJAX handlers for LLM generation
 *
 * @package FiloDataBrokerPlugin
 */

namespace FiloDataBrokerPlugin\Controllers\LLM;

use FiloDataBrokerPlugin\Core\Settings;
use FiloDataBrokerPlugin\Core\Generator;
use FiloDataBrokerPlugin\Core\ProgressManager;

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
	 * Get statistics for dashboard
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response|\WP_Error REST response
	 */
	public function get_statistics( $request = null ) {
		global $wpdb;

		$settings = $this->settings->get_settings();
		$post_types = $settings['post_types'] ?? array( 'post', 'page' );

		// Get total post/page counts
		$total_posts = wp_count_posts( 'post' )->publish ?? 0;
		$total_pages = wp_count_posts( 'page' )->publish ?? 0;

		// Get posts/pages that have CDN URLs (included in LLM)
		$posts_with_cdn = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID)
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = %s
				AND p.post_status = 'publish'
				AND pm.meta_key = '_fidabr_cdn_url'
				AND pm.meta_value != ''",
				'post'
			)
		);

		$pages_with_cdn = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT p.ID)
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				WHERE p.post_type = %s
				AND p.post_status = 'publish'
				AND pm.meta_key = '_fidabr_cdn_url'
				AND pm.meta_value != ''",
				'page'
			)
		);

		// Calculate total CDN storage used
		$total_cdn_storage = $wpdb->get_var(
			"SELECT SUM(CAST(pm.meta_value AS UNSIGNED))
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE pm.meta_key = '_fidabr_cdn_size'
			AND p.post_status = 'publish'
			AND CAST(pm.meta_value AS UNSIGNED) > 0"
		);

		$statistics = array(
			'posts_in_llm'        => (int) $posts_with_cdn,
			'total_posts'         => (int) $total_posts,
			'pages_in_llm'        => (int) $pages_with_cdn,
			'total_pages'         => (int) $total_pages,
			'cdn_storage_used'    => (int) ( $total_cdn_storage ?? 0 ),
			'cdn_storage_total'   => 1073741824, // 1GB in bytes
		);

		return rest_ensure_response( $statistics );
	}

	/**
	 * Generate LLM file
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response|\WP_Error REST response
	 */
	/**
	 * Generate LLM file with queue processing
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response|\WP_Error REST response
	 */
	public function generate_file( $request = null ) {
		$start = $request ? sanitize_text_field( $request->get_param( 'start' ) ) : null;
		$is_start = ! empty( $start );

		try {
			// Check if this is starting fresh or continuing
			$is_generation_active = ProgressManager::is_generation_active();

			error_log( "[FIDABR Actions] is_start: " . ( $is_start ? 'true' : 'false' ) . ", generation_active: " . ( $is_generation_active ? 'true' : 'false' ) );

			if ( $is_start ) {
				// Starting fresh - clear any existing progress
				if ( $is_generation_active ) {
					$old_progress_manager = new ProgressManager();
					$old_progress_manager->cleanup();
				}

				// Initialize new generation
				$settings = $this->settings->get_settings();

				error_log( "[FIDABR Actions] Settings post_types: " . wp_json_encode( $settings['post_types'] ) );

				if ( empty( $settings['post_types'] ) ) {
					return new \WP_Error( 'no_post_types', 'No post types selected for generation', array( 'status' => 400 ) );
				}

				$progress_manager = new ProgressManager();
				$generator = new Generator( $progress_manager );

				// Build queue and initialize
				$queue = $generator->build_processing_queue( $settings['post_types'], $settings );
				$total_posts = count( $queue ); // Use actual queue size as total

				$progress_manager->initialize( $settings['post_types'], $total_posts );
				$progress_manager->set_processing_queue( $queue );

				// Store settings in session for consistency
				$progress_data = $progress_manager->get_progress();
				$progress_data['settings'] = $settings;
				$progress_manager->save_progress( $progress_data );

				error_log( "[FIDABR Actions] Started new generation with {$total_posts} posts" );

			} else {
				// Continue existing generation
				if ( ! $is_generation_active ) {
					return rest_ensure_response( array(
						'finished' => true,
						'items' => array( 'parsed' => 0, 'total' => 0 ),
						'last' => null
					) );
				}

				$progress_manager = new ProgressManager();
			}

			// Get current progress
			$progress_data = $progress_manager->get_progress();

			if ( ! $progress_data ) {
				return new \WP_Error( 'session_error', 'Session error', array( 'status' => 500 ) );
			}

			// Get stored settings from session for consistency (define early for use in finalization)
			$stored_settings = isset( $progress_data['settings'] ) ? $progress_data['settings'] : $this->settings->get_settings();

			// Check if already completed
			if ( $progress_data['status'] === 'completed' ) {
				return rest_ensure_response( array(
					'finished' => true,
					'items' => array(
						'parsed' => $progress_data['total_posts'],
						'total' => $progress_data['total_posts']
					),
					'last' => null
				) );
			}

			// Check for errors
			if ( $progress_data['status'] === 'error' ) {
				$progress_manager->cleanup();
				return new \WP_Error( 'generation_error', 'Generation failed', array( 'status' => 500 ) );
			}

			// Process next item
			$next_item = $progress_manager->get_next_queue_item();

			if ( ! $next_item ) {
				// No more items - create final llms.txt file from cached CDN URLs
				$result = $this->create_final_llms_file( $stored_settings );

				if ( is_wp_error( $result ) ) {
					$progress_manager->fail_generation( $result->get_error_message() );
					$progress_manager->cleanup();
					return $result;
				}

				$progress_manager->complete_generation( $result['file_path'], $result['file_size'] );
				$progress_manager->cleanup();

				return rest_ensure_response( array(
					'finished' => true,
					'items' => array(
						'parsed' => $progress_data['total_posts'],
						'total' => $progress_data['total_posts']
					),
					'last' => null
				) );
			}

			// Update the next_item with stored settings to ensure consistency
			$next_item['options'] = $stored_settings;

			// Process the item
			$generator = new Generator( $progress_manager );
			$result = $generator->process_single_item( $next_item );

			$last_item = array(
				'type' => $next_item['post']->post_type,
				'id' => $next_item['post']->ID,
				'title' => $next_item['post']->post_title
			);

			if ( is_wp_error( $result ) ) {
				$progress_manager->fail_post( $next_item['post'], $result->get_error_message() );
				// Still increment processed posts counter for failed items
				$progress_manager->increment_processed_posts();
			} else {
				$progress_manager->complete_post( $next_item['post'], $result['cdn_url'] ?? null );
			}

			// Get updated progress
			$updated_progress = $progress_manager->get_progress();

			error_log( "[FIDABR Actions] Returning progress - parsed: {$updated_progress['processed_posts']}, total: {$updated_progress['total_posts']}" );

			return rest_ensure_response( array(
				'finished' => false,
				'items' => array(
					'parsed' => $updated_progress['processed_posts'],
					'total' => $updated_progress['total_posts']
				),
				'last' => $last_item
			) );

		} catch ( \Exception $e ) {
			error_log( "[FIDABR Actions] ERROR: Exception during generation: " . $e->getMessage() );
			return new \WP_Error( 'generation_exception', 'Generation failed: ' . $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Create final llms.txt file from cached CDN URLs without re-processing
	 *
	 * @param array $settings Generation settings
	 * @return array|\WP_Error Array with file_path and file_size or WP_Error on failure
	 */
	private function create_final_llms_file( $settings ) {
		try {
			error_log( "[FIDABR Actions] Creating final llms.txt file from cached CDN URLs" );

			// Validate settings
			if ( ! is_array( $settings ) ) {
				error_log( "[FIDABR Actions] ERROR: Invalid settings provided to create_final_llms_file" );
				return new \WP_Error( 'invalid_settings', 'Invalid settings provided for final file creation', array( 'status' => 500 ) );
			}

			if ( empty( $settings['post_types'] ) || ! is_array( $settings['post_types'] ) ) {
				error_log( "[FIDABR Actions] ERROR: No valid post_types found in settings" );
				return new \WP_Error( 'no_post_types', 'No valid post types found in settings', array( 'status' => 500 ) );
			}

			// Get site information
			$site_title = get_bloginfo( 'name' );
			$site_description = get_bloginfo( 'description' );

			// Build simple llms.txt content
			$content = "# " . $site_title . "\n\n";
			if ( ! empty( $site_description ) ) {
				$content .= $site_description . "\n\n";
			}

			$total_links = 0;

			// Process each post type and collect posts with cached CDN URLs
			foreach ( $settings['post_types'] as $post_type ) {
				$posts_with_urls = $this->get_posts_with_cdn_urls( $post_type, $settings );

				if ( ! empty( $posts_with_urls ) ) {
					$post_type_obj = get_post_type_object( $post_type );
					$section_name = $post_type_obj ? $post_type_obj->labels->name : ucfirst( $post_type );

					$content .= "## " . $section_name . "\n\n";

					foreach ( $posts_with_urls as $post_data ) {
						$content .= "- [" . $post_data['title'] . "](" . $post_data['cdn_url'] . ")\n";
						$total_links++;
					}
					$content .= "\n";
				}
			}

			$content .= "\n*Generated on " . current_time( 'Y-m-d H:i:s' ) . " with " . $total_links . " items*\n";

			// Get file path and save
			$generator = new Generator();
			$file_path = $generator->get_llms_file_path();
			$saved = $generator->save_llms_file( $file_path, $content );

			if ( is_wp_error( $saved ) ) {
				return $saved;
			}

			// Update WordPress meta
			update_option( 'fidabr_llms_last_generated', current_time( 'timestamp' ) );
			update_option( 'fidabr_llms_file_size', strlen( $content ) );

			error_log( "[FIDABR Actions] Final llms.txt file created successfully with {$total_links} links" );

			return array(
				'file_path' => $file_path,
				'file_size' => strlen( $content )
			);

		} catch ( \Exception $e ) {
			error_log( "[FIDABR Actions] ERROR: Exception creating final file: " . $e->getMessage() );
			return new \WP_Error( 'create_final_file_failed', 'Failed to create final file: ' . $e->getMessage(), array( 'status' => 500 ) );
		}
	}

	/**
	 * Get posts with cached CDN URLs for a specific post type
	 *
	 * @param string $post_type Post type name
	 * @param array $settings Generation settings
	 * @return array Array of posts with CDN URLs
	 */
	private function get_posts_with_cdn_urls( $post_type, $settings ) {
		$posts = get_posts( array(
			'post_type'   => $post_type,
			'post_status' => 'publish',
			'numberposts' => $settings['max_posts_per_type'] ?? 50,
			'meta_query'  => array(
				array(
					'key'     => '_fidabr_cdn_url',
					'compare' => 'EXISTS'
				)
			)
		) );

		$posts_with_urls = array();
		foreach ( $posts as $post ) {
			$cdn_url = get_post_meta( $post->ID, '_fidabr_cdn_url', true );
			if ( ! empty( $cdn_url ) ) {
				$posts_with_urls[] = array(
					'id'       => $post->ID,
					'title'    => $post->post_title,
					'cdn_url'  => $cdn_url,
				);
			}
		}

		return $posts_with_urls;
	}

	/**
	 * Clear custom metadata from all posts and pages
	 *
	 * @param \WP_REST_Request $request Request object
	 * @return \WP_REST_Response|\WP_Error REST response
	 */
	public function clear_metadata( $request = null ) {
		try {
			// Get all posts and pages with any plugin metadata
			$posts = get_posts( array(
				'post_type'   => array( 'post', 'page' ),
				'post_status' => array( 'publish', 'private', 'draft', 'pending' ),
				'numberposts' => -1, // Get all posts
				'meta_query'  => array(
					'relation' => 'OR',
					array(
						'key'     => '_fidabr_cdn_url',
						'compare' => 'EXISTS'
					),
					array(
						'key'     => '_fidabr_content_hash',
						'compare' => 'EXISTS'
					),
					array(
						'key'     => '_fidabr_cdn_upload_time',
						'compare' => 'EXISTS'
					),
					array(
						'key'     => '_fidabr_cdn_size',
						'compare' => 'EXISTS'
					)
				)
			) );

			$cleared_count = 0;
			$plugin_meta_keys = array(
				'_fidabr_cdn_url',
				'_fidabr_content_hash',
				'_fidabr_cdn_upload_time',
				'_fidabr_cdn_size'
			);

			foreach ( $posts as $post ) {
				$post_had_metadata = false;

				// Delete all plugin-specific metadata
				foreach ( $plugin_meta_keys as $meta_key ) {
					$deleted = delete_post_meta( $post->ID, $meta_key );
					if ( $deleted ) {
						$post_had_metadata = true;
					}
				}

				if ( $post_had_metadata ) {
					$cleared_count++;
				}
			}

			// Remove the LLMs.txt file
			$file_removed = false;
			$llms_file_path = $this->generator->get_llms_file_path();
			if ( file_exists( $llms_file_path ) ) {
				$file_removed = wp_delete_file( $llms_file_path );
				if ( $file_removed ) {
					error_log( "[FIDABR Actions] Removed LLMs.txt file: " . $llms_file_path );
				} else {
					error_log( "[FIDABR Actions] Failed to remove LLMs.txt file: " . $llms_file_path );
				}
			} else {
				error_log( "[FIDABR Actions] LLMs.txt file does not exist at: " . $llms_file_path );
			}

			// Clear WordPress options that track file status
			delete_option( 'fidabr_llms_last_generated' );
			delete_option( 'fidabr_llms_file_size' );
			error_log( "[FIDABR Actions] Cleared WordPress options for file tracking" );

			error_log( "[FIDABR Actions] Reset complete - cleared metadata from {$cleared_count} posts/pages, file removed: " . ( $file_removed ? 'yes' : 'no' ) );

			return rest_ensure_response( array(
				'message' => "Successfully reset plugin data. Cleared metadata from {$cleared_count} posts/pages" . ( $file_removed ? " and removed LLMs.txt file" : "" ) . ".",
				'cleared_count' => $cleared_count,
				'file_removed' => $file_removed
			) );

		} catch ( \Exception $e ) {
			error_log( "[FIDABR Actions] ERROR: Exception during reset: " . $e->getMessage() );
			return new \WP_Error( 'reset_failed', 'Failed to reset plugin data: ' . $e->getMessage(), array( 'status' => 500 ) );
		}
	}

}