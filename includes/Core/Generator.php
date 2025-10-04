<?php
/**
 * Generator - LLM file generation using llms-txt-php library
 *
 * @package FiloDataBrokerPlugin
 */

namespace FiloDataBrokerPlugin\Core;

use Stolt\LlmsTxt\LlmsTxt;
use Stolt\LlmsTxt\Section;
use Stolt\LlmsTxt\Section\Link;

defined( 'ABSPATH' ) || exit;

/**
 * Generator class for creating llms.txt files
 */
class Generator {

	/**
	 * CDN Client instance
	 *
	 * @var CDNClient
	 */
	private $cdn_client;

	/**
	 * Content Crawler instance
	 *
	 * @var ContentCrawler
	 */
	private $content_crawler;

	/**
	 * Progress Manager instance
	 *
	 * @var ProgressManager
	 */
	private $progress_manager;

	/**
	 * Constructor
	 *
	 * @param ProgressManager $progress_manager Optional progress manager instance
	 */
	public function __construct( $progress_manager = null ) {
		$this->cdn_client        = new CDNClient();
		$this->content_crawler   = new ContentCrawler();
		$this->progress_manager  = $progress_manager;
	}

	/**
	 * Generate llms.txt file
	 *
	 * @param array $options Generation options
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	public function generate_llms_file( $options = array() ) {
		error_log( "[FIDABR Generator] Starting llms.txt generation" );

		$default_options = array(
			'post_types'         => array( 'post', 'page' ),
			'auto_update'        => true,
			'include_excerpts'   => true,
			'max_posts_per_type' => 50,
		);

		$options = wp_parse_args( $options, $default_options );
		error_log( "[FIDABR Generator] Generation options: " . json_encode( $options ) );

		try {
			// Initialize progress tracking if we have a progress manager
			if ( $this->progress_manager ) {
				$total_posts = $this->calculate_total_posts( $options['post_types'], $options );
				$this->progress_manager->initialize( $options['post_types'], $total_posts );
				$this->progress_manager->update_operation( 'Initializing generation process' );
			}

			// Get site information
			$site_title       = get_bloginfo( 'name' );
			$site_description = get_bloginfo( 'description' );
			error_log( "[FIDABR Generator] Site info - Title: $site_title, Description: $site_description" );

			if ( $this->progress_manager ) {
				$this->progress_manager->update_operation( 'Setting up LLMs.txt structure' );
			}

			// Create LlmsTxt instance
			$llms_txt = new LlmsTxt();
			$llms_txt->title( $site_title );

			if ( ! empty( $site_description ) ) {
				$llms_txt->description( $site_description );
			}

			$sections_added = 0;

			// Process each post type
			foreach ( $options['post_types'] as $post_type ) {
				error_log( "[FIDABR Generator] Processing post type: $post_type" );

				if ( $this->progress_manager ) {
					$this->progress_manager->update_operation( "Processing {$post_type} content", $post_type );
				}

				$section = $this->create_section_for_post_type( $post_type, $options );
				if ( $section ) {
					$llms_txt->addSection( $section );
					$sections_added++;
					error_log( "[FIDABR Generator] Added section for $post_type" );
				} else {
					error_log( "[FIDABR Generator] WARNING: No section created for $post_type" );
				}
			}

			error_log( "[FIDABR Generator] Total sections added: $sections_added" );

			if ( $this->progress_manager ) {
				$this->progress_manager->update_operation( 'Generating final LLMs.txt file content' );
			}

			// Generate the llms.txt content
			$llms_content = $llms_txt->toString();
			error_log( "[FIDABR Generator] Generated llms.txt content length: " . strlen( $llms_content ) . " chars" );

			if ( $this->progress_manager ) {
				$this->progress_manager->update_operation( 'Saving LLMs.txt file to server' );
			}

			// Save to WordPress root (following reference implementation pattern)
			$file_path = $this->get_llms_file_path();
			error_log( "[FIDABR Generator] Saving to file path: $file_path" );
			$saved = $this->save_llms_file( $file_path, $llms_content );

			if ( is_wp_error( $saved ) ) {
				error_log( "[FIDABR Generator] ERROR saving file: " . $saved->get_error_message() );

				if ( $this->progress_manager ) {
					$this->progress_manager->fail_generation( $saved->get_error_message() );
				}

				return $saved;
			}

			// Update last generation time
			update_option( 'fidabr_llms_last_generated', current_time( 'timestamp' ) );
			update_option( 'fidabr_llms_file_size', strlen( $llms_content ) );

			// Mark generation as completed
			if ( $this->progress_manager ) {
				$this->progress_manager->complete_generation( $file_path, strlen( $llms_content ) );
			}

			error_log( "[FIDABR Generator] llms.txt generation completed successfully" );
			return true;

		} catch ( \Exception $e ) {
			error_log( "[FIDABR Generator] ERROR: Exception during generation: " . $e->getMessage() );

			if ( $this->progress_manager ) {
				$this->progress_manager->fail_generation( $e->getMessage() );
			}

			return new \WP_Error(
				'llms_generation_failed',
				'Failed to generate llms.txt: ' . $e->getMessage()
			);
		}
	}

	/**
	 * Create a section for a specific post type
	 *
	 * @param string $post_type Post type name
	 * @param array  $options Generation options
	 * @return Section|null Section instance or null if no posts found
	 */
	private function create_section_for_post_type( $post_type, $options ) {
		error_log( "[FIDABR Generator] Creating section for post type: $post_type" );

		// Get posts from content crawler
		$posts = $this->content_crawler->get_posts_for_type( $post_type, $options );
		error_log( "[FIDABR Generator] Retrieved " . count( $posts ) . " posts for $post_type" );

		if ( empty( $posts ) ) {
			error_log( "[FIDABR Generator] No posts found for $post_type, returning null section" );

			if ( $this->progress_manager ) {
				$this->progress_manager->complete_section( $post_type, 0 );
			}

			return null;
		}

		// Create section
		$post_type_obj = get_post_type_object( $post_type );
		$section_name  = $post_type_obj ? $post_type_obj->labels->name : ucfirst( $post_type );
		error_log( "[FIDABR Generator] Creating section named: $section_name" );

		$section = new Section();
		$section->name( $section_name );

		$links_added = 0;

		// Process posts and upload to CDN
		foreach ( $posts as $post ) {
			error_log( "[FIDABR Generator] Processing post ID {$post->ID}: {$post->post_title}" );

			if ( $this->progress_manager ) {
				$this->progress_manager->update_current_post( $post, 'processing' );
			}

			$link = $this->create_link_for_post( $post, $options );
			if ( $link ) {
				$section->addLink( $link );
				$links_added++;
				error_log( "[FIDABR Generator] Added link for post ID {$post->ID}" );

				if ( $this->progress_manager ) {
					$cdn_url = get_post_meta( $post->ID, '_fidabr_cdn_url', true );
					$this->progress_manager->complete_post( $post, $cdn_url );
				}
			} else {
				error_log( "[FIDABR Generator] WARNING: Failed to create link for post ID {$post->ID}" );

				if ( $this->progress_manager ) {
					$this->progress_manager->fail_post( $post, 'Failed to create link for post' );
				}
			}
		}

		// Mark section as completed
		if ( $this->progress_manager ) {
			$this->progress_manager->complete_section( $post_type, $links_added );
		}

		error_log( "[FIDABR Generator] Section '$section_name' created with $links_added links" );
		return $section;
	}

