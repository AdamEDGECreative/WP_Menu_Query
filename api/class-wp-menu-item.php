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
	 * The menu location this item was created for.
	 * @var string
	 */
	public $location;

	/**
	 * Whether this menu item is the currently queried page or not.
	 * @var boolean
	 */
	private $_current;

	/**
	 * Child items of this item. Stored as a menu query for easy iteration/access.
	 * @var WP_Menu_Query
	 */
	private $_children;

	/**
	 * Whether this menu item has a child item which is the currently queried page or not.
	 * @var boolean
	 */
	private $_has_current_child;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    WP_Post    $item The raw menu item.
	 */
	public function __construct( WP_Post $item, $location ) {
		$this->_raw_item = $item;
		$this->_map( $item );

		$this->location = $location;

		$this->_children = null;
		$this->_has_current_child = null;

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

	public function has_parent() {
		return 0 !== $this->parent;
	}

	public function get_children() {
		if ( null === $this->_children ) {
			$this->_get_children();
		}

		return $this->_children;
	}

	public function has_children() {
		if ( null === $this->_children ) {
			$this->_get_children();
		}

		return $this->_children->found_items > 0;
	}

	public function get_child_count() {
		if ( null === $this->_children ) {
			$this->_get_children();
		}

		return $this->_children->found_items;
	}

	public function has_current_child() {
		if ( null === $this->_has_current_child ) {
			$this->_has_current_child = $this->_has_current_child();
		}

		return $this->_has_current_child;
	}

	private function _has_current_child() {
		if ( null === $this->_children ) {
			$this->_get_children();
		}

		$match = false;

		foreach ($this->_children->items as $key => $item) {
			if ( $item->is_current() ) {
				$match = true;
				break;
			}
		}

		return $match;
	}

	private function _get_children() {
		$this->_children = new WP_Menu_Query(
			array( 
				'location' => $this->location,
				'parent' => $this->ID,
			)
		);
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
		$this->parent = (integer)$item->menu_item_parent;

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
		$this->object_id = (integer)$item->object_id;

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
