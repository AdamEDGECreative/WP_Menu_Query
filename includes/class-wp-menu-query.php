<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://github.com/AdamEDGECreative/WP_Menu_Query
 * @since      1.0.0
 *
 * @package    WP_Menu_Query
 * @subpackage WP_Menu_Query/includes
 */

if ( !function_exists( 'trigger_error_with_context' ) ) {
	
	/**
	 * Trigger error and display where the current function was called from.
	 * Allows for more useful errors as by default the filenames and line numbers 
	 * are reported from the class or function that calls trigger_error();
	 * @param  string  $message The error message.
	 * @param  integer $level   The designated error type for this error. 
	 *                          It only works with the E_USER family of constants, 
	 *                          and will default to E_USER_NOTICE.
	 * @param integer $depth  	The number of levels up the backtrace that should be reported. 
	 */
	function trigger_error_with_context($message, $level=E_USER_NOTICE, $depth = 1 ) { 
		$backtrace = debug_backtrace();

		for ($i=0; $i < $depth; $i++) { 
		  $caller = next($backtrace); 
		}

	  $caller_function = $caller['function'];
	  if ( '__construct' === $caller_function && isset( $caller['class'] ) ) {
	  	$caller_function = 'new ' . $caller['class'] . '()';
	  } elseif ( isset( $caller['class'] ) ) {
	  	$caller_function = $caller['class'] . ':' . $caller_function;
	  }

	  $caller_file = $caller['file'];
	  $caller_line = $caller['line'];

	  $message = sprintf( 
	  	'%s in <b>%s</b> called from <b>%s</b> on line <b>%s</b> -- reported by custom error handler',
	  	$message,
	  	$caller_function,
	  	$caller_file,
	  	$caller_line
	  );

	  trigger_error( $message, $level ); 
	} 

}

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WP_Menu_Query
 * @subpackage WP_Menu_Query/includes
 * @author     Adam Taylor <adam@edge-creative.com>
 */
class _WP_Menu_Query {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WP_Menu_Query_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'wp-menu-query';
		$this->version = '1.0.0';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - WP_Menu_Query_Loader. Orchestrates the hooks of the plugin.
	 * - WP_Menu_Query_i18n. Defines internationalization functionality.
	 * - WP_Menu_Query_Admin. Defines all hooks for the admin area.
	 * - WP_Menu_Query_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-menu-query-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-menu-query-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-menu-query-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wp-menu-query-public.php';

		/**
		 * The API classes designed to be usable by the developer.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'api/class-wp-menu.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'api/class-wp-menu-query.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'api/class-wp-menu-item.php';

		$this->loader = new WP_Menu_Query_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the WP_Menu_Query_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new WP_Menu_Query_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new WP_Menu_Query_Admin( $this->get_plugin_name(), $this->get_version() );

		/**
		 * No assets to load at the moment.
		 */
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		// $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new WP_Menu_Query_Public( $this->get_plugin_name(), $this->get_version() );

		/**
		 * No assets to load at the moment.
		 */
		// $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		// $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    WP_Menu_Query_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
