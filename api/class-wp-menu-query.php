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
		if ( null !== $args ) {
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

		/**
		 * For the below documentation, the term 
		 * item_type_array refers to an array with type and id keys.
		 * With these 2 keys we can match any menu item specifically.
		 *
		 * @param string 			 	 'type' The type of object. 
		 *                      			  A valid post type slug, taxonomy slug or 'custom'.
		 *                      			  For post type archives, pass the post type slug.
		 * @param integer|string 'id' 	The ID of the post or taxonomy term to include.
		 *                             	For post type archives should be the post type slug.
		 *                              For custom links should be set to the URL of the link.
		 *
		 * Examples:
		 *
		 * array(
		 * 	 'type' => 'post',
		 * 	 'id' 	=> 1,
		 * );
		 * 
		 * array(
		 * 	 'type' => 'post',
		 * 	 'id' 	=> 'post',
		 * );
		 * 
		 * array(
		 * 	 'type' => 'custom',
		 * 	 'id'   => 'http://example.com',
		 * );
		 */

		return array(
			/**
			 * The location the menu is attached to.
			 * @var string
			 */
			'location' => '',
			/**
			 * Specific menu items to include. Only these items 
			 * (if they exist in the menu) will be output.
			 * Specify them as an array of item_type_arrays
			 */
			'include' => array(),
			/**
			 * Specific menu items to exclude. Any matching items will be removed.
			 * Specify them as an array of item_type_arrays
			 */
			'exclude' => array(),
			/**
			 * Limit the amount of menu items returned.
			 * Use -1 to show all menu items.
			 * @var integer
			 */
			'limit' => -1,
			/**
			 * The number of items to skip from the start of the menu before output.
			 * Will only applied to top level items (parent == 0)
			 * @var integer
			 */
			'offset' => 0,
			/**
			 * Whether to include child menu items or not.
			 * @var boolean
			 */
			'include_children' => true,
			/**
			 * Pass a specific item_type_array to get child items for that item.
			 * @var array
			 */
			'parent' => 0,
		);
	}

	private function _location_is_registered() {
		$registered_locations = get_registered_nav_menus();
		
		return isset( $registered_locations[ $this->get( 'location' ) ] );
	}

	private function _location_has_menu() {
		$menu_locations = get_nav_menu_locations();
		
		return isset( $menu_locations[ $this->get( 'location' ) ] );
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
		// echo '<pre style="text-align:left;">'; 
		// var_dump( $this->current_item, $this->items ); 
		// echo '</pre>';
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
		$items = wp_get_nav_menu_items( $menu->term_id );

		// Map the items to corresponding WP_Menu_Items
		foreach ($items as $key => $item) {
			// _filter_item will return the mapped item, or false if invalid
			$this->items[] = $this->_filter_item( $item );
		}

		// Remove any invalid items & re index the array
		$this->items = array_values( array_filter( $this->items ) );

		// Apply limit and offset if passed
		$limit = count( $this->items );
		if ($this->get( 'limit' ) > -1 && is_numeric( $this->get( 'limit' ) )  ) {
			$limit = (integer)$this->get( 'limit' );
		}

		$this->items = array_slice( $this->items, $this->get( 'offset' ), $limit );

		// Update class properties with amount found
		$this->item_count = count( $this->items );
		$this->found_items = $this->item_count;
	}

	private function _check_location() {
		$location = $this->get( 'location' );

		if ( 
			!isset( $location ) || 
			!$location || 
			'' == $location 
		) {
			trigger_error_with_context( "Location is a required key and must be set", E_USER_ERROR, 4 );
		}

		if ( !$this->_location_is_registered() ) {
			
			trigger_error_with_context( "The location '$location' is not registered", E_USER_WARNING, 4 );

		} elseif ( !$this->_location_has_menu() ) {
		
			trigger_error_with_context( "The location '$location' does not have an attached menu", E_USER_WARNING, 4 );

		}

		return true;
	}

	private function _filter_item( $item ) {
		// Convert the item to a WP_Menu_Item
		$item = new WP_Menu_Item( $item );

		/**
		 * Check include conditions if passed.
		 * Returns false if the item matches one in the include array.
		 */
		if ( count( $this->get( 'include' ) ) ) {
			
			$_include = array();

			foreach ($this->get( 'include' ) as $key => $item_type_array) {
				
				if ( $this->_item_matches_type_array( $item, $item_type_array ) ) {
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

			foreach ($this->get( 'exclude' ) as $key => $item_type_array) {
				
				if ( $this->_item_matches_type_array( $item, $item_type_array ) ) {
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

	private function _item_matches_type_array( $item, $item_type_array ) {
		return (
			$item->object == $item_type_array['type'] &&
			$item->object_id == $item_type_array['id']
		);
	}

	/**
	 * Get a WP_Menu object representing the menu currently queried.
	 * @return WP_Menu 
	 */
	public function get_menu() {
		$menu_location = $this->get( 'location' );
		return new WP_Menu( $menu_location );
	}

}
