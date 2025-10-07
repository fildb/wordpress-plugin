<?php

declare(strict_types=1);

namespace FiloDataBrokerPlugin\Assets;

defined( 'ABSPATH' ) || exit;

use FiloDataBrokerPlugin\Core\Template;
use FiloDataBrokerPlugin\Traits\Base;
use FiloDataBrokerPlugin\Libs\Assets;

/**
 * Class Admin
 *
 * Handles admin functionalities for the FiloDataBrokerPlugin.
 *
 * @package FiloDataBrokerPlugin\Admin
 */
class Admin {

	use Base;

	/**
	 * Script handle for FiloDataBrokerPlugin.
	 */
	const HANDLE = 'fidabr';

	/**
	 * JS Object name for FiloDataBrokerPlugin.
	 */
	const OBJ_NAME = 'fidabrAdmin';

	/**
	 * Development script path for FiloDataBrokerPlugin.
	 */
	const DEV_SCRIPT = 'src/admin/main.jsx';

	/**
	 * List of allowed screens for script enqueue.
	 *
	 * @var array
	 */
	private $allowed_screens = array(
		'toplevel_page_fidabr',
	);

	/**
	 * Frontend bootstrapper.
	 *
	 * @return void
	 */
	public function bootstrap() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_script' ) );
	}

	/**
	 * Enqueue script based on the current screen.
	 *
	 * @param string $screen The current screen.
	 */
	public function enqueue_script( $screen ) {
		$current_screen     = $screen;
		$template_file_name = Template::FRONTEND_TEMPLATE;

		if ( ! is_admin() ) {
			$template_slug = get_page_template_slug();
			if ( $template_slug ) {

				if ( $template_slug === $template_file_name ) {
					array_push( $this->allowed_screens, $template_file_name );
					$current_screen = $template_file_name;
				}
			}
		}

		if ( in_array( $current_screen, $this->allowed_screens, true ) ) {
			Assets\enqueue_asset(
				FIDABR_PLUGIN_DIR . '/assets/admin/dist',
				self::DEV_SCRIPT,
				$this->get_config()
			);
			wp_localize_script( self::HANDLE, self::OBJ_NAME, $this->get_data() );
		}
	}

	/**
	 * Get the script configuration.
	 *
	 * @return array The script configuration.
	 */
	public function get_config() {
		return array(
			'dependencies' => array( 'react', 'react-dom' ),
			'handle'       => self::HANDLE,
			'in-footer'    => true,
		);
	}

	/**
	 * Get data for script localization.
	 *
	 * @return array The localized script data.
	 */
	public function get_data() {

		return array(
			'nonce'   => wp_create_nonce( 'wp_rest' ),
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'isAdmin' => is_admin(),
			'apiUrl'  => rest_url( FIDABR_PLUGIN_ROUTE_PREFIX . '/' ),
			'userInfo'  => $this->get_user_data(),
		);
	}

	/**
	 * Get user data for script localization.
	 *
	 * @return array The user data.
	 */
	private function get_user_data() {
		$username   = '';
		$avatar_url = '';

		if ( is_user_logged_in() ) {
			// Get current user's data .
			$current_user = wp_get_current_user();

			// Get username.
			$username = $current_user->user_login; // or use user_nicename, display_name, etc.

			// Get avatar URL.
			$avatar_url = get_avatar_url( $current_user->ID );
		}

		return array(
			'username' => $username,
			'avatar'   => $avatar_url,
		);
	}
}
