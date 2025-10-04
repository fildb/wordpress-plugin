<?php
/**
 * ProgressManager - Tracks and manages LLM generation progress
 *
 * @package FiloDataBrokerPlugin
 */

namespace FiloDataBrokerPlugin\Core;

defined( 'ABSPATH' ) || exit;

/**
 * ProgressManager class for tracking LLM generation progress
 */
class ProgressManager {

	/**
	 * Fixed transient key for single session
	 *
	 * @var string
	 */
	const TRANSIENT_KEY = 'fidabr_llm_progress_active';

	/**
	 * Constructor
	 */
	public function __construct() {
		// No session ID needed - we use a single, fixed transient key
	}


	/**
	 * Initialize progress tracking
	 *
	 * @param array $post_types Array of post types to process
	 * @param int   $total_posts Total number of posts across all types
	 * @return void
	 */
	public function initialize( $post_types, $total_posts ) {
		$progress_data = array(
			'status'             => 'initializing',
			'overall_progress'   => 0,
			'current_operation'  => 'Initializing generation process',
			'current_post_type'  => null,
			'processed_posts'    => 0,
			'total_posts'        => $total_posts,
			'current_post'       => null,
			'completed_sections' => array(),
			'post_types'         => $post_types,
			'errors'             => array(),
			'processing_queue'   => array(),
			'queue_index'        => 0,
			'started_at'         => current_time( 'timestamp' ),
			'updated_at'         => current_time( 'timestamp' ),
		);

		$this->save_progress( $progress_data );
		error_log( "[FIDABR ProgressManager] Initialized generation with {$total_posts} total posts" );
	}

	/**
	 * Update current operation status
	 *
	 * @param string $operation Current operation description
	 * @param string $post_type Current post type being processed
	 * @return void
	 */
	public function update_operation( $operation, $post_type = null ) {
		$progress_data = $this->get_progress();
		if ( ! $progress_data ) {
			return;
		}

		$progress_data['current_operation'] = $operation;
		$progress_data['current_post_type'] = $post_type;
		$progress_data['status'] = 'processing';
		$progress_data['updated_at'] = current_time( 'timestamp' );

		$this->save_progress( $progress_data );
		error_log( "[FIDABR ProgressManager] Operation: {$operation}" . ( $post_type ? " (Post Type: {$post_type})" : '' ) );
	}

	/**
	 * Update current post being processed
	 *
	 * @param \WP_Post $post Post object
	 * @param string   $status Post processing status (processing, uploading, completed, failed)
	 * @return void
	 */
	public function update_current_post( $post, $status = 'processing' ) {
		$progress_data = $this->get_progress();
		if ( ! $progress_data ) {
			return;
		}

		$progress_data['current_post'] = array(
			'id'     => $post->ID,
			'title'  => $post->post_title,
			'type'   => $post->post_type,
			'status' => $status,
		);
		$progress_data['updated_at'] = current_time( 'timestamp' );

		$this->save_progress( $progress_data );
		error_log( "[FIDABR ProgressManager] Processing post ID {$post->ID}: {$post->post_title} (Status: {$status})" );
	}

	/**
	 * Mark a post as completed
	 *
	 * @param \WP_Post $post Post object
	 * @param string   $cdn_url CDN URL for the uploaded content
	 * @return void
	 */
	public function complete_post( $post, $cdn_url = null ) {
		$progress_data = $this->get_progress();
		if ( ! $progress_data ) {
			return;
		}

		$progress_data['processed_posts']++;
		$progress_data['overall_progress'] = $this->calculate_progress( $progress_data['processed_posts'], $progress_data['total_posts'] );

		// Update current post status
		if ( $progress_data['current_post'] && $progress_data['current_post']['id'] === $post->ID ) {
			$progress_data['current_post']['status'] = 'completed';
			if ( $cdn_url ) {
				$progress_data['current_post']['cdn_url'] = $cdn_url;
			}
		}

		$progress_data['updated_at'] = current_time( 'timestamp' );

		$this->save_progress( $progress_data );
		error_log( "[FIDABR ProgressManager] Completed post ID {$post->ID}: {$post->post_title} ({$progress_data['processed_posts']}/{$progress_data['total_posts']})" );
	}

