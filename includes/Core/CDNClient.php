<?php
/**
 * CDNClient - FiloDataBroker CDN HTTP API client
 *
 * @package FiloDataBrokerPlugin
 */

namespace FiloDataBrokerPlugin\Core;

defined( 'ABSPATH' ) || exit;

/**
 * CDNClient class for uploading content to FiloDataBroker CDN
 */
class CDNClient {

	/**
	 * CDN API endpoint URL
	 */
	const CDN_ENDPOINT = 'https://n8n.majus.org/webhook/70b814af-c541-4aed-b65f-7665413dffd6';

	/**
	 * Upload content to CDN
	 *
	 * @param string $content Content to upload
	 * @param string $filename Filename for the content
	 * @param string $site_id Site identifier
	 * @return array|WP_Error Response with CDN URL or error
	 */
	public function upload_content( $content, $filename, $site_id = '' ) {
		if ( empty( $site_id ) ) {
			$site_id = $this->get_site_id();
		}

		// Create multipart boundary
		$boundary = wp_generate_uuid4();

		// Build multipart form data body
		$body = '';
		
		// Add file field
		$body .= "--{$boundary}\r\n";
		$body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$filename}\"\r\n";
		$body .= "Content-Type: text/plain\r\n\r\n";
		$body .= $content . "\r\n";
		
		// Add site_id field
		$body .= "--{$boundary}\r\n";
		$body .= "Content-Disposition: form-data; name=\"site_id\"\r\n\r\n";
		$body .= $site_id . "\r\n";
		
		// End boundary
		$body .= "--{$boundary}--\r\n";

		$args = array(
			'method'      => 'POST',
			'timeout'     => 30,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking'    => true,
			'headers'     => array(
				'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
				'User-Agent'   => 'fdb-wp-plugin/' . FDBPLUGIN_VERSION,
				'Authorization' => 'Bearer *****', // Placeholder token
			),
			'body'        => $body,
		);

		error_log('[CDN Client] Uploading to CDN: ' . self::CDN_ENDPOINT);

		$response = wp_remote_post( self::CDN_ENDPOINT, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code !== 200 ) {
			return new \WP_Error(
				'cdn_upload_failed',
				sprintf( 'CDN upload failed with status %d: %s', $response_code, $response_body )
			);
		}

		// Parse JSON response - expect valid JSON with URL field
		$decoded_response = json_decode( $response_body, true );
		
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new \WP_Error(
				'cdn_invalid_response',
				'CDN response is not valid JSON: ' . json_last_error_msg()
			);
		}

		if ( ! isset( $decoded_response['url'] ) ) {
			return new \WP_Error(
				'cdn_missing_url',
				'CDN response missing required URL field'
			);
		}

		$cdn_url = $decoded_response['url'];

		error_log('[CDN Client] URL: ' . $cdn_url);

		return array(
			'success' => true,
			'url'     => $cdn_url,
			'site_id' => $site_id,
		);
	}

	/**
	 * Get site identifier
	 *
	 * @return string Site identifier
	 */
	private function get_site_id() {
		// Generate site ID based on domain and path
		$site_url = get_site_url();
		$parsed   = wp_parse_url( $site_url );
		$host     = $parsed['host'] ?? 'localhost';
		$path     = $parsed['path'] ?? '';

		// Clean and create identifier
		$site_id = sanitize_title( $host . $path );
		if ( empty( $site_id ) ) {
			$site_id = 'site_' . wp_hash( $site_url );
		}

		return $site_id;
	}

	/**
	 * Batch upload multiple content items
	 *
	 * @param array $content_items Array of content items with 'content', 'filename' keys
	 * @param string $site_id Site identifier
	 * @return array Results array with success/error for each item
	 */
	public function batch_upload( $content_items, $site_id = '' ) {
		$results = array();

		foreach ( $content_items as $index => $item ) {
			if ( ! isset( $item['content'] ) || ! isset( $item['filename'] ) ) {
				$results[ $index ] = new \WP_Error(
					'invalid_item',
					'Content item missing required fields'
				);
				continue;
			}

			$result = $this->upload_content(
				$item['content'],
				$item['filename'],
				$site_id
			);

			$results[ $index ] = $result;

			// Add small delay between uploads to avoid overwhelming the CDN
			if ( count( $content_items ) > 1 ) {
				usleep( 100000 ); // 0.1 second
			}
		}

		return $results;
	}

	/**
	 * Test CDN connectivity
	 *
	 * @return bool|WP_Error True if connection successful, WP_Error otherwise
	 */
	public function test_connection() {
		$test_content = 'Test connection from FiloDataBroker Plugin - ' . current_time( 'mysql' );
		$test_filename = 'test-connection-' . time() . '.txt';

		$result = $this->upload_content( $test_content, $test_filename );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return true;
	}

}