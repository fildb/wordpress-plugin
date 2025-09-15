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
	 * Constructor
	 */
	public function __construct() {
		$this->cdn_client      = new CDNClient();
		$this->content_crawler = new ContentCrawler();
	}

	/**
	 * Generate llms.txt file
	 *
	 * @param array $options Generation options
	 * @return bool|WP_Error True on success, WP_Error on failure
	 */
	public function generate_llms_file( $options = array() ) {
		error_log( "[FDB Generator] Starting llms.txt generation" );

		$default_options = array(
			'post_types'         => array( 'post', 'page' ),
			'auto_update'        => true,
			'include_excerpts'   => true,
			'max_posts_per_type' => 50,
		);

		$options = wp_parse_args( $options, $default_options );
		error_log( "[FDB Generator] Generation options: " . json_encode( $options ) );

		try {
			// Get site information
			$site_title       = get_bloginfo( 'name' );
			$site_description = get_bloginfo( 'description' );
			error_log( "[FDB Generator] Site info - Title: $site_title, Description: $site_description" );

			// Create LlmsTxt instance
			$llms_txt = new LlmsTxt();
			$llms_txt->title( $site_title );

			if ( ! empty( $site_description ) ) {
				$llms_txt->description( $site_description );
			}

			$sections_added = 0;

			// Process each post type
			foreach ( $options['post_types'] as $post_type ) {
				error_log( "[FDB Generator] Processing post type: $post_type" );
				$section = $this->create_section_for_post_type( $post_type, $options );
				if ( $section ) {
					$llms_txt->addSection( $section );
					$sections_added++;
					error_log( "[FDB Generator] Added section for $post_type" );
				} else {
					error_log( "[FDB Generator] WARNING: No section created for $post_type" );
				}
			}

			error_log( "[FDB Generator] Total sections added: $sections_added" );

			// Generate the llms.txt content
			$llms_content = $llms_txt->toString();
			error_log( "[FDB Generator] Generated llms.txt content length: " . strlen( $llms_content ) . " chars" );

			// Save to WordPress root (following reference implementation pattern)
			$file_path = $this->get_llms_file_path();
			error_log( "[FDB Generator] Saving to file path: $file_path" );
			$saved = $this->save_llms_file( $file_path, $llms_content );

			if ( is_wp_error( $saved ) ) {
				error_log( "[FDB Generator] ERROR saving file: " . $saved->get_error_message() );
				return $saved;
			}

			// Update last generation time
			update_option( 'fdb_llms_last_generated', current_time( 'timestamp' ) );
			update_option( 'fdb_llms_file_size', strlen( $llms_content ) );

			error_log( "[FDB Generator] llms.txt generation completed successfully" );
			return true;

		} catch ( \Exception $e ) {
			error_log( "[FDB Generator] ERROR: Exception during generation: " . $e->getMessage() );
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
		error_log( "[FDB Generator] Creating section for post type: $post_type" );

		// Get posts from content crawler
		$posts = $this->content_crawler->get_posts_for_type( $post_type, $options );
		error_log( "[FDB Generator] Retrieved " . count( $posts ) . " posts for $post_type" );

		if ( empty( $posts ) ) {
			error_log( "[FDB Generator] No posts found for $post_type, returning null section" );
			return null;
		}

		// Create section
		$post_type_obj = get_post_type_object( $post_type );
		$section_name  = $post_type_obj ? $post_type_obj->labels->name : ucfirst( $post_type );
		error_log( "[FDB Generator] Creating section named: $section_name" );

		$section = new Section();
		$section->name( $section_name );

		$links_added = 0;

		// Process posts and upload to CDN
		foreach ( $posts as $post ) {
			error_log( "[FDB Generator] Processing post ID {$post->ID}: {$post->post_title}" );
			$link = $this->create_link_for_post( $post, $options );
			if ( $link ) {
				$section->addLink( $link );
				$links_added++;
				error_log( "[FDB Generator] Added link for post ID {$post->ID}" );
			} else {
				error_log( "[FDB Generator] WARNING: Failed to create link for post ID {$post->ID}" );
			}
		}

		error_log( "[FDB Generator] Section '$section_name' created with $links_added links" );
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
		error_log( "[FDB Generator] Creating link for post ID {$post->ID}: {$post->post_title}" );

		// Get post content
		$content = $this->content_crawler->extract_post_content( $post, $options );
		error_log( "[FDB Generator] Extracted content length: " . strlen( $content ) . " chars" );

		if ( empty( $content ) ) {
			error_log( "[FDB Generator] WARNING: Empty content extracted for post ID {$post->ID}, returning null link" );
			return null;
		}

		// Generate filename
		$filename = $this->generate_filename( $post );
		error_log( "[FDB Generator] Generated filename: $filename" );

		// Upload to CDN
		$upload_result = $this->cdn_client->upload_content( $content, $filename );

		if ( is_wp_error( $upload_result ) ) {
			// Log error but continue with other posts
			error_log( "[FDB Generator] ERROR: CDN upload failed for post {$post->ID}: " . $upload_result->get_error_message() );
			return null;
		}

		error_log( "[FDB Generator] CDN upload successful for post {$post->ID}, URL: " . $upload_result['url'] );

		// Create description
		$description = $this->generate_post_description( $post, $options );
		error_log( "[FDB Generator] Generated description length: " . strlen( $description ) . " chars" );

		// Create link
		$link = new Link();
		$link->urlTitle( $post->post_title );
		$link->url( $upload_result['url'] );

		if ( ! empty( $description ) ) {
			$link->urlDetails( $description );
		}

		error_log( "[FDB Generator] Link created successfully for post ID {$post->ID}" );
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
	private function get_llms_file_path() {
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
	private function save_llms_file( $file_path, $content ) {
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
		$last_generated = get_option( 'fdb_llms_last_generated', 0 );
		$file_size      = get_option( 'fdb_llms_file_size', 0 );
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
		delete_option( 'fdb_llms_last_generated' );
		delete_option( 'fdb_llms_file_size' );

		return true;
	}
}