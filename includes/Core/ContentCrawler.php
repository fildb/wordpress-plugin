<?php
/**
 * ContentCrawler - WordPress content extraction and processing
 *
 * @package FiloDataBrokerPlugin
 */

namespace FiloDataBrokerPlugin\Core;

defined( 'ABSPATH' ) || exit;

/**
 * ContentCrawler class for analyzing and extracting WordPress content
 */
class ContentCrawler {

	/**
	 * Get posts for a specific post type
	 *
	 * @param string $post_type Post type name
	 * @param array  $options Options for filtering and limiting posts
	 * @return array Array of WP_Post objects
	 */
	public function get_posts_for_type( $post_type, $options = array() ) {
		error_log( "[FIDABR ContentCrawler] Getting posts for type: $post_type" );
		error_log( "[FIDABR ContentCrawler] Options: " . json_encode( $options ) );

		$default_args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $options['max_posts_per_type'] ?? 50,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'no_found_rows'  => true,
			'fields'         => 'all',
		);

		// Allow filtering of query args
		$query_args = apply_filters( 'fidabr_content_crawler_query_args', $default_args, $post_type, $options );
		error_log( "[FIDABR ContentCrawler] Query args: " . json_encode( $query_args ) );

		$posts = get_posts( $query_args );
		error_log( "[FIDABR ContentCrawler] Raw posts found: " . count( $posts ) );

		if ( ! empty( $posts ) ) {
			error_log( "[FIDABR ContentCrawler] First post sample: ID={$posts[0]->ID}, title={$posts[0]->post_title}, status={$posts[0]->post_status}" );
		}

		// Filter posts that should be excluded
		$filtered_posts = array_filter( $posts, array( $this, 'should_include_post' ) );
		error_log( "[FIDABR ContentCrawler] Posts after filtering: " . count( $filtered_posts ) );

		if ( count( $posts ) !== count( $filtered_posts ) ) {
			error_log( "[FIDABR ContentCrawler] Posts excluded: " . ( count( $posts ) - count( $filtered_posts ) ) );
		}

