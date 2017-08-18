<?php
	
/**
 * The cache store for menu items used by WP_Menu_Query.
 *
 * @link       https://github.com/AdamEDGECreative/WP_Menu_Query
 * @since      1.0.0
 *
 * @package    WP_Menu_Query
 * @subpackage WP_Menu_Query/api
 */

/**
 * The cache store for menu items used by WP_Menu_Query.
 *
 * Stores menu items by location and allows for easy
 * retrieval of them over the WordPress lifecycle without
 * having to hit the database.
 *
 * @package    WP_Menu_Query
 * @subpackage WP_Menu_Query/api
 * @author     Adam Taylor <adam@edge-creative.com>
 */
class WP_Menu_Query_Cache {

	/**
	 * The location term cache.
	 * Stores each location term object 
	 * keyed by the location string.
	 * @var array
	 */
	private $_location_cache;

	/**
	 * The menu item cache store.
	 * Stores each array of menu items 
	 * keyed by the menu terms term_id.
	 * @var array
	 */
	private $_item_cache;

	/**
	 * The singleton instance of this class.
	 * @var WP_Menu_Query_Cache
	 */
	protected static $_instance;

	/**
	 * Declare constructor as protected to prevent
	 * new instances being created from outside.
	 */
	protected function __construct() {}

	/**
	 * Declare magic method __clone to be private
	 * to prevent instances being cloned.
	 */
	private function __clone() {}

	/**
	 * Declare magic method __wakeup as private
	 * to prevent new instances from unserialize()
	 */
	private function __wakeup() {}

	public static function get_instance() {
		if ( !isset( static::$_instance ) ) {
			static::$_instance = new static;
		}

		return static::$_instance;
	}

	public function get_location_term( $location ) {
		// Check cache first
		if ( isset( $this->_location_cache[ $location ] ) ) {
			return $this->_location_cache[ $location ];
		}

		$location_term = new WP_Menu( $location );

		// Store the object into the cache
		$this->_location_cache[ $location ] = $location_term;

		return $location_term;
	}

	public function get_items( $menu_term_id ) {
		// Check cache first
		if ( isset( $this->_item_cache[ $menu_term_id ] ) ) {
			return $this->_item_cache[ $menu_term_id ];
		}

		// Get items
		$items = wp_get_nav_menu_items( $menu_term_id );

		// Store items into the cache
		$this->_item_cache[ $menu_term_id ] = $items;

		return $items;
	}

}
