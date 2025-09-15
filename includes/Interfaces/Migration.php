<?php

namespace FiloDataBrokerPlugin\Interfaces;

/**
 * Interface Migration
 *
 * Defines the contract for database migration operations.
 *
 * @package FiloDataBrokerPlugin\Interfaces
 */
interface Migration {

	/**
	 * Perform actions when migrating up.
	 *
	 * @return void
	 */
	public static function up();

	/**
	 * Perform actions when migrating down.
	 *
	 * @return void
	 */
	public static function down();
}
