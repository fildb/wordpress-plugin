<?php
/**
 * FiloDataBrokerPlugin Routes
 *
 * Defines and registers custom API routes for the FiloDataBrokerPlugin using the Haruncpi\WpApi library.
 *
 * @package FiloDataBrokerPlugin\Routes
 */

namespace FiloDataBrokerPlugin\Routes;

use FiloDataBrokerPlugin\Libs\API\Route;

Route::prefix(
	FDBPLUGIN_ROUTE_PREFIX,
	function ( Route $route ) {

		// Define accounts API routes.

		$route->post( '/accounts/create', '\FiloDataBrokerPlugin\Controllers\Accounts\Actions@create' );
		$route->get( '/accounts/get', '\FiloDataBrokerPlugin\Controllers\Accounts\Actions@get' );
		$route->post( '/accounts/delete', '\FiloDataBrokerPlugin\Controllers\Accounts\Actions@delete' );
		$route->post( '/accounts/update', '\FiloDataBrokerPlugin\Controllers\Accounts\Actions@update' );

		// Posts routes.
		$route->get( '/posts/get', '\FiloDataBrokerPlugin\Controllers\Posts\Actions@get_all_posts' );
		$route->get( '/posts/get/{id}', '\FiloDataBrokerPlugin\Controllers\Posts\Actions@get_post' );
		// Allow hooks to add more custom API routes.
		do_action( 'fdbplugin_api', $route );
	}
);
