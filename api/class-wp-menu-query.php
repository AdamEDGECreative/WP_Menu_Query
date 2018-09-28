<?php

/**
 * The object that represents a query against a WordPress created menu.
 *
 * @link       https://github.com/AdamEDGECreative/WP_Menu_Query
 * @since      1.0.0
 *
 * @package    WP_Menu_Query
 * @subpackage WP_Menu_Query/api
 */

/**
 * The object that represents a query against a WordPress created menu.
 *
 * Allows developers to query a set of menu items based
 * on location, menu they are attached to etc.
 *
 * @package    WP_Menu_Query
 * @subpackage WP_Menu_Query/api
 * @author     Adam Taylor <adam@edge-creative.com>
 */
class WP_Menu_Query {

	/**
	 * The query args passed to the class.
	 * @var array
	 */
	public $query_vars;

	/**
	 * Filled with WP_Menu_Items when the items are fetched.
	 * @var array
	 */
	public $items;

	/**
	 * The total number of items found.
	 * @var array
	 */
	public $item_count;
	/**
	 * Alias of the number of items found.
	 * This class has no paging so these are equivalent.
	 */
	public $found_items;

	/**
	 * The index of the current item being displayed.
	 * Available during the loop.
	 * @var integer
	 */
	public $current_item;

	/**
	 * The current item being displayed.
	 * Available during the loop.
	 * @var WP_Menu_Item
	 */
	public $item;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    array    $args Optional, A set of query args to initialise the query.
	 *                          Defaults to null so that the class can be instantiated 
	 *                          without args, query vars set and then items fetched.
	 */
	public function __construct( $args = null ) {
		$this->_location_error_depth = 3;

		if ( null !== $args ) {
			$this->_location_error_depth = 4;
			$this->query( $args );
		}
	}

	private function _init_query_vars( $args ) {
		$defaults = $this->_get_default_args();

		$query_vars = wp_parse_args( $args, $defaults );
		$query_vars = apply_filters( 'wp_menu_query_vars', $query_vars );

		$this->query_vars = $query_vars;
	}

	private function _get_default_args() {
		return array(
			/**
			 * The location the menu is attached to.
			 * @var string
			 */
			'location' => '',
			/**
			 * Specific menu items to include. Only these items 
			 * (if they exist in the menu) will be output.
			 * 
			 * Pass a URL to be matched against each menu item.
			 * Relative URLs are valid and will be mapped relative to the home URL.
			 * @var string 
			 */
			'include' => array(),
			/**
			 * Specific menu items to exclude. Any matching items will be removed.
			 * 
			 * Pass a URL to be matched against each menu item.
			 * Relative URLs are valid and will be mapped relative to the home URL.
			 */
			'exclude' => array(),
			/**
			 * Limit the amount of top level menu items returned.
			 * Use -1 to show all menu items.
			 * @var integer
			 */
			'limit' => -1,
			/**
			 * Limit the amount of child menu items returned.
			 * Use -1 to show all child menu items.
			 * @var integer
			 */
			'limit_children' => -1,
			/**
			 * The number of items to skip from the start of the menu before output.
			 * Will only be applied to top level items (parent == 0)
			 * @var integer
			 */
			'offset' => 0,
			/**
			 * Pass a specific URL or a menu item's ID 
			 * to get child items for that URL or ID.
			 *
			 * Absolute or relative URLs are both valid.
			 * Relative URLs will be mapped relative to the home URL.
			 * @var string|integer
			 */
			'parent' => 0,
		);
	}

	public function get( $var_name ) {
		return $this->query_vars[ $var_name ];
	}

	public function set( $var_name, $value ) {
		$this->query_vars[ $var_name ] = $value;
	}

	/**
	 * Define whether or not there are currently items 
	 * left to process while in the loop.
	 * @return boolean
	 */
	public function have_items() {
		return $this->current_item < count( $this->items );
	}

	/**
	 * Move to the next item and return it to the caller.
	 * Used in the loop.
	 * @return WP_Menu_Item The next item.
	 */
	public function the_item() {
		if ( isset( $this->items[ $this->current_item ] ) ) {
			$this->item = $this->items[ $this->current_item ];
			$this->current_item++;

			return $this->item;
		}

		return null;
	}

	/**
	 * Reset the internal pointers back to the start.
	 */
	public function rewind_items() {
		$this->current_item = 0;
		$this->item = null;
	}
	// Alias
	public function reset_items() {
		$this->rewind_items();
	}

	public function query( $args = null ) {
		if ( null !== $args ) {
			
			// Add any currently set vars to the args passed
			$args = wp_parse_args( $args, $this->query_vars );

			$this->_init_query_vars( $args );

		}

		$this->_fetch();
	}

	/**
	 * Fetches the menu items based on the args defined.
	 */
	private function _fetch() {
		if ( !$this->_check_location() ) {
			return;
		}

		// Reset class properties
		$this->rewind_items();
		$this->items = array();

		// Get the items
		$menu = $this->get_menu();
		$this->items = $this->_get_menu_items( $menu );

		$this->_filter_parent_arg();

		// Filter the items array to only include child items of the parent 
		// if parent option was passed.
		$this->items = array_values( array_filter( $this->items, array( $this, '_filter_to_parent' ) ) );

		$this->_map_items_to_objects();

		$this->_apply_limits();

		// Update class properties with number found
		$this->_update_counts();
	}

