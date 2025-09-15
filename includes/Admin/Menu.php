<?php

namespace FiloDataBrokerPlugin\Admin;

use FiloDataBrokerPlugin\Traits\Base;

/**
 * Class Menu
 *
 * Represents the admin menu management for the FiloDataBrokerPlugin plugin.
 *
 * @package FiloDataBrokerPlugin\Admin
 */
class Menu {

	use Base;

	/**
	 * Parent slug for the menu.
	 *
	 * @var string
	 */
	private $parent_slug = 'wordpress-plugin-boilerplate';

	/**
	 * Initializes the admin menu.
	 *
	 * @return void
	 */
	public function init() {
		// Hook the function to the admin menu.
		add_action( 'admin_menu', array( $this, 'menu' ) );
	}

	/**
	 * Adds a menu to the WordPress admin dashboard.
	 *
	 * @return void
	 */
	public function menu() {

		add_menu_page(
			__( 'FiloDataBroker', 'fdb-wp-plugin' ),
			__( 'FiloDataBroker', 'fdb-wp-plugin' ),
			'manage_options',
			$this->parent_slug,
			array( $this, 'admin_page' ),
			'dashicons-media-text',
			3
		);

		$plugin_url = admin_url( '/admin.php?page=' . $this->parent_slug );

		$current_page = get_admin_page_parent();

		if ( $current_page === $this->parent_slug ) {
			$plugin_url = '';
		}

		$submenu_pages = array(
			array(
				'parent_slug' => $this->parent_slug,
				'page_title'  => __( 'Generator', 'fdb-wp-plugin' ),
				'menu_title'  => __( 'Generator', 'fdb-wp-plugin' ),
				'capability'  => 'manage_options',
				'menu_slug'   => $this->parent_slug,
				'function'    => array( $this, 'admin_page' ),
			),
		);

		$plugin_submenu_pages = apply_filters( 'fdbplugin_submenu_pages', $submenu_pages );

		foreach ( $plugin_submenu_pages as $submenu ) {

			add_submenu_page(
				$submenu['parent_slug'],
				$submenu['page_title'],
				$submenu['menu_title'],
				$submenu['capability'],
				$submenu['menu_slug'],
				$submenu['function']
			);
		}
	}

	/**
	 * Callback function for the main "FiloDataBroker" menu page.
	 *
	 * @return void
	 */
	public function admin_page() {
		?>
		<div class="wrap">
			<div id="fdb-admin-app"></div>
		</div>
		<?php
	}
}
