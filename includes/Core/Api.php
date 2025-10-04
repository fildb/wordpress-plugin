<?php

namespace FiloDataBrokerPlugin\Core;

use FiloDataBrokerPlugin\Traits\Base;
use FiloDataBrokerPlugin\Libs\API\Config;

/**
 * Class API
 *
 * Initializes and configures the API for the FiloDataBrokerPlugin.
 *
 * @package FiloDataBrokerPlugin\Core
 */
class API {

	use Base;

	/**
	 * Initializes the API for the FiloDataBrokerPlugin.
	 *
	 * @return void
	 */
	public function init() {
		Config::set_route_file( FIDABR_PLUGIN_DIR . '/includes/Routes/Api.php' )
			->set_namespace( 'FiloDataBrokerPlugin\Api' )
			->init();
	}
}
