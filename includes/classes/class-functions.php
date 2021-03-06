<?php
/**
 * Class Functions
 *
 * @author Pluginbazar
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WOOCNGR_Functions' ) ) {
	/**
	 * Class WOOCNGR_Functions
	 */
	class WOOCNGR_Functions {

		/**
		 * API key for the integration
		 *
		 * @var string
		 */
		public $api_key = '';


		/**
		 * Managership ID
		 *
		 * @var string
		 */
		public $managership_id = '';


		/**
		 * Specify base URL for the api
		 *
		 * @var string
		 */
		public $base_url = '';


		/**
		 * Load agreement data
		 * @var array
		 */
		public $agreement_data = array();


		/**
		 * WOOCNGR_Functions constructor.
		 */
		function __construct() {

			/**
			 * Initialize variables
			 */
			$this->api_key        = $this->get_option( 'woocngr_api_key' );
			$this->base_url       = sprintf( 'https://%scargonizer.no/', in_array( 'yes', (array) $this->get_option( 'woocngr_enable_sandbox', array() ) ) ? 'sandbox.' : '' );
			$this->agreement_data = $this->get_option( 'woocngr_agreement_data', array() );

			$this->set_managership_id();
		}


		/**
		 * Return Agreements with id and name as array
		 *
		 * @return array
		 */
		function get_transport_agreements() {

			$agreements = array();

			foreach ( $this->agreement_data as $agreement_id => $data ) {
				$agreements[ $agreement_id ] = woocngr()->get_args_option( 'name', '', $data );
			}

			return $agreements;
		}

		/**
		 * Return complete curl URL
		 *
		 * @param string $endpoint
		 *
		 * @return string
		 */
		function get_curl_url( $endpoint = '' ) {

			$endpoint = explode( '.', $endpoint );
			$endpoint = isset( $endpoint[0] ) ? $endpoint[0] : '';

			if ( empty( $endpoint ) ) {
				return $this->base_url;
			}

			return sprintf( '%s%s.xml', $this->base_url, $endpoint );
		}


		/**
		 * Return plugin settings fields
		 *
		 * @return mixed|void
		 */
		function get_plugin_settings() {

			$options   = array(
				array(
					'id'          => 'woocngr_enable_sandbox',
					'title'       => esc_html__( 'Enable Sandbox', 'woo-cargonizer' ),
					'placeholder' => esc_html__( 'Do you want to integrate with sandbox or with main.', 'woo-cargonizer' ),
					'type'        => 'checkbox',
					'args'        => array(
						'yes' => esc_html__( 'Enable / Disable Sandbox', 'woo-cargonizer' ),
					),
				),
				array(
					'id'          => 'woocngr_api_key',
					'title'       => esc_html__( 'API Key', 'woo-cargonizer' ),
					'details'     => esc_html__( 'Set API key for cargonizer integration', 'woo-cargonizer' ),
					'placeholder' => esc_html( '3408c1jht433fd3kliukje80fd7f93624c1948505' ),
					'type'        => 'text',
				),
				array(
					'id'    => 'woocngr_managerships_id',
					'title' => esc_html__( 'Managerships ID', 'woo-cargonizer' ),
					'type'  => 'text',
				),
				array(
					'id'    => 'woocngr_transport_agreement',
					'class' => 'woocngr_transport_agreement',
					'title' => esc_html__( 'Select Transport Agreement', 'woo-cargonizer' ),
					'type'  => 'radio',
					'args'  => woocngr()->get_transport_agreements(),
				),
			); 
			$agreement = $this->get_option( 'woocngr_transport_agreement', array() );
			$agreement = reset( $agreement );

			foreach ( $this->agreement_data as $agreement_id => $data ) {

				$career_name    = explode( '-', woocngr()->get_args_option( 'name', '', $data ) );
				$career_name    = isset( $career_name[0] ) ? $career_name[0] : '';
				$should_display = $agreement == $agreement_id ? 'should-display' : '';
				$options[]      = array(
					'id'      => 'woocngr_product_' . $agreement_id,
					'class'   => 'woocngr_product agreement_' . $agreement_id . ' ' . $should_display,
					'title'   => esc_html__( 'Product Identifier', WOOCNGR_TD ),
					'details' => sprintf( esc_html__( 'Select product for - %s', WOOCNGR_TD ), $career_name ),
					'type'    => 'select2',
					'args'    => woocngr()->get_args_option( 'products', '', $data ),
				);
			}

			$pages['woocngr-options'] = array(
				'page_nav'      => esc_html__( 'API Settings', 'woo-cargonizer' ),
				'page_settings' => array(
					array(
						'title'   => esc_html__( 'API Credintials Settings', 'woo-cargonizer' ),
						'options' => $options
					),
				),
			);

			$pages['woocngr-settings'] = array(
				'page_nav'      => esc_html__( 'Settings', 'woo-cargonizer' ),
				'page_settings' => array(
					array(
						'title'   => esc_html__( 'General Settings', 'woo-cargonizer' ),
						'options' => array(
							array(
								'id'          => 'woocngr_shop_name',
								'title'       => esc_html__( 'Shop name', 'woo-cargonizer' ),
								'details'     => esc_html__( 'Please specify your WooCommerce store or shop name here.', 'woo-cargonizer' ),
								'type'        => 'text',
								'placeholder' => get_bloginfo( 'name' ),
							),
							array(
								'id'    => 'woocngr_order_settings',
								'title' => esc_html__( 'Order Settings', 'woo-cargonizer' ),
								'type'  => 'checkbox',
								'args'  => array(
									'autocomplete'      => esc_html__( 'Complete order when creating freight order', 'woo-cargonizer' ),
									'create_shipping'   => esc_html__( 'Create shipping upon order completion', 'woo-cargonizer' ),
									'tracking_in_order' => esc_html__( 'Send tracking URL in order email', 'woo-cargonizer' ),
									'fixed_weight'      => esc_html__( 'Fixed weight in all orders', 'woo-cargonizer' ),
								),
							),
							array(
								'id'      => 'woocngr_order_fixed_weight',
								'details' => esc_html__( 'Weight, If you select fixed weight in previous settings, then specify the weight here.', 'woo-cargonizer' ),
								'type'    => 'number',
							),
							array(
								'id'    => 'woocngr_order_extra',
								'title' => esc_html__( 'Freight assignments and etiquette', 'woo-cargonizer' ),
								'type'  => 'checkbox',
								'args'  => array(
									'attach_company_name' => esc_html__( 'Attach company name to freight assignment', 'woo-cargonizer' ),
									'career_booking'      => esc_html__( 'Enable career booking', 'woo-cargonizer' ),
								),
							),
						),
					),
					array(
						'title'   => esc_html__( 'Return Address', 'woo-cargonizer' ),
						'options' => array(
							array(
								'id'    => 'woocngr_address_name',
								'title' => esc_html__( 'Name *', 'woo-cargonizer' ),
								'type'  => 'text',
							),
							array(
								'id'    => 'woocngr_address_1',
								'title' => esc_html__( 'Address 1 *', 'woo-cargonizer' ),
								'type'  => 'textarea',
							),
							array(
								'id'    => 'woocngr_address_2',
								'title' => esc_html__( 'Address 2', 'woo-cargonizer' ),
								'type'  => 'textarea',
							),
							array(
								'id'    => 'woocngr_address_zip',
								'title' => esc_html__( 'Zip Code *', 'woo-cargonizer' ),
								'type'  => 'text',
							),
							array(
								'id'    => 'woocngr_address_city',
								'title' => esc_html__( 'City *', 'woo-cargonizer' ),
								'type'  => 'text',
							),
							array(
								'id'    => 'woocngr_address_country',
								'title' => esc_html__( 'Country *', 'woo-cargonizer' ),
								'type'  => 'select2',
								'args'  => WC()->countries->get_countries(),
							),
						),
					),
				),
			);


			$shipping_options = array();

			foreach ( WC_Shipping_Zones::get_zones() as $zone ) {

				$zone_id          = woocngr()->get_args_option( 'zone_id', '', $zone );
				$zone_name        = woocngr()->get_args_option( 'zone_name', '', $zone );
				$shipping_methods = woocngr()->get_args_option( 'shipping_methods', array(), $zone );

				foreach ( $shipping_methods as $index => $method ) {

					$woocngr_shi_product  = sprintf( 'woocngr_shi_product_%s_%s_%s', $zone_id, $method->id, $index );
					$woocngr_shi_printer  = sprintf( 'woocngr_shi_printer_%s_%s_%s', $zone_id, $method->id, $index );
					$woocngr_shi_services = sprintf( 'woocngr_shi_services_%s_%s_%s', $zone_id, $method->id, $index );
					$product_selected     = $this->get_option( $woocngr_shi_product );
					$product_selected     = explode( '-', $product_selected );
					$product_selected     = isset( $product_selected[0] ) ? $product_selected[0] : '';
					$services_all         = woocngr_services_data();
					$services_all         = $this->get_args_option( $product_selected, array(), $services_all );

					$shipping_options[] = array(
						'id'      => $woocngr_shi_product,
						'class'   => 'woocngr_shi_product_selection',
						'title'   => sprintf( '%s | %s', $zone_name, $method->title ),
						'details' => esc_html__( 'Select product', 'woo-cargonizer' ),
						'type'    => 'select',
						'args'    => woocngr_generate_products_list(),
					);
					$shipping_options[] = array(
						'id'      => $woocngr_shi_printer,
						'details' => esc_html__( 'Select a printer', 'woo-cargonizer' ),
						'type'    => 'select',
						'args'    => woocngr()->get_option( 'woocngr_printers', array() ),
					);
					$shipping_options[] = array(
						'id'      => $woocngr_shi_services,
						'class'   => $woocngr_shi_product,
						'details' => esc_html__( 'Select services you preferred', 'woo-cargonizer' ),
						'type'    => 'checkbox',
						'args'    => $services_all,
					);
				}
			}

			$pages['woocngr-printers'] = array(
				'page_nav'      => esc_html__( 'Printers and Shipping', 'woo-cargonizer' ),
				'page_settings' => array(
					array(
						'title'   => esc_html__( 'Printer Settings', 'woo-cargonizer' ),
						'options' => array(
							array(
								'id'    => 'woocngr_printer_id',
								'title' => esc_html__( 'Select a printer', 'woo-cargonizer' ),
								'type'  => 'select',
								'args'  => woocngr()->get_option( 'woocngr_printers', array() ),
							),
							array(
								'id'      => 'woocngr_printer_opt',
								'title'   => esc_html__( 'Shipping label', 'woo-cargonizer' ),
								'type'    => 'radio',
								'class'   => 'woocngr_printer_hide',
								'args'    => array(
									'upon_creation' => esc_html__( 'Upon creation of Consignment', 'woo-cargonizer' ),
									'custom_time'   => esc_html__( 'Custom time. Specify in below field.', 'woo-cargonizer' ),
								),
								'default' => array( 'upon_creation' ),
							),
							array(
								'id'            => 'woocngr_printer_time',
								'class'         => 'woocngr_printer_hide',
								'details'       => esc_html__( 'Specify custom time of printing shipping label', 'woo-cargonizer' ),
								'type'          => 'timepicker',
								'placeholder'   => '08:00 AM',
								'field_options' => array(
									'timeFormat' => 'h:mm p',
									'interval'   => '30',
									'minTime'    => '24:00am',
									'maxTime'    => '23:59pm',
									'dynamic'    => false,
									'dropdown'   => true,
									'scrollbar'  => true,
								),
							),
						),
					),
					array(
						'title'   => esc_html__( 'Shipping Settings', 'woo-cargonizer' ),
						'options' => $shipping_options,
					),
				),
			);


			$large_product  = $this->get_option( 'woocngr_btn_large_product' );
			$large_product  = explode( '-', $large_product );
			$large_product  = isset( $large_product[0] ) ? $large_product[0] : '';
			$large_services = woocngr_services_data();
			$large_services = $this->get_args_option( $large_product, array(), $large_services );

			$small_product  = $this->get_option( 'woocngr_btn_small_product' );
			$small_product  = explode( '-', $small_product );
			$small_product  = isset( $small_product[0] ) ? $small_product[0] : '';
			$small_services = woocngr_services_data();
			$small_services = $this->get_args_option( $small_product, array(), $small_services );

			$pages['woocngr-buttons'] = array(
				'page_nav'      => esc_html__( 'Button Settings', 'woo-cargonizer' ),
				'page_settings' => array(
					array(
						'title'   => esc_html__( 'Large Button Settings', 'woo-cargonizer' ),
						'options' => array(
							array(
								'id'    => 'woocngr_btn_large_product',
								'class' => 'woocngr_btn_large_product_selection',
								'title' => esc_html__( 'Product for Large Button', 'woo-cargonizer' ),
								'type'  => 'select',
								'args'  => woocngr_generate_products_list(),
							),
							array(
								'id'      => 'woocngr_btn_large_printer',
								'details' => esc_html__( 'Select a printer', 'woo-cargonizer' ),
								'type'    => 'select',
								'args'    => woocngr()->get_option( 'woocngr_printers', array() ),
							),
							array(
								'id'      => 'woocngr_btn_large_services',
								'class'   => 'woocngr_btn_large_product',
								'details' => esc_html__( 'Select services you preferred', 'woo-cargonizer' ),
								'type'    => 'checkbox',
								'args'    => $large_services,
							),
						),
					),
					array(
						'title'   => esc_html__( 'Small Button Settings', 'woo-cargonizer' ),
						'options' => array(
							array(
								'id'    => 'woocngr_btn_small_product',
								'class' => 'woocngr_btn_small_product_selection',
								'title' => esc_html__( 'Product for Small Button', 'woo-cargonizer' ),
								'type'  => 'select',
								'args'  => woocngr_generate_products_list(),
							),
							array(
								'id'      => 'woocngr_btn_small_printer',
								'details' => esc_html__( 'Select a printer', 'woo-cargonizer' ),
								'type'    => 'select',
								'args'    => woocngr()->get_option( 'woocngr_printers', array() ),
							),
							array(
								'id'      => 'woocngr_btn_small_services',
								'class'   => 'woocngr_btn_small_product',
								'details' => esc_html__( 'Select services you preferred', 'woo-cargonizer' ),
								'type'    => 'checkbox',
								'args'    => $small_services,
							),
						),
					),
				),
			);

			return apply_filters( 'woocngr_filters_settings_pages', $pages );
		}


		/**
		 * Return request URL
		 *
		 * @param array $args
		 * @param bool $is_this_api
		 *
		 * @return string
		 */
		function get_request_url( $args = array(), $is_this_api = true ) {

			$request_args = array();

			if ( $is_this_api ) {
				$request_args['woocngr_api'] = esc_attr( 'yes' );
			}

			if ( ! empty( $page = woocngr()->get_args_option( 'page', '', wp_unslash( $_GET ) ) ) ) {
				$request_args['page'] = $page;
			}

			$request_args = wp_parse_args( $request_args, wp_unslash( $_GET ) );
			$request_args = wp_parse_args( $request_args, $args );
			$php_self     = woocngr()->get_args_option( 'PHP_SELF', '', wp_unslash( $_SERVER ) );

			if ( ! $is_this_api ) {
				unset( $request_args['woocngr_api'] );
				unset( $request_args['api_for'] );
			}

			return sprintf( '%s%s?%s', site_url(), $php_self, http_build_query( $request_args ) );
		}


		/**
		 * Set managership ID
		 */
		function set_managership_id() {
			$this->managership_id = $this->get_option( 'woocngr_managerships_id' );
		}


		/**
		 * Print notices
		 *
		 * @param string $message
		 * @param string $type
		 * @param bool $is_dismissible
		 */
		function print_notice( $message = '', $type = 'success', $is_dismissible = true ) {

			$is_dismissible = $is_dismissible ? 'is-dismissible' : '';

			if ( ! empty( $message ) ) {
				printf( '<div class="notice notice-%s %s"><p>%s</p></div>', $type, $is_dismissible, $message );
			}
		}


		/**
		 * Return Post Meta Value
		 *
		 * @param bool $meta_key
		 * @param bool $post_id
		 * @param string $default
		 *
		 * @return mixed|string|void
		 */
		function get_meta( $meta_key = false, $post_id = false, $default = '' ) {

			if ( ! $meta_key ) {
				return '';
			}

			$post_id    = ! $post_id ? get_the_ID() : $post_id;
			$meta_value = get_post_meta( $post_id, $meta_key, true );
			$meta_value = empty( $meta_value ) ? $default : $meta_value;

			return apply_filters( 'eem_filters_get_meta', $meta_value, $meta_key, $post_id, $default );
		}


		/**
		 * Return option value
		 *
		 * @param string $option_key
		 * @param string $default_val
		 *
		 * @return mixed|string|void
		 */
		function get_option( $option_key = '', $default_val = '' ) {

			if ( empty( $option_key ) ) {
				return '';
			}

			$option_val = get_option( $option_key, $default_val );
			$option_val = empty( $option_val ) ? $default_val : $option_val;

			return apply_filters( 'woocngr_filters_option_' . $option_key, $option_val );
		}


		/**
		 * Return PB_Settings class
		 *
		 * @param array $args
		 *
		 * @return PB_Settings
		 */
		function PB_Settings( $args = array() ) {

			return new PB_Settings( $args );
		}


		/**
		 * Return Arguments Value
		 *
		 * @param string $key
		 * @param string $default
		 * @param array $args
		 *
		 * @return mixed|string
		 */
		function get_args_option( $key = '', $default = '', $args = array() ) {

			global $this_preloader;

			$args = empty( $args ) ? $this_preloader : $args;
			$key  = empty( $key ) ? '' : $key;

			if ( empty( $default ) && is_array( $default ) ) {
				$default = array();
			} else if ( empty( $default ) && ! is_array( $default ) ) {
				$default = '';
			}

			if ( isset( $args[ $key ] ) && ! empty( $args[ $key ] ) ) {
				return $args[ $key ];
			}

			return $default;
		}
	}
}

global $woocngr;

$woocngr = new WOOCNGR_Functions();