	private function _check_location() {
		$location = $this->get( 'location' );

		if ( 
			!isset( $location ) || 
			!$location || 
			'' == $location 
		) {
			trigger_error_with_context( "Location is a required key and must be set", E_USER_ERROR, $this->_location_error_depth );
			return false;
		}

		if ( !$this->_location_is_registered() ) {
			
			trigger_error_with_context( "The location '$location' is not registered", E_USER_WARNING, $this->_location_error_depth );
			return false;

		} elseif ( !$this->_location_has_menu() ) {
		
			trigger_error_with_context( "The location '$location' does not have an attached menu", E_USER_WARNING, $this->_location_error_depth );
			return false;

		}

		return true;
	}

	private function _location_is_registered() {
		return WP_Menu_Location::location_is_registered( $this->get( 'location' ) );
	}

	private function _location_has_menu() {
		return WP_Menu_Location::location_has_menu( $this->get( 'location' ) );
	}

	/**
	 * Get a WP_Menu object representing the menu currently queried.
	 * @return WP_Menu 
	 */
	public function get_menu() {
		$menu_location = $this->get( 'location' );

		$query_cache = WP_Menu_Query_Cache::get_instance();
		return $query_cache->get_location_term( $menu_location );
	}

	private function _get_menu_items( WP_Menu $menu ) {
		$query_cache = WP_Menu_Query_Cache::get_instance();
		return $query_cache->get_items( $menu->term_id );
	}

	private function _filter_parent_arg() {
		// Replace the parent with an ID if a URL was passed
		if ( $this->_parent_is_url() ) {
			$parent = $this->get( 'parent' );

			// Make the parent URL relative to the home page URL if not absolute
			$parent = $this->_filter_url( $parent );

			$this->set( 'parent', $this->_find_parent( $parent ) );
		}
	}

	private function _parent_is_url() {
		$_parent = $this->get( 'parent' );

		return is_string( $_parent );
	}

	private function _find_parent( $url ) {
		$match = 0;

		foreach ($this->items as $key => $item) {
			if ( $this->_item_matches_url( $item, $url ) ) {
				$match = $item->ID;
				break;
			}
		}

		return $match;
	}

	private function _filter_to_parent( $item ) {
		$parent = 0;

		if ( $this->get( 'parent' ) > 0 && is_numeric( $this->get( 'parent' ) ) ) {
			
			// If parent is an ID use it directly
			$parent = $this->get( 'parent' );

			// If not, use 0 as the default

		}

		return $parent === (integer)$item->menu_item_parent;
	}

	private function _map_items_to_objects() {
		// Map the items to corresponding WP_Menu_Items
		foreach ($this->items as $key => &$item) {
			// _filter_item will return the mapped item, or false if invalid
			$item = $this->_filter_item( $item );
			unset( $item );
		}

		// Remove any invalid items & re index the array
		$this->items = array_values( array_filter( $this->items ) );
	}

	private function _filter_item( $item ) {
		// Convert the item to a WP_Menu_Item
		$item = new WP_Menu_Item( $item, $this->query_vars );

		/**
		 * Check include conditions if passed.
		 * Returns false if the item matches one in the include array.
		 */
		if ( count( $this->get( 'include' ) ) ) {
			
			$_include = array();

			foreach ($this->get( 'include' ) as $key => $url) {
				
				$url = $this->_filter_url( $url );

				if ( $this->_item_matches_url( $item, $url ) ) {
					$_include[] = 1;
				} else {
					$_include[] = 0;
				}

			}

			if ( !array_filter( $_include ) ) {
				return false;					
			}

		}

		/**
		 * Check exclude conditions if passed.
		 * Returns false if item matches one in the exclude array.
		 */
		if ( count( $this->get( 'exclude' ) ) ) {
			
			$_exclude = array();

			foreach ($this->get( 'exclude' ) as $key => $url) {
				
				$url = $this->_filter_url( $url );

				if ( $this->_item_matches_url( $item, $url ) ) {
					$_exclude[] = 1;
				} else {
					$_exclude[] = 0;
				}

			}

			if ( array_filter( $_exclude ) ) {
				return false;					
			}

		}

		return $item;
	}

	private function _filter_url( $url ) {
		// Check for relative URLs and make them relative to the home URL
		// Checked by detecting links that do not start with http:// or https://
		$url_start_pattern = '#^https?://#';

		if ( !preg_match( $url_start_pattern, $url ) ) {
			$url = trailingslashit( home_url( $url ) );
		}

		return $url;
	}

	private function _item_matches_url( $item, $url ) {
		return $item->url == $url;
	}

	private function _apply_limits() {
		// Apply limit and offset if passed
		$limit = count( $this->items );

		if ( 0 == $this->get( 'parent' ) ) {
			
			// Use 'limit' arg for top level items
			if ( $this->get( 'limit' ) > -1 && is_numeric( $this->get( 'limit' ) ) ) {
				$limit = (integer)$this->get( 'limit' );
			}

		} else {

			// Use 'limit_children' arg for child items
			if ( $this->get( 'limit_children' ) > -1 && is_numeric( $this->get( 'limit_children' ) ) ) {
				$limit = (integer)$this->get( 'limit_children' );
			}

		}

		$this->items = array_slice( $this->items, $this->get( 'offset' ), $limit );
	}

	private function _update_counts() {
		$this->item_count = count( $this->items );
		$this->found_items = $this->item_count;
	}

}
