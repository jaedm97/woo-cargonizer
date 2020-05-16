<?php
/**
 * Class Hooks
 */


if ( ! class_exists( 'WOOCNGR_Hooks' ) ) {
	/**
	 * Class WOOCNGR_Hooks
	 */
	class WOOCNGR_Hooks {

		/**
		 * WOOCNGR_Hooks constructor.
		 */
		function __construct() {

			add_action( 'init', array( $this, 'register_everything' ) );
			add_action( 'admin_notices', array( $this, 'display_admin_noitices' ) );
			add_action( 'pb_settings_after_option', array( $this, 'display_fields_extra' ) );
			add_action( 'admin_init', array( $this, 'process_admin_api' ) );

			add_action( 'manage_shop_order_posts_columns', array( $this, 'add_columns' ), 16, 1 );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'columns_content' ), 10, 2 );

			add_action( 'wp_ajax_woocngr_send_details', array( $this, 'send_details' ) );
			add_action( 'wp_ajax_woocngr_override_send', array( $this, 'override_send' ) );

			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		}


		/**
		 * Render box
		 */
		function render_box() {
			woocngr_get_template( 'shipping-metabox.php' );
		}


		/**
		 *
		 * @param $post_type
		 */
		function add_meta_boxes( $post_type ) {

			if ( $post_type !== 'shop_order' ) {
				return;
			}

			$box_title = esc_html__( 'Shipping and Return ', WOOCNGR_TD );

			add_meta_box( 'woocngr', $box_title, array( $this, 'render_box' ), $post_type, 'side', 'high' );
		}


		/**
		 * Override order data and send to cargoniser
		 */
		function override_send() {

			$posted_data       = wp_unslash( $_POST );
			$order_id          = woocngr()->get_args_option( 'order_id', '', $posted_data );
			$transport_product = woocngr()->get_args_option( 'transport_product', '', $posted_data );
			$transport_product = explode( '-', $transport_product );
			$transport_product = array_map( 'trim', $transport_product );
			$product_id        = isset( $transport_product[0] ) ? $transport_product[0] : '';
			$agreement_id      = isset( $transport_product[1] ) ? $transport_product[1] : '';

			if ( empty( $order_id ) || empty( $product_id ) || empty( $agreement_id ) ) {
				wp_send_json_error( esc_html__( 'Error occured!', WOOCNGR_TD ) );
			}

			$override_args = array(
				'product_id'   => $product_id,
				'agreement_id' => $agreement_id,
				'package'      => woocngr()->get_args_option( 'package', '', $posted_data ),
				'weight'       => woocngr()->get_args_option( 'weight', '', $posted_data ),
				'length'       => woocngr()->get_args_option( 'length', '', $posted_data ),
				'width '       => woocngr()->get_args_option( 'width', '', $posted_data ),
				'height'       => woocngr()->get_args_option( 'height', '', $posted_data ),
			);

			if ( ! woocngr_create_consignment( $order_id, $override_args ) ) {
				wp_send_json_error( esc_html__( 'Error occured!', WOOCNGR_TD ) );
			}

			wp_send_json_success();
		}


		/**
		 * Send details to api and create consignment then store response in order meta
		 */
		function send_details() {

			$order_id = (int) woocngr()->get_args_option( 'order_id', '', wp_unslash( $_POST ) );


			if ( empty( $order_id ) || ! is_int( $order_id ) ) {
				wp_send_json_error( esc_html__( 'Error occured!', WOOCNGR_TD ) );
			}

			if ( woocngr_create_consignment( $order_id ) ) {
				wp_send_json_success();
			}
		}


		/**
		 * Render columns content
		 *
		 * @param $column
		 * @param $post_id
		 */
		function columns_content( $column, $post_id ) {

			if ( $column === 'woocngr-send' ) {
				printf( '<div class="woocngr-send-details" data-order_id="%s">%s</div>', $post_id, esc_html__( 'Send', WOOCNGR_TD ) );
			}
		}


		/**
		 * Add Cargonizer column in Order List page
		 *
		 * @param $columns
		 *
		 * @return mixed
		 */
		function add_columns( $columns ) {

			$columns['woocngr-send'] = esc_html__( 'Cargonizer', 'wp-poll' );

			return $columns;
		}


		/**
		 * Process admin request inside WP Admin
		 */
		function process_admin_api() {

			$query_args  = empty( $_GET ) ? array() : wp_unslash( $_GET );
			$woocngr_api = woocngr()->get_args_option( 'woocngr_api', '', $query_args );
			$api_for     = woocngr()->get_args_option( 'api_for', '', $query_args );

			if ( $woocngr_api !== 'yes' || empty( $api_for ) ) {
				return;
			}


			/**
			 * Update managership ID through API
			 */
			if ( $api_for === 'woocngr_managerships_id' ) {
				update_managerships_id();
			}


			/**
			 * Get transport agrrements data
			 */
			if ( $api_for === 'woocngr_transport_agreement' ) {
				woocngr_update_agreements();
			}

			wp_safe_redirect( woocngr()->get_request_url( $query_args, false ) );
		}


		/**
		 * Display field extra where it needs
		 *
		 * @param array $option
		 */
		function display_fields_extra( $option = array() ) {

			if ( woocngr()->get_args_option( 'id', '', $option ) === 'woocngr_transport_agreement' ) {
				printf( '<a href="%s" class="woocngr-field-extra woocngr_transport_agreement"><span class="dashicons dashicons-image-rotate"></span> %s</a>',
					woocngr()->get_request_url( array( 'api_for' => 'woocngr_transport_agreement' ) ),
					esc_html__( 'Get Transport Agreements', WOOCNGR_TD )
				);
			}

			if ( woocngr()->get_args_option( 'id', '', $option ) === 'woocngr_managerships_id' ) {
				printf( '<a href="%s" class="woocngr-field-extra"><span class="dashicons dashicons-image-rotate"></span> %s</a>',
					woocngr()->get_request_url( array( 'api_for' => 'woocngr_managerships_id' ) ),
					esc_html__( 'Get ID automatically from API', WOOCNGR_TD )
				);
			}
		}


		/**
		 * Display notices in wp-admin
		 */
		function display_admin_noitices() {

			/**
			 * Display error notice when no api key
			 */
			if ( empty( woocngr()->api_key ) || empty( woocngr()->get_option( 'woocngr_managerships_id' ) ) ) {
				woocngr()->print_notice( esc_html__( 'Please complete API settings to make the plugin working properly!', WOOCNGR_TD ), 'error' );
			}
		}


		/**
		 * Register Post types, Taxes, Pages and Shortcodes
		 */
		function register_everything() {

			woocngr()->PB_Settings( array(
				'plugin_name'      => esc_html( 'Cargonizer integration for WooCommerce' ),
				'add_in_menu'      => true,
				'menu_type'        => 'submenu',
				'menu_title'       => esc_html__( 'Cargonizer Settings', 'woo-cargonizer' ),
				'page_title'       => esc_html__( 'Cargonizer Settings', 'woo-cargonizer' ),
				'menu_page_title'  => esc_html__( 'Cargonizer Settings', 'woo-cargonizer' ),
				'capability'       => 'manage_options',
				'menu_slug'        => 'woocngr-settings',
				'parent_slug'      => 'woocommerce',
				'pages'            => woocngr()->get_plugin_settings(),
				'required_plugins' => array( 'woocommerce' => esc_html( 'WooCommerce' ) ),
			) );
		}
	}

	new WOOCNGR_Hooks();
}