	/**
	 * Mark a post as failed
	 *
	 * @param \WP_Post $post Post object
	 * @param string   $error_message Error message
	 * @return void
	 */
	public function fail_post( $post, $error_message ) {
		$progress_data = $this->get_progress();
		if ( ! $progress_data ) {
			return;
		}

		// Update current post status
		if ( $progress_data['current_post'] && $progress_data['current_post']['id'] === $post->ID ) {
			$progress_data['current_post']['status'] = 'failed';
			$progress_data['current_post']['error'] = $error_message;
		}

		// Add to errors array
		$progress_data['errors'][] = array(
			'post_id'     => $post->ID,
			'post_title'  => $post->post_title,
			'post_type'   => $post->post_type,
			'error'       => $error_message,
			'timestamp'   => current_time( 'timestamp' ),
		);

		$progress_data['updated_at'] = current_time( 'timestamp' );

		$this->save_progress( $progress_data );
		error_log( "[FIDABR ProgressManager] Failed post ID {$post->ID}: {$post->post_title} - {$error_message}" );
	}

	/**
	 * Complete a section (post type)
	 *
	 * @param string $post_type Post type that was completed
	 * @param int    $posts_processed Number of posts processed in this section
	 * @return void
	 */
	public function complete_section( $post_type, $posts_processed ) {
		$progress_data = $this->get_progress();
		if ( ! $progress_data ) {
			return;
		}

		$post_type_obj = get_post_type_object( $post_type );
		$section_name = $post_type_obj ? $post_type_obj->labels->name : ucfirst( $post_type );

		$progress_data['completed_sections'][] = array(
			'name'            => $section_name,
			'post_type'       => $post_type,
			'posts_processed' => $posts_processed,
			'completed_at'    => current_time( 'timestamp' ),
		);
		$progress_data['current_post_type'] = null;
		$progress_data['updated_at'] = current_time( 'timestamp' );

		$this->save_progress( $progress_data );
		error_log( "[FIDABR ProgressManager] Completed section '{$section_name}' with {$posts_processed} posts" );
	}

	/**
	 * Mark generation as completed
	 *
	 * @param string $file_path Path to generated file
	 * @param int    $file_size Size of generated file
	 * @return void
	 */
	public function complete_generation( $file_path, $file_size ) {
		$progress_data = $this->get_progress();
		if ( ! $progress_data ) {
			return;
		}

		$progress_data['status'] = 'completed';
		$progress_data['overall_progress'] = 100;
		$progress_data['current_operation'] = 'Generation completed successfully';
		$progress_data['current_post'] = null;
		$progress_data['file_path'] = $file_path;
		$progress_data['file_size'] = $file_size;
		$progress_data['completed_at'] = current_time( 'timestamp' );
		$progress_data['updated_at'] = current_time( 'timestamp' );

		$this->save_progress( $progress_data );
		error_log( "[FIDABR ProgressManager] Generation completed successfully. File: {$file_path} ({$file_size} bytes)" );
	}

	/**
	 * Mark generation as failed
	 *
	 * @param string $error_message Error message
	 * @return void
	 */
	public function fail_generation( $error_message ) {
		$progress_data = $this->get_progress();
		if ( ! $progress_data ) {
			return;
		}

		$progress_data['status'] = 'error';
		$progress_data['current_operation'] = 'Generation failed';
		$progress_data['current_post'] = null;
		$progress_data['errors'][] = array(
			'type'      => 'generation_error',
			'error'     => $error_message,
			'timestamp' => current_time( 'timestamp' ),
		);
		$progress_data['failed_at'] = current_time( 'timestamp' );
		$progress_data['updated_at'] = current_time( 'timestamp' );

		$this->save_progress( $progress_data );
		error_log( "[FIDABR ProgressManager] Generation failed: {$error_message}" );
	}

	/**
	 * Get current progress data
	 *
	 * @return array|null Progress data or null if not found
	 */
	public function get_progress() {
		return get_transient( self::TRANSIENT_KEY );
	}

	/**
	 * Save progress data and emit SSE event
	 *
	 * @param array $progress_data Progress data to save
	 * @return void
	 */
	public function save_progress( $progress_data ) {
		// Store for 1 hour - long enough for generation but will auto-cleanup
		set_transient( self::TRANSIENT_KEY, $progress_data, HOUR_IN_SECONDS );

		// Emit WordPress action for real-time SSE updates
		do_action( 'fidabr_progress_update', $progress_data );
	}

