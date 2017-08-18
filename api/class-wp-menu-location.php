<?php
	
/**
 * The object that represents a menu location within WordPress.
 *
 * @link       https://github.com/AdamEDGECreative/WP_Menu_Query
 * @since      1.0.0
 *
 * @package    WP_Menu_Query
 * @subpackage WP_Menu_Query/api
 */

/**
 * The object that represents a menu location within WordPress.
 *
 * Has static functionality to check locations exist and
 * throw appropriate exceptions/warnings if they don't.
 *
 * @package    WP_Menu_Query
 * @subpackage WP_Menu_Query/api
 * @author     Adam Taylor <adam@edge-creative.com>
 */
class WP_Menu_Location {

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

	public static function get_menu_from_location( $location ) {
		$menu_locations = get_nav_menu_locations();

		if ( isset( $menu_locations[ $location ] ) ) {
			return wp_get_nav_menu_object( $menu_locations[ $location ] );
		}

		return null;
	}

	public static function location_has_menu( $location ) {
		$menu_locations = get_nav_menu_locations();
		
		return isset( $menu_locations[ $location ] );
	}

	public static function location_is_registered( $location ) {
		$registered_locations = get_registered_nav_menus();
		
		return isset( $registered_locations[ $location ] );
	}

}
