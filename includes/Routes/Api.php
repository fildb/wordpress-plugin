<?php
/**
 * FiloDataBrokerPlugin Routes
 *
 * Defines and registers custom API routes for the FiloDataBrokerPlugin using the Haruncpi\WpApi library.
 *
 * @package FiloDataBrokerPlugin\Routes
 */

namespace FiloDataBrokerPlugin\Routes;

defined( 'ABSPATH' ) || exit;

use FiloDataBrokerPlugin\Libs\API\Route;

Route::prefix(
	FIDABR_PLUGIN_ROUTE_PREFIX,
	function ( Route $route ) {
		// Define accounts API routes.
		// $route->post( '/accounts/create', '\FiloDataBrokerPlugin\Controllers\Accounts\Actions@create' );
		// $route->get( '/accounts/get', '\FiloDataBrokerPlugin\Controllers\Accounts\Actions@get' );
		// $route->post( '/accounts/delete', '\FiloDataBrokerPlugin\Controllers\Accounts\Actions@delete' );
		// $route->post( '/accounts/update', '\FiloDataBrokerPlugin\Controllers\Accounts\Actions@update' );
		// Posts routes.
		// $route->get( '/posts/get', '\FiloDataBrokerPlugin\Controllers\Posts\Actions@get_all_posts' );
		// $route->get( '/posts/get/{id}', '\FiloDataBrokerPlugin\Controllers\Posts\Actions@get_post' );
		// LLM Generator routes.
		$route->get( '/llm/settings', '\FiloDataBrokerPlugin\Controllers\LLM\Actions@get_settings' );
		$route->post( '/llm/settings', '\FiloDataBrokerPlugin\Controllers\LLM\Actions@save_settings' );
		$route->get( '/llm/post-types', '\FiloDataBrokerPlugin\Controllers\LLM\Actions@get_post_types' );
		$route->get( '/llm/status', '\FiloDataBrokerPlugin\Controllers\LLM\Actions@get_status' );
		$route->get( '/llm/statistics', '\FiloDataBrokerPlugin\Controllers\LLM\Actions@get_statistics' );
		$route->post( '/llm/generate', '\FiloDataBrokerPlugin\Controllers\LLM\Actions@generate_file' );
		$route->post( '/llm/clear-metadata', '\FiloDataBrokerPlugin\Controllers\LLM\Actions@clear_metadata' );
		// Allow hooks to add more custom API routes.
		do_action( 'fidabr_plugin_api', $route );
	}
)->auth( function() {
	// Check if user is logged in
	if ( ! is_user_logged_in() ) {
		return false;
	}

	// Check if user has manage_options capability
	$user = wp_get_current_user();
	if ( ! $user || ! $user->has_cap( 'manage_options' ) ) {
		return false;
	}

	// Verify nonce for POST requests
	if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
		$nonce = isset( $_SERVER['HTTP_X_WP_NONCE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return false;
		}
	}

	return true;
});