	/**
	 * Calculate progress percentage
	 *
	 * @param int $processed Number of posts processed
	 * @param int $total Total number of posts
	 * @return int Progress percentage (0-100)
	 */
	private function calculate_progress( $processed, $total ) {
		if ( $total === 0 ) {
			return 0;
		}
		return min( 100, floor( ( $processed / $total ) * 100 ) );
	}

	/**
	 * Clean up progress data (called when generation is complete or fails)
	 *
	 * @return void
	 */
	public function cleanup() {
		delete_transient( self::TRANSIENT_KEY );
		error_log( "[FIDABR ProgressManager] Cleaned up generation progress" );
	}

	/**
	 * Get estimated time remaining
	 *
	 * @return string Human-readable time estimate
	 */
	public function get_time_estimate() {
		$progress_data = $this->get_progress();
		if ( ! $progress_data || $progress_data['processed_posts'] === 0 ) {
			return 'Calculating...';
		}

		$elapsed_time = current_time( 'timestamp' ) - $progress_data['started_at'];
		$posts_per_second = $progress_data['processed_posts'] / max( 1, $elapsed_time );
		$remaining_posts = $progress_data['total_posts'] - $progress_data['processed_posts'];
		$estimated_seconds = $remaining_posts / max( 0.1, $posts_per_second );

		if ( $estimated_seconds < 60 ) {
			return sprintf( '%d seconds', ceil( $estimated_seconds ) );
		} elseif ( $estimated_seconds < 3600 ) {
			return sprintf( '%d minutes', ceil( $estimated_seconds / 60 ) );
		} else {
			return sprintf( '%d hours', ceil( $estimated_seconds / 3600 ) );
		}
	}

	/**
	 * Set the processing queue
	 *
	 * @param array $queue Array of items to process
	 * @return void
	 */
	public function set_processing_queue( $queue ) {
		$progress_data = $this->get_progress();
		if ( ! $progress_data ) {
			return;
		}

		$progress_data['processing_queue'] = $queue;
		$progress_data['queue_index'] = 0;
		$progress_data['status'] = 'processing';
		$progress_data['updated_at'] = current_time( 'timestamp' );

		$this->save_progress( $progress_data );
		error_log( "[FIDABR ProgressManager] Set processing queue with " . count( $queue ) . " items" );
	}

	/**
	 * Get the next item from the processing queue
	 *
	 * @return array|null Next queue item or null if queue is empty
	 */
	public function get_next_queue_item() {
		$progress_data = $this->get_progress();
		if ( ! $progress_data ) {
			return null;
		}

		$queue = $progress_data['processing_queue'] ?? array();
		$index = $progress_data['queue_index'] ?? 0;

		if ( $index >= count( $queue ) ) {
			return null; // Queue is exhausted
		}

		// Get the current item and advance the index
		$item = $queue[ $index ];
		$progress_data['queue_index'] = $index + 1;
		$progress_data['updated_at'] = current_time( 'timestamp' );

		$this->save_progress( $progress_data );

		return $item;
	}

	/**
	 * Increment processed posts counter
	 *
	 * @return void
	 */
	public function increment_processed_posts() {
		$progress_data = $this->get_progress();
		if ( ! $progress_data ) {
			return;
		}

		$progress_data['processed_posts']++;
		$progress_data['overall_progress'] = $this->calculate_progress( $progress_data['processed_posts'], $progress_data['total_posts'] );
		$progress_data['updated_at'] = current_time( 'timestamp' );

		$this->save_progress( $progress_data );
		error_log( "[FIDABR ProgressManager] Incremented processed posts to {$progress_data['processed_posts']}/{$progress_data['total_posts']}" );
	}

	/**
	 * Check if generation is currently active
	 *
	 * @return bool True if generation is active, false otherwise
	 */
	public static function is_generation_active() {
		$progress_data = get_transient( self::TRANSIENT_KEY );

		if ( ! $progress_data || ! is_array( $progress_data ) ) {
			return false;
		}

		$status = $progress_data['status'] ?? '';
		return ! in_array( $status, array( 'completed', 'error' ), true );
	}
}