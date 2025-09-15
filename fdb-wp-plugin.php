<?php
/**
 * Plugin Name: FiloDataBroker Plugin
 * Description: WordPress plugin that automatically generates `llms.txt` file to help LLMs/AI systems better understand and navigate your website content.
 * Author: Denis Perov
 * Author URI: mailto:denis.perov@gmail.com
 * License: GPLv2
 * Version: 1.0.0
 * Text Domain: fdb-wp-plugin
 * Domain Path: /languages
 *
 * @package FiloDataBroker Plugin
 */

use FiloDataBrokerPlugin\Core\Install;

defined( 'ABSPATH' ) || exit;

require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
require_once plugin_dir_path( __FILE__ ) . 'plugin.php';

/**
 * Initializes the FiloDataBrokerPlugin plugin when plugins are loaded.
 *
 * @since 1.0.0
 * @return void
 */
function fdb_plugin_init() {
	FiloDataBrokerPlugin::get_instance()->init();
}

// Hook for plugin initialization.
add_action( 'plugins_loaded', 'fdb_plugin_init' );

// Hook for plugin activation.
register_activation_hook( __FILE__, array( Install::get_instance(), 'init' ) );
