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
			add_action( 'wp_ajax_woocngr_update_pickup', array( $this, 'update_pickup' ) );
			add_action( 'wp_ajax_nopriv_woocngr_update_pickup', array( $this, 'update_pickup' ) );

			add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
			add_action( 'woocngr_consignment_created', array( $this, 'printing_labels' ), 10, 3 );
			add_action( 'woocommerce_checkout_billing', array( $this, 'render_pickup_selections' ), 0 );
			add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'update_order_meta' ), 10, 1 );
		}


		/**
		 * Update order meta for pickup address
		 *
		 * @param $order_id
		 */
		function update_order_meta( $order_id ) {
			if ( ! empty( $_POST['woocngr_pickup_address'] ) ) {
				update_post_meta( $order_id, 'woocngr_pickup_address', sanitize_text_field( $_POST['woocngr_pickup_address'] ) );
			}
		}


		/**
		 * Render conditional pickup address picker
		 */
		function render_pickup_selections() {
			printf( '<div class="woocngr_pickup_address_wrap"></div>' );
		}


		/**
		 * Sending label to a printer
		 *
		 * @param $consignment_id
		 * @param $order_id
		 * @param $response
		 */
		function printing_labels( $consignment_id, $order_id, $response ) {

			if ( empty( $printer_id = woocngr()->get_option( 'woocngr_printer_id' ) ) ) {
				return;
			}

			$printer_opt  = woocngr()->get_option( 'woocngr_printer_opt', array( 'upon_creation' ) );
			$printer_opt  = is_array( $printer_opt ) ? $printer_opt : array();
			$printer_time = woocngr()->get_option( 'woocngr_printer_time' );

			if ( ! in_array( 'upon_creation', $printer_opt ) ) {
				return;
			}

			$args     = array(
				'url' => sprintf( '%sconsignments/label_direct?%s', woocngr()->base_url, http_build_query( array(
					'printer_id'      => $printer_id,
					'consignment_ids' => array( $consignment_id ),
				) ) ),
			);
			$response = woocngr_get_curl_response( '', $args );
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
		 * Update pickup address upon country selection in checkout
		 */
		function update_pickup() {

			$posted_data        = wp_unslash( $_POST );
			$zip_code           = woocngr()->get_args_option( 'zipcode', '', $posted_data );
			$country            = woocngr()->get_args_option( 'country', '', $posted_data );
			$career_name        = '';
			$agreement_id       = woocngr()->get_option( 'woocngr_transport_agreement', '' );
			$transfer_agreement = woocngr()->get_option( 'woocngr_transfer_agreement', array() );
			$transfer_agreement = woocngr()->get_args_option( 'transport-agreement', array(), $transfer_agreement );

			foreach ( $transfer_agreement as $agreement ) {

				$career_arr = woocngr()->get_args_option( 'carrier', array(), $agreement );
				$this_id    = woocngr()->get_args_option( 'id', array(), $agreement );

				if ( in_array( $this_id, $agreement_id ) ) {
					$career_name = wc_strtolower( woocngr()->get_args_option( 'name', array(), $career_arr ) );
				}
			}

			$args = array(
				'url' => sprintf( '%sservice_partners.xml?%s', woocngr()->base_url, http_build_query( array(
					'country'  => $country,
					'postcode' => $zip_code,
					'carrier'  => $career_name,
				) ) ),
			);

			$response         = woocngr_get_curl_response( '', $args );
			$service_partners = woocngr()->get_args_option( 'service-partners', array(), $response );
			$service_partners = woocngr()->get_args_option( 'service-partner', array(), $service_partners );
			$service_partners = isset( $service_partners[0] ) ? $service_partners : array( $service_partners );
			$service_partners = array_filter( $service_partners );
			$partners_arr     = array();

			foreach ( $service_partners as $partner ) {

				$partner_name       = woocngr()->get_args_option( 'name', '', $partner );
				$partner_city       = woocngr()->get_args_option( 'city', '', $partner );
				$partner_number     = woocngr()->get_args_option( 'number', '', $partner );
				$partner_address1   = woocngr()->get_args_option( 'address1', '', $partner );
				$partner_postcode   = woocngr()->get_args_option( 'postcode', '', $partner );
				$partner_country    = woocngr()->get_args_option( 'country', '', $partner );
				$partner_identifier = implode( '~', array(
					$partner_name,
					$partner_number,
					$partner_address1,
					$partner_postcode,
					$partner_city,
					$partner_country
				) );
				$partner_value      = implode( ' | ', array(
					$partner_name,
					$partner_city,
					$partner_postcode,
				) );

				$partners_arr[ $partner_identifier ] = $partner_value;
			}

			ob_start();
			woocommerce_form_field( 'woocngr_pickup_address', array(
				'label'   => esc_html__( 'Select pickup address', WOOCNGR_TD ),
				'type'    => 'select',
				'options' => $partners_arr,
			) );
			wp_send_json_success( ob_get_clean() );
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
				wp_send_json_error( esc_html__( 'Error occured in sending!', WOOCNGR_TD ) );
			}

			wp_send_json_success();
		}


		/**
		 * Send details to api and create consignment then store response in order meta
		 */
		function send_details() {

			$order_id = (int) woocngr()->get_args_option( 'order_id', '', wp_unslash( $_POST ) );
			$btn_type = woocngr()->get_args_option( 'btn_type', '', wp_unslash( $_POST ) );
			$args     = array();

			if ( empty( $order_id ) || ! is_int( $order_id ) ) {
				wp_send_json_error( esc_html__( 'Error occured!', WOOCNGR_TD ) );
			}

			if ( ! empty( $btn_type ) && in_array( $btn_type, array( 'small', 'large' ) ) ) {
				$args['btn_type'] = $btn_type;
			}

			if ( woocngr_create_consignment( $order_id, $args ) ) {
				wp_send_json_success();
			}

			wp_send_json_error( esc_html__( 'Error occured in sending!', WOOCNGR_TD ) );
		}


		/**
		 * Render columns content
		 *
		 * @param $column
		 * @param $post_id
		 */
		function columns_content( $column, $post_id ) {

			if ( $column === 'woocngr-send' ) {
				printf( '<div class="woocngr-btn-send woocngr-send-details" data-order_id="%s">%s</div>', $post_id, esc_html__( 'Send', WOOCNGR_TD ) );
				printf( '<div class="woocngr-btn-send woocngr-send-btn" data-btn_type="large" data-order_id="%s">%s</div>', $post_id, esc_html__( 'Large', WOOCNGR_TD ) );
				printf( '<div class="woocngr-btn-send woocngr-send-btn" data-btn_type="small" data-order_id="%s">%s</div>', $post_id, esc_html__( 'Small', WOOCNGR_TD ) );
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
			 * Get transport agrrements data
			 */
			if ( $api_for === 'woocngr_transport_agreement' ) {
				woocngr_update_agreements();
			}

			/**
			 * Get Printers
			 */
			if ( $api_for === 'woocngr_printer_id' ) {
				woocngr_update_printers();
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

			if ( woocngr()->get_args_option( 'id', '', $option ) === 'woocngr_printer_id' ) {
				printf( '<a href="%s" class="woocngr-field-extra"><span class="dashicons dashicons-image-rotate"></span> %s</a>',
					woocngr()->get_request_url( array( 'api_for' => 'woocngr_printer_id' ) ),
					esc_html__( 'Get printers from API', WOOCNGR_TD )
				);
			}

			if ( in_array( woocngr()->get_args_option( 'class', '', $option ), array(
				'woocngr_shi_product_selection',
				'woocngr_btn_large_product_selection',
				'woocngr_btn_small_product_selection',
			) ) ) {
				printf( '<script class="services_data">\'%s\'</script>', json_encode( woocngr_services_data() ) );
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