	/**
	 * Create a link for a specific post
	 *
	 * @param \WP_Post $post Post object
	 * @param array    $options Generation options
	 * @return Link|null Link instance or null on failure
	 */
	private function create_link_for_post( $post, $options ) {
		error_log( "[FIDABR Generator] Creating link for post ID {$post->ID}: {$post->post_title}" );

		// Get post content
		$content = $this->content_crawler->extract_post_content( $post, $options );
		error_log( "[FIDABR Generator] Extracted content length: " . strlen( $content ) . " chars" );

		if ( empty( $content ) ) {
			error_log( "[FIDABR Generator] WARNING: Empty content extracted for post ID {$post->ID}, returning null link" );

			if ( $this->progress_manager ) {
				$this->progress_manager->fail_post( $post, 'Empty content extracted' );
			}

			return null;
		}

		// Check if we already have a CDN URL for this content
		$content_hash = md5( $content );
		$stored_cdn_url = get_post_meta( $post->ID, '_fidabr_cdn_url', true );
		$stored_content_hash = get_post_meta( $post->ID, '_fidabr_content_hash', true );

		$cdn_url = null;

		if ( ! empty( $stored_cdn_url ) && $stored_content_hash === $content_hash ) {
			// Content hasn't changed, reuse existing CDN URL
			error_log( "[FIDABR Generator] Reusing existing CDN URL for post {$post->ID}: {$stored_cdn_url}" );
			$cdn_url = $stored_cdn_url;
		} else {
			// Content changed or no stored URL, upload to CDN
			error_log( "[FIDABR Generator] Content changed or no stored URL, uploading to CDN for post {$post->ID}" );

			// Generate filename
			$filename = $this->generate_filename( $post );
			error_log( "[FIDABR Generator] Generated filename: $filename" );

			// Update progress to show content extraction completed
			if ( $this->progress_manager ) {
				$this->progress_manager->update_operation( "Uploading {$post->post_title} to CDN", $post->post_type );
			}

			// Update progress for uploading
			if ( $this->progress_manager ) {
				$this->progress_manager->update_current_post( $post, 'uploading' );
			}

			// Upload to CDN
			$upload_result = $this->cdn_client->upload_content( $content, $filename );

			if ( is_wp_error( $upload_result ) ) {
				// Log error but continue with other posts
				error_log( "[FIDABR Generator] ERROR: CDN upload failed for post {$post->ID}: " . $upload_result->get_error_message() );

				if ( $this->progress_manager ) {
					$this->progress_manager->fail_post( $post, $upload_result->get_error_message() );
				}

				return null;
			}

			$cdn_url = $upload_result['url'];
			error_log( "[FIDABR Generator] CDN upload successful for post {$post->ID}, URL: " . $cdn_url );

			// Store new CDN URL and content hash in metadata
			update_post_meta( $post->ID, '_fidabr_cdn_url', $cdn_url );
			update_post_meta( $post->ID, '_fidabr_cdn_upload_time', current_time( 'timestamp' ) );
			update_post_meta( $post->ID, '_fidabr_content_hash', $content_hash );
		}

		// Create description
		$description = $this->generate_post_description( $post, $options );
		error_log( "[FIDABR Generator] Generated description length: " . strlen( $description ) . " chars" );

		// Create link
		$link = new Link();
		$link->urlTitle( $post->post_title );
		$link->url( $cdn_url );

		if ( ! empty( $description ) ) {
			$link->urlDetails( $description );
		}

		error_log( "[FIDABR Generator] Link created successfully for post ID {$post->ID}" );
		return $link;
	}

