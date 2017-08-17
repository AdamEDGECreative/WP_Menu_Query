<?php

/**
 * The object that represents a single WordPress Menu Item.
 *
 * @link       https://github.com/AdamEDGECreative/WP_Menu_Query
 * @since      1.0.0
 *
 * @package    WP_Menu_Query
 * @subpackage WP_Menu_Query/api
 */

/**
 * The object that represents a single WordPress Menu Item.
 *
 * @package    WP_Menu_Query
 * @subpackage WP_Menu_Query/api
 * @author     Adam Taylor <adam@edge-creative.com>
 */
class WP_Menu_Item {

	/**
	 * The raw menu item data.
	 * @var WP_Post
	 */
	private $_raw_item;

	/**
	 * Whether this menu item is the currently queried page or not.
	 * @var boolean
	 */
	private $_current;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $item The raw menu item.
	 */
	public function __construct( WP_Post $item ) {
		$this->_raw_item = $item;
		$this->_map( $item );

		$this->set_current( apply_filters( 'wp_menu_query_item_is_current', $this->_is_queried_object(), $this ) );
	}

	private function _is_queried_object() {
		$qo = get_queried_object();

		$match = false;

		switch ( $this->type ) {
			case 'post_type':
				if ( is_a( $qo, 'WP_Post' ) ) {
					$match = $this->object_id == $qo->ID;
				}
				break;

			case 'post_type_archive':
				if ( is_a( $qo, 'WP_Post_Type' ) ) {
					$match = $this->object_id == $qo->name;
				}
				break;

			case 'taxonomy':
				if ( is_a( $qo, 'WP_Term' ) ) {
					$match = $this->object_id == $qo->term_id && $this->object == $qo->taxonomy;
				}
				break;

			case 'custom':			
			default:
				break;
		}

		// If no match found, try to match the current URL to the item's URL
		if ( !$match ) {
			$current_url = get_pagenum_link();
			$match = $current_url == $this->url;
		}

		return $match;
	}

	public function __get( $name ) {
		return $this->get_meta( $name );
	}

	public function get_meta( $name ) {
		return get_post_meta( $this->ID, $name, true );
	}

	public function is_current() {
		return $this->_current;
	}

	public function set_current( $current = true ) {
		$this->_current = $current;
	}

	private function _map( WP_Post $item ) {
		/**
		 * The menu item's ID.
		 * NOT the same as the post ID for post type items.
		 * @var integer
		 */
		$this->ID = $item->ID;

		// Standard properties

		/**
		 * The parent menu item ID
		 * @var integer
		 */
		$this->parent = $item->menu_item_parent;

		/**
		 * Type of object.
		 * Post type for posts, taxonomy name for terms.
		 * @var string
		 */
		$this->object = $item->object;
		
		/**
		 * The ID of the object relative to the object type.
		 * Post ID for posts, Term ID for terms.
		 * @var integer
		 */
		$this->object_id = $item->object_id;

		/**
		 * If the item is an archive, there will be no object ID.
		 * Set the object ID to the post type slug.
		 *
		 * If the item is a custom item, there will be no object ID.
		 * Set the object ID to the menu item's URL.
		 */
		if ( 'post_type_archive' === $item->type ) {
			$this->object_id = $item->object;
		}
		if ( 'custom' === $item->type ) {
			$this->object_id = $item->url;
		}
		
		/**
		 * The type of menu item.
		 * 'taxonomy' for terms, 'post_type' for posts, 
		 * 'post_type_archive' for archives, 'custom' for custom links.
		 * @var string
		 */
		$this->type = $item->type;
		
		/**
		 * The type of menu item as a label.
		 * e.g. 'Custom Link' for 'custom' types.
		 * @var string
		 */
		$this->type_label = $item->type_label;


		// Link properties

		/**
		 * The title chosen for the menu item.
		 * May be different to the post title as
		 * this is whatever has been typed in the Menus screen.
		 * @var string
		 */
		$this->title = $item->title;

		/**
		 * The URL of the menu item.
		 * @var string
		 */
		$this->url = esc_url( $item->url );
		
		/**
		 * The target attribute for the item.
		 * '_blank' if open in new window has been chosen.
		 * @var string
		 */
		$this->target = $item->target;
		
		/**
		 * Any additional classes added to the item in the Menus screen.
		 * @var array
		 */
		$this->classes = $item->classes;
		
		/**
		 * The description of the menu item.
		 * Each menu item can have a description added in the Menus screen/
		 * @var string
		 */
		$this->description = $item->description;

	}

}