		return $filtered_posts;
	}

	/**
	 * Extract content from a post
	 *
	 * @param \WP_Post $post Post object
	 * @param array    $options Extraction options
	 * @return string Extracted and processed content
	 */
	public function extract_post_content( $post, $options = array() ) {
		error_log( "[FIDABR ContentCrawler] Extracting content for post ID: {$post->ID}, title: {$post->post_title}" );
		error_log( "[FIDABR ContentCrawler] Post content length: " . strlen( $post->post_content ) );
		error_log( "[FIDABR ContentCrawler] Extract options: " . json_encode( $options ) );

		$content_parts = array();

		// Add title
		$content_parts[] = '# ' . $post->post_title;

		// Add metadata if requested
		if ( ! empty( $options['include_meta'] ) ) {
			$meta_content = $this->extract_post_meta( $post );
			$content_parts[] = $meta_content;
			error_log( "[FIDABR ContentCrawler] Added metadata: " . strlen( $meta_content ) . " chars" );
		}

		// Add excerpt if available
		if ( ! empty( $post->post_excerpt ) ) {
			$content_parts[] = '> ' . $post->post_excerpt;
			error_log( "[FIDABR ContentCrawler] Added excerpt: " . strlen( $post->post_excerpt ) . " chars" );
		}

		// Process main content
		$main_content = $this->process_post_content( $post->post_content );
		if ( ! empty( $main_content ) ) {
			$content_parts[] = $main_content;
			error_log( "[FIDABR ContentCrawler] Processed main content: " . strlen( $main_content ) . " chars" );
		} else {
			error_log( "[FIDABR ContentCrawler] WARNING: Main content is empty after processing" );
		}

		// Add taxonomies if requested
		if ( ! empty( $options['include_taxonomies'] ) ) {
			$taxonomy_content = $this->extract_post_taxonomies( $post );
			if ( ! empty( $taxonomy_content ) ) {
				$content_parts[] = $taxonomy_content;
				error_log( "[FIDABR ContentCrawler] Added taxonomies: " . strlen( $taxonomy_content ) . " chars" );
			}
		}

		// Add post URL as reference
		$permalink = get_permalink( $post );
		$content_parts[] = "\n---\n**Original URL:** " . $permalink;
		error_log( "[FIDABR ContentCrawler] Added permalink: $permalink" );

		$final_content = implode( "\n\n", array_filter( $content_parts ) );
		error_log( "[FIDABR ContentCrawler] Final content length: " . strlen( $final_content ) . " chars" );

		// Apply content filters
		$filtered_content = apply_filters( 'fidabr_extracted_post_content', $final_content, $post, $options );

		if ( strlen( $filtered_content ) !== strlen( $final_content ) ) {
			error_log( "[FIDABR ContentCrawler] Content modified by filters: " . strlen( $filtered_content ) . " chars" );
		}

		return $filtered_content;
	}

	/**
	 * Process post content (clean HTML, normalize text)
	 *
	 * @param string $content Raw post content
	 * @return string Processed content
	 */
	private function process_post_content( $content ) {
		if ( empty( $content ) ) {
			error_log( "[FIDABR ContentCrawler] WARNING: Empty content passed to process_post_content" );
			return '';
		}

		error_log( "[FIDABR ContentCrawler] Processing content, initial length: " . strlen( $content ) );

		// Apply WordPress content filters (shortcodes, etc.)
		$content = apply_filters( 'the_content', $content );
		error_log( "[FIDABR ContentCrawler] After WordPress filters: " . strlen( $content ) . " chars" );

		// Remove HTML tags but preserve structure
		$content = $this->clean_html_content( $content );
		error_log( "[FIDABR ContentCrawler] After HTML cleaning: " . strlen( $content ) . " chars" );

		// Normalize whitespace
		$content = $this->normalize_whitespace( $content );
		error_log( "[FIDABR ContentCrawler] After whitespace normalization: " . strlen( $content ) . " chars" );

		// Limit content length if needed
		$content = $this->limit_content_length( $content );
		error_log( "[FIDABR ContentCrawler] After length limiting: " . strlen( $content ) . " chars" );

		return $content;
	}

	/**
	 * Clean HTML content while preserving structure
	 *
	 * @param string $content HTML content
	 * @return string Cleaned content
	 */
	private function clean_html_content( $content ) {
		// Convert common HTML elements to markdown equivalents
		$replacements = array(
			'/<h([1-6]).*?>(.*?)<\/h[1-6]>/is'     => function( $matches ) {
				return str_repeat( '#', (int) $matches[1] ) . ' ' . trim( $matches[2] );
			},
			'/<p.*?>(.*?)<\/p>/is'                 => "$1\n\n",
			'/<br\s*\/?>/i'                        => "\n",
			'/<strong.*?>(.*?)<\/strong>/is'       => "**$1**",
			'/<b.*?>(.*?)<\/b>/is'                 => "**$1**",
			'/<em.*?>(.*?)<\/em>/is'               => "*$1*",
			'/<i.*?>(.*?)<\/i>/is'                 => "*$1*",
			'/<a.*?href=[\'"](.*?)[\'"].*?>(.*?)<\/a>/is' => "[$2]($1)",
			'/<ul.*?>(.*?)<\/ul>/is'               => "$1",
			'/<ol.*?>(.*?)<\/ol>/is'               => "$1",
			'/<li.*?>(.*?)<\/li>/is'               => "- $1\n",
		);

		foreach ( $replacements as $pattern => $replacement ) {
			if ( is_callable( $replacement ) ) {
				$content = preg_replace_callback( $pattern, $replacement, $content );
			} else {
				$content = preg_replace( $pattern, $replacement, $content );
			}
		}

		// Remove remaining HTML tags
		$content = wp_strip_all_tags( $content );

		return $content;
	}

	/**
	 * Normalize whitespace in content
	 *
	 * @param string $content Content to normalize
	 * @return string Normalized content
	 */
	private function normalize_whitespace( $content ) {
		// Replace multiple consecutive spaces with single space
		$content = preg_replace( '/[ \t]+/', ' ', $content );

		// Replace multiple consecutive newlines with maximum of two
		$content = preg_replace( '/\n\s*\n\s*\n/', "\n\n", $content );

		// Trim lines
		$lines   = explode( "\n", $content );
		$lines   = array_map( 'trim', $lines );
		$content = implode( "\n", $lines );

		return trim( $content );
	}

	/**
	 * Limit content length
	 *
	 * @param string $content Content to limit
	 * @param int    $max_words Maximum number of words (default: 500)
	 * @return string Limited content
	 */
	private function limit_content_length( $content, $max_words = 500 ) {
		$word_count = str_word_count( $content );

		if ( $word_count <= $max_words ) {
			return $content;
		}

		// Split content into words and take first $max_words
		$words   = preg_split( '/\s+/', $content );
		$limited = array_slice( $words, 0, $max_words );
		$content = implode( ' ', $limited );

		// Try to end at a sentence boundary
		$last_period = strrpos( $content, '.' );
		if ( $last_period !== false && $last_period > strlen( $content ) * 0.8 ) {
			$content = substr( $content, 0, $last_period + 1 );
		}

		$content .= "\n\n*[Content truncated]*";

		return $content;
	}

	/**
	 * Extract post metadata
	 *
	 * @param \WP_Post $post Post object
	 * @return string Formatted metadata
	 */
	private function extract_post_meta( $post ) {
		$meta_parts = array();

		// Add publish date
		$meta_parts[] = '**Published:** ' . get_the_date( 'Y-m-d', $post );

		// Add author
		$author = get_the_author_meta( 'display_name', $post->post_author );
		if ( ! empty( $author ) ) {
			$meta_parts[] = '**Author:** ' . $author;
		}

		// Add post type
		$post_type_obj = get_post_type_object( $post->post_type );
		if ( $post_type_obj ) {
			$meta_parts[] = '**Type:** ' . $post_type_obj->labels->singular_name;
		}

		return implode( ' | ', $meta_parts );
	}

	/**
	 * Extract post taxonomies
	 *
	 * @param \WP_Post $post Post object
	 * @return string Formatted taxonomy information
	 */
	private function extract_post_taxonomies( $post ) {
		$taxonomy_parts = array();

		// Get all taxonomies for this post type
		$taxonomies = get_object_taxonomies( $post->post_type, 'objects' );

		foreach ( $taxonomies as $taxonomy ) {
			// Skip built-in taxonomies we don't want to include
			if ( in_array( $taxonomy->name, array( 'post_format' ), true ) ) {
				continue;
			}

			$terms = get_the_terms( $post, $taxonomy->name );

			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$term_names = wp_list_pluck( $terms, 'name' );
				$taxonomy_parts[] = '**' . $taxonomy->labels->name . ':** ' . implode( ', ', $term_names );
			}
		}

		if ( empty( $taxonomy_parts ) ) {
			return '';
		}

		return "## Taxonomies\n\n" . implode( "\n", $taxonomy_parts );
	}

	/**
	 * Check if a post should be included in the llms.txt
	 *
	 * @param \WP_Post $post Post object
	 * @return bool True if post should be included
	 */
	private function should_include_post( $post ) {
		error_log( "[FIDABR ContentCrawler] Checking if post should be included: ID={$post->ID}, title={$post->post_title}" );

		// Skip password-protected posts
		if ( ! empty( $post->post_password ) ) {
			error_log( "[FIDABR ContentCrawler] Excluding post {$post->ID}: password protected" );
			return false;
		}

		// Skip posts marked as noindex (if using SEO plugins)
		if ( $this->is_post_noindex( $post ) ) {
			error_log( "[FIDABR ContentCrawler] Excluding post {$post->ID}: marked as noindex" );
			return false;
		}

		// Allow filtering
		$should_include = apply_filters( 'fidabr_should_include_post', true, $post );

		if ( ! $should_include ) {
			error_log( "[FIDABR ContentCrawler] Excluding post {$post->ID}: filtered out by 'fidabr_should_include_post' filter" );
		} else {
			error_log( "[FIDABR ContentCrawler] Including post {$post->ID}: passed all checks" );
		}

		return $should_include;
	}

	/**
	 * Check if post is marked as noindex by SEO plugins
	 *
	 * @param \WP_Post $post Post object
	 * @return bool True if post is noindex
	 */
	private function is_post_noindex( $post ) {
		// Check Yoast SEO
		if ( class_exists( 'WPSEO_Meta' ) ) {
			$noindex = \WPSEO_Meta::get_value( 'meta-robots-noindex', $post->ID );
			if ( $noindex === '1' ) {
				return true;
			}
		}

		// Check RankMath
		if ( class_exists( 'RankMath' ) ) {
			$noindex = get_post_meta( $post->ID, 'rank_math_robots', true );
			if ( is_array( $noindex ) && in_array( 'noindex', $noindex, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get available post types for llms.txt generation
	 *
	 * @return array Array of post type objects
	 */
	public function get_available_post_types() {
		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		error_log( "[FIDABR ContentCrawler] Found public post types: " . implode( ', ', array_keys( $post_types ) ) );

		// Remove attachment post type
		unset( $post_types['attachment'] );

		// Allow filtering
		$filtered_post_types = apply_filters( 'fidabr_available_post_types', $post_types );

		if ( count( $filtered_post_types ) !== count( $post_types ) ) {
			error_log( "[FIDABR ContentCrawler] Post types modified by filter: " . implode( ', ', array_keys( $filtered_post_types ) ) );
		}

		return $filtered_post_types;
	}

	/**
	 * Get content statistics
	 *
	 * @return array Content statistics
	 */
	public function get_content_stats() {
		error_log( "[FIDABR ContentCrawler] Getting content statistics" );

		$post_types = $this->get_available_post_types();
		$stats      = array();

		foreach ( $post_types as $post_type => $post_type_obj ) {
			$count = wp_count_posts( $post_type );
			$published_count = $count->publish ?? 0;
			$total_count = array_sum( (array) $count );

			$stats[ $post_type ] = array(
				'name'            => $post_type_obj->labels->name,
				'published_count' => $published_count,
				'total_count'     => $total_count,
			);

			error_log( "[FIDABR ContentCrawler] Stats for $post_type: {$published_count} published, {$total_count} total" );
		}

		error_log( "[FIDABR ContentCrawler] Content stats generated for " . count( $stats ) . " post types" );
		return $stats;
	}
}