	/**
	 * Generate filename for post content
	 *
	 * @param \WP_Post $post Post object
	 * @return string Filename
	 */
	private function generate_filename( $post ) {
		$slug = $post->post_name ?: sanitize_title( $post->post_title );
		return $post->post_type . '_' . $post->ID . '_' . $slug . '.md';
	}

	/**
	 * Generate description for post
	 *
	 * @param \WP_Post $post Post object
	 * @param array    $options Generation options
	 * @return string Description
	 */
	private function generate_post_description( $post, $options ) {
		$description = '';

		// Use excerpt if available and requested
		if ( $options['include_excerpts'] && ! empty( $post->post_excerpt ) ) {
			$description = $post->post_excerpt;
		} else {
			// Generate excerpt from content
			$content     = $post->post_content;
			$content     = wp_strip_all_tags( $content );
			$content     = preg_replace( '/\s+/', ' ', $content );
			$description = wp_trim_words( $content, 30 );
		}

		return $description;
	}

	/**
	 * Get the correct file path for llms.txt (following reference implementation)
	 *
	 * @return string File path for llms.txt
	 */
	public function get_llms_file_path() {
		if ( defined( 'FLYWHEEL_PLUGIN_DIR' ) ) {
			return trailingslashit( dirname( ABSPATH ) ) . 'www/' . 'llms.txt';
		} else {
			return trailingslashit( ABSPATH ) . 'llms.txt';
		}
	}

	/**
	 * Save llms.txt file to filesystem
	 *
	 * @param string $file_path File path
	 * @param string $content File content
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	public function save_llms_file( $file_path, $content ) {
		// Initialize WordPress filesystem
		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! WP_Filesystem() ) {
			return new \WP_Error(
				'filesystem_error',
				'Could not initialize WordPress filesystem'
			);
		}

		// Write file
		$result = $wp_filesystem->put_contents( $file_path, $content, FS_CHMOD_FILE );

		if ( ! $result ) {
			return new \WP_Error(
				'file_write_error',
				'Could not write llms.txt file to: ' . $file_path
			);
		}

		return true;
	}

	/**
	 * Get generation status
	 *
	 * @return array Status information
	 */
	public function get_generation_status() {
		$last_generated = get_option( 'fidabr_llms_last_generated', 0 );
		$file_size      = get_option( 'fidabr_llms_file_size', 0 );
		$llms_file_path = $this->get_llms_file_path();

		return array(
			'last_generated'    => $last_generated,
			'last_generated_hr' => $last_generated ? wp_date( 'Y-m-d H:i:s', $last_generated ) : 'Never',
			'file_exists'       => file_exists( $llms_file_path ),
			'file_size'         => $file_size,
			'file_size_hr'      => size_format( $file_size ),
			'file_url'          => home_url( 'llms.txt' ),
		);
	}

