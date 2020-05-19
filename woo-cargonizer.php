<?php
/**
 * Plugin Name: Cargonizer integration for WooCommerce
 * Plugin URI: https://jaed.pro/
 * Description: Integrate your store with Cargonizer
 * Version: 1.0.1
 * Author: Jaed Mosharraf
 * Text Domain: woo-cargonizer
 * Domain Path: /languages/
 * Author URI: https://jaed.pro/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

defined( 'ABSPATH' ) || exit;
defined( 'WOOCNGR_PLUGIN_URL' ) || define( 'WOOCNGR_PLUGIN_URL', WP_PLUGIN_URL . '/' . plugin_basename( dirname( __FILE__ ) ) . '/' );
defined( 'WOOCNGR_PLUGIN_DIR' ) || define( 'WOOCNGR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
defined( 'WOOCNGR_PLUGIN_FILE' ) || define( 'WOOCNGR_PLUGIN_FILE', plugin_basename( __FILE__ ) );
defined( 'WOOCNGR_VERSION' ) || define( 'WOOCNGR_VERSION', '1.0.0' );
defined( 'WOOCNGR_TD' ) || define( 'WOOCNGR_TD', 'woo-cargonizer' );


if ( ! class_exists( 'wooCargonizer' ) ) {
	/**
	 * Class wooCargonizer
	 */
	class wooCargonizer {


		/**
		 * wooCargonizer constructor.
		 */
		function __construct() {

			$this->load_scripts();
			$this->define_classes_functions();

			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		}

		/**
		 * Loading TextDomain
		 */
		function load_textdomain() {

			load_plugin_textdomain( 'woo-cargonizer', false, plugin_basename( dirname( __FILE__ ) ) . '/languages/' );
		}


		/**
		 * Loading classes and functions
		 */
		function define_classes_functions() {

			require_once( WOOCNGR_PLUGIN_DIR . 'includes/classes/class-pb-settings-3.2.php' );
			require_once( WOOCNGR_PLUGIN_DIR . 'includes/classes/class-functions.php' );
			require_once( WOOCNGR_PLUGIN_DIR . 'includes/classes/class-hooks.php' );

			require_once( WOOCNGR_PLUGIN_DIR . 'includes/functions.php' );
		}


		/**
		 * Return data that will pass on pluginObject
		 *
		 * @return array
		 */
		function localize_scripts_data() {

			return array(
				'ajaxURL'            => admin_url( 'admin-ajax.php' ),
				'sendingText'        => esc_html__( 'Sending...', WOOCNGR_TD ),
				'sendingSuccessText' => esc_html__( 'Success', WOOCNGR_TD ),
			);
		}


		/**
		 * Loading scripts to backend
		 */
		function admin_scripts() {

			wp_enqueue_script( 'woocngr-admin', plugins_url( 'assets/admin/js/scripts.js', __FILE__ ), array( 'jquery' ), date( 'H:s' ) );
			wp_localize_script( 'woocngr-admin', 'woocngr_object', $this->localize_scripts_data() );

			wp_enqueue_style( 'woocngr-admin', WOOCNGR_PLUGIN_URL . 'assets/admin/css/style.css' );
		}


		/**
		 * Loading scripts to the frontend
		 */
		function front_scripts() {

			wp_enqueue_script( 'woocngr-front', plugins_url( 'assets/front/js/scripts.js', __FILE__ ), array( 'jquery' ), WOOCNGR_VERSION );
			wp_localize_script( 'woocngr-front', 'woocngr_object', $this->localize_scripts_data() );

			wp_enqueue_style( 'woocngr-front', WOOCNGR_PLUGIN_URL . 'assets/front/css/style.css', array(), WOOCNGR_VERSION );
		}


		/**
		 * Loading scripts
		 */
		function load_scripts() {

			add_action( 'wp_enqueue_scripts', array( $this, 'front_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		}
	}

	new wooCargonizer();
}