	/**
	 * Delete llms.txt file
	 *
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	public function delete_llms_file() {
		$llms_file_path = $this->get_llms_file_path();

		if ( ! file_exists( $llms_file_path ) ) {
			return true;
		}

		global $wp_filesystem;

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		if ( ! WP_Filesystem() ) {
			return new \WP_Error(
				'filesystem_error',
				'Could not initialize WordPress filesystem'
			);
		}

		$result = $wp_filesystem->delete( $llms_file_path );

		if ( ! $result ) {
			return new \WP_Error(
				'file_delete_error',
				'Could not delete llms.txt file'
			);
		}

		// Clear stored options
		delete_option( 'fidabr_llms_last_generated' );
		delete_option( 'fidabr_llms_file_size' );

		return true;
	}

	/**
	 * Calculate total number of posts across all post types
	 *
	 * @param array $post_types Array of post type names
	 * @param array $options Generation options
	 * @return int Total number of posts
	 */
	public function calculate_total_posts( $post_types, $options ) {
		$total = 0;

		foreach ( $post_types as $post_type ) {
			$posts = $this->content_crawler->get_posts_for_type( $post_type, $options );
			$total += count( $posts );
		}

		return $total;
	}

	/**
	 * Build processing queue for polling mechanism
	 *
	 * @param array $post_types Array of post type names
	 * @param array $options Generation options
	 * @return array Queue of items to process
	 */
	public function build_processing_queue( $post_types, $options ) {
		$queue = array();

		error_log( "[FIDABR Generator] Building queue for post types: " . wp_json_encode( $post_types ) );

		foreach ( $post_types as $post_type ) {
			$posts = $this->content_crawler->get_posts_for_type( $post_type, $options );

			error_log( "[FIDABR Generator] Found " . count( $posts ) . " posts for type: {$post_type}" );

			foreach ( $posts as $post ) {
				$queue[] = array(
					'post' => $post,
					'post_type' => $post_type,
					'options' => $options
				);
			}
		}

		error_log( "[FIDABR Generator] Built processing queue with " . count( $queue ) . " items" );
		return $queue;
	}

	/**
	 * Process a single item from the queue
	 *
	 * @param array $item Queue item containing post, post_type, and options
	 * @return array|\WP_Error Result with cdn_url or WP_Error on failure
	 */
	public function process_single_item( $item ) {
		$post = $item['post'];
		$options = $item['options'];

		try {
			error_log( "[FIDABR Generator] Processing single item: post ID {$post->ID}" );

			if ( $this->progress_manager ) {
				$this->progress_manager->update_current_post( $post, 'processing' );
			}

			// Extract content
			$content = $this->content_crawler->extract_post_content( $post, $options );

			if ( empty( $content ) ) {
				error_log( "[FIDABR Generator] WARNING: Empty content extracted for post ID {$post->ID}" );
				return new \WP_Error( 'empty_content', 'Empty content extracted for post: ' . $post->post_title );
			}

			// Check if we already have a CDN URL for this content
			$content_hash = md5( $content );
			$stored_cdn_url = get_post_meta( $post->ID, '_fidabr_cdn_url', true );
			$stored_content_hash = get_post_meta( $post->ID, '_fidabr_content_hash', true );

			$cdn_url = null;

			if ( ! empty( $stored_cdn_url ) && $stored_content_hash === $content_hash ) {
				// Content hasn't changed, reuse existing CDN URL
				error_log( "[FIDABR Generator] Reusing existing CDN URL for post {$post->ID}: {$stored_cdn_url}" );
				$cdn_url = $stored_cdn_url;
			} else {
				// Content changed or no stored URL, upload to CDN
				error_log( "[FIDABR Generator] Content changed or no stored URL, uploading to CDN for post {$post->ID}" );

				// Generate filename
				$filename = $this->generate_filename( $post );

				if ( $this->progress_manager ) {
					$this->progress_manager->update_current_post( $post, 'uploading' );
				}

				// Upload to CDN
				$upload_result = $this->cdn_client->upload_content( $content, $filename );

				if ( is_wp_error( $upload_result ) ) {
					error_log( "[FIDABR Generator] ERROR: CDN upload failed for post {$post->ID}: " . $upload_result->get_error_message() );
					return $upload_result;
				}

				$cdn_url = $upload_result['url'];
				error_log( "[FIDABR Generator] CDN upload successful for post {$post->ID}, URL: " . $cdn_url );

				// Store new CDN URL and content hash in metadata
				update_post_meta( $post->ID, '_fidabr_cdn_url', $cdn_url );
				update_post_meta( $post->ID, '_fidabr_cdn_upload_time', current_time( 'timestamp' ) );
				update_post_meta( $post->ID, '_fidabr_content_hash', $content_hash );
			}

			return array(
				'cdn_url' => $cdn_url,
				'post_id' => $post->ID,
				'post_title' => $post->post_title,
				'file_size' => strlen( $content )
			);

		} catch ( \Exception $e ) {
			error_log( "[FIDABR Generator] ERROR: Exception processing post ID {$post->ID}: " . $e->getMessage() );
			return new \WP_Error( 'processing_exception', 'Exception processing post: ' . $e->getMessage() );
		}
	}

}