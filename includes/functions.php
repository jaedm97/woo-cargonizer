<?php
/**
 * All Functions
 *
 * @author Pluginbazar
 */


defined( 'ABSPATH' ) || exit;


if ( ! function_exists( 'woocngr_create_consignment' ) ) {
	/**
	 * Create consignment and store response in order meta
	 *
	 * @param $order_id
	 * @param $args
	 *
	 * @return bool
	 */
	function woocngr_create_consignment( $order_id, $args = array() ) {

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		$order_settings     = woocngr()->get_option( 'woocngr_order_settings', array() );
		$order_settings     = is_array( $order_settings ) ? $order_settings : array();
		$order_extra        = woocngr()->get_option( 'woocngr_order_extra', array() );
		$order_extra        = is_array( $order_extra ) ? $order_extra : array();
		$woocngr_profile    = woocngr()->get_option( 'woocngr_profile', array() );
		$managerships       = woocngr()->get_args_option( 'managerships', array(), $woocngr_profile );
		$managership        = woocngr()->get_args_option( 'managership', array(), $managerships );
		$sender             = woocngr()->get_args_option( 'sender', array(), $managership );
		$agreement_id       = woocngr()->get_option( 'woocngr_transport_agreement', array() );
		$agreement_id       = is_array( $agreement_id ) ? $agreement_id : array();
		$agreement_id       = reset( $agreement_id );
		$product_id         = woocngr()->get_option( 'woocngr_product_' . $agreement_id );
		$product_items      = array();
		$values[]           = sprintf( '<value name="provider-email" value="%s" />', woocngr()->get_args_option( 'email', '', $woocngr_profile ) );
		$values[]           = sprintf( '<value name="order" value="%s" />', $order_id );
		$values[]           = sprintf( '<value name="humbaba" value="enkidu" />' );
		$consignee[]        = sprintf( '<name>%s %s</name>', $order->get_shipping_first_name(), $order->get_shipping_last_name() );
		$consignee[]        = sprintf( '<postcode>%s</postcode>', $order->get_shipping_postcode() );
		$consignee[]        = sprintf( '<address1>%s</address1>', $order->get_shipping_address_1() );
		$consignee[]        = sprintf( '<city>%s</city>', $order->get_shipping_city() );
		$consignee[]        = sprintf( '<country>%s</country>', $order->get_shipping_country() );
		$consignee[]        = sprintf( '<mobile>%s</mobile>', $order->get_billing_phone() );
		$consignee[]        = sprintf( '<contact-person>%s %s </contact-person>', $order->get_shipping_first_name(), $order->get_shipping_last_name() );
		$return_address[]   = sprintf( '<name>%s</name>', woocngr()->get_option( 'woocngr_address_name' ) );
		$return_address[]   = sprintf( '<address1>%s</address1>', woocngr()->get_option( 'woocngr_address_1' ) );
		$return_address[]   = sprintf( '<address2>%s</address2>', woocngr()->get_option( 'woocngr_address_2' ) );
		$return_address[]   = sprintf( '<postcode>%s</postcode>', woocngr()->get_option( 'woocngr_address_zip' ) );
		$return_address[]   = sprintf( '<city>%s</city>', woocngr()->get_option( 'woocngr_address_city' ) );
		$return_address[]   = sprintf( '<country>%s</country>', woocngr()->get_option( 'woocngr_address_country' ) );
		$services[]         = sprintf( '<service id="insurance"><currency>%s</currency><amount>%s</amount></service>', get_woocommerce_currency(), $order->get_total() );
		$create_shipping    = in_array( 'create_shipping', $order_settings ) ? 'true' : 'false';
		$career_booking     = in_array( 'career_booking', $order_extra ) ? 'true' : 'false';
		$order_fixed_weight = woocngr()->get_option( 'woocngr_order_fixed_weight', '' );

		if ( in_array( 'attach_company_name', $order_extra ) ) {
			$values[] = sprintf( '<value name="provider" value="%s" />', woocngr()->get_args_option( 'name', '', $sender ) );
		}

		foreach ( $order->get_items() as $order_item ) {
			if ( $order_item instanceof WC_Order_Item_Product ) {

				$order_item_product = $order_item->get_product();
				$product_weight     = in_array( 'fixed_weight', $order_settings ) ? $order_fixed_weight : $order_item_product->get_weight();
				$product_items[]    = sprintf( '<item volume="%s" weight="%s" length="%s" width="%s" height="%s" amount="%s" description="%s" type="package" />',
					woocngr()->get_args_option( 'package', $order_item->get_quantity(), $args ),
					woocngr()->get_args_option( 'weight', $product_weight, $args ),
					woocngr()->get_args_option( 'length', $order_item_product->get_length(), $args ),
					woocngr()->get_args_option( 'width', $order_item_product->get_width(), $args ),
					woocngr()->get_args_option( 'height', $order_item_product->get_height(), $args ),
					$order_item->get_subtotal(),
					esc_html( $order_item_product->get_short_description() )
				);
			}
		}

		$agreement_id = woocngr()->get_args_option( 'agreement_id', $agreement_id, $args );
		$product_id   = woocngr()->get_args_option( 'product_id', $product_id, $args );

		$args_str = '<consignments>
			<consignment transport_agreement="' . $agreement_id . '" estimate="true">
				<values>' . implode( '', $values ) . '</values>
				<transfer>' . $create_shipping . '</transfer>
				<booking_request>' . $career_booking . '</booking_request>
				<product>' . $product_id . '</product>
				<parts>
					<consignee>' . implode( '', $consignee ) . '</consignee>
					<return_address>' . implode( '', $return_address ) . '</return_address>
				</parts>
				<items>' . implode( '', $product_items ) . '</items>
				<services>' . implode( '', $services ) . '</services>
				<references>
					<consignee>' . $order_id . '</consignee>
				</references>
				<messages>
					<consignee>' . $order->get_customer_note() . '</consignee>
				</messages>
			</consignment>
		</consignments>';

		update_option( 'request_submitted', $args_str );

		$response = woocngr_get_curl_response( 'consignments', array( CURLOPT_POSTFIELDS => $args_str ), true );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$consignment        = woocngr()->get_args_option( 'consignment', array(), $response );
		$consignment_id     = woocngr()->get_args_option( 'id', '', $consignment );
		$consignment_number = woocngr()->get_args_option( 'number', '', $consignment );

		if ( empty( $consignment_id ) || empty( $consignment_number ) ) {
			return false;
		}

		/**
		 * Complete order when creating freight order
		 */
		if ( in_array( 'autocomplete', $order_settings ) ) {
			$order->update_status( 'completed' );
		}


		/**
		 * Store response in order meta
		 */
		update_post_meta( $order_id, 'woocngr_consignment_response', $response );
		update_post_meta( $order_id, 'woocngr_consignment_id', $consignment_id );
		update_post_meta( $order_id, 'woocngr_consignment_number', $consignment_number );


		/**
		 * Hooks to control mail, printing and other stuffs
		 */
		do_action( 'woocngr_consignment_created', $consignment_id, $order_id, $response );

		return true;
	}
}


if ( ! function_exists( 'update_managerships_id' ) ) {
	/**
	 * Update managership id and store profile information
	 */
	function update_managerships_id() {
		if ( ! is_wp_error( $response = woocngr_get_curl_response( 'profile', array(), false, false ) ) ) {

			update_option( 'woocngr_profile', $response );

			$managerships = woocngr()->get_args_option( 'managerships', array(), $response );
			$managership  = woocngr()->get_args_option( 'managership', array(), $managerships );

			update_option( 'woocngr_managerships_id', woocngr()->get_args_option( 'id', '', $managership ) );
		}
	}
}


if ( ! function_exists( 'woocngr_update_agreements' ) ) {
	/**
	 * Update agreements data and save into `woocngr_agreement_data` in option table
	 */
	function woocngr_update_agreements() {

		if ( is_wp_error( $response = woocngr_get_curl_response( 'transport_agreements' ) ) ) {
			return;
		}

		$agreements_data     = array();
		$transport_agreement = woocngr()->get_args_option( 'transport-agreement', array(), $response );

		foreach ( $transport_agreement as $agrrement ) {

			$agreement_id = woocngr()->get_args_option( 'id', array(), $agrrement );
			$description  = woocngr()->get_args_option( 'description', array(), $agrrement );
			$carrier_arr  = woocngr()->get_args_option( 'carrier', array(), $agrrement );
			$carrier_name = woocngr()->get_args_option( 'name', array(), $carrier_arr );
			$products_arr = woocngr()->get_args_option( 'products', array(), $agrrement );
			$products     = array();

			foreach ( woocngr()->get_args_option( 'product', array(), $products_arr ) as $product ) {
				$products[ woocngr()->get_args_option( 'identifier', '', $product ) ] = woocngr()->get_args_option( 'name', '', $product );
			}

			$agreements_data[ $agreement_id ] = array(
				'name'     => sprintf( '%s - %s', $carrier_name, $description ),
				'products' => $products,
			);
		}

		update_option( 'woocngr_agreement_data', $agreements_data );
		update_option( 'woocngr_transfer_agreement', $response );
	}
}


if ( ! function_exists( 'woocngr_update_printers' ) ) {
	/**
	 * Update printers data and save into `woocngr_printers_data` in option table
	 */
	function woocngr_update_printers() {

		if ( is_wp_error( $response = woocngr_get_curl_response( 'printers' ) ) ) {
			return;
		}

		$printers  = array();
		$_printers = woocngr()->get_args_option( 'printers', array(), $response );
		$_printers = empty( $_printers ) ? array( woocngr()->get_args_option( 'printer', array(), $response ) ) : $_printers;

		foreach ( $_printers as $printer ) {
			if ( ! empty( $printer_id = woocngr()->get_args_option( 'id', '', $printer ) ) ) {
				$printers[ $printer_id ] = woocngr()->get_args_option( 'name', '', $printer );
			}
		}

		update_option( 'woocngr_printers_data', $_printers );
		update_option( 'woocngr_printers', $printers );
	}
}


if ( ! function_exists( 'woocngr_get_curl_response' ) ) {
	/**
	 * Get curl response from API
	 *
	 * @param $endpoint
	 * @param array $args
	 * @param bool $is_post
	 * @param bool $include_sender
	 *
	 * @return mixed
	 */
	function woocngr_get_curl_response( $endpoint, $args = array(), $is_post = false, $include_sender = true ) {

		$headers = array(
			'Content-Type: application/xml;charset=utf-8',
		);

		if ( ! empty( $api_key = woocngr()->api_key ) ) {
			$headers[] = sprintf( 'X-Cargonizer-Key: %s', woocngr()->api_key );
		}

		if ( $include_sender ) {
			$headers[] = sprintf( 'X-Cargonizer-Sender: %s', woocngr()->managership_id );
		}

		$default = array(
			CURLOPT_URL            => woocngr()->get_args_option( 'url', woocngr()->get_curl_url( $endpoint ), $args ),
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_ENCODING       => "",
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST  => $is_post ? 'POST' : 'GET',
			CURLOPT_HTTPHEADER     => $headers,
		);

		foreach ( $args as $key => $value ) {
			if ( ! is_string( $key ) ) {
				$default[ $key ] = $value;
			}
		}

		$curl = curl_init();
		curl_setopt_array( $curl, $default );
		$response = curl_exec( $curl );

		curl_close( $curl );

		$response = json_decode( json_encode( (array) simplexml_load_string( $response ) ), true );
		$response = is_array( $response ) ? $response : array();
		$error    = woocngr()->get_args_option( 'error', '', $response );

		if ( ! empty( $error ) ) {
			return new WP_Error( 'api_error', $error );
		}

		return $response;
	}
}


if ( ! function_exists( 'woocngr' ) ) {
	/**
	 * Return global $woocngr
	 *
	 * @return WOOCNGR_Functions
	 */
	function woocngr() {
		global $woocngr;

		if ( empty( $woocngr ) ) {
			$woocngr = new WOOCNGR_Functions();
		}

		return $woocngr;
	}
}


if ( ! function_exists( 'woocngr_get_template_part' ) ) {
	/**
	 * Get Template Part
	 *
	 * @param $slug
	 * @param string $name
	 * @param array $args
	 * @param bool $main_template | When you call a template from extensions you can use this param as true to check from main template only
	 */
	function woocngr_get_template_part( $slug, $name = '', $args = array(), $main_template = false ) {

		$template   = '';
		$plugin_dir = WOOCNGR_PLUGIN_DIR;

		/**
		 * Locate template
		 */
		if ( $name ) {
			$template = locate_template( array(
				"{$slug}-{$name}.php",
				"woocngr/{$slug}-{$name}.php"
			) );
		}

		/**
		 * Search for Template in Plugin
		 *
		 * @in Plugin
		 */
		if ( ! $template && $name && file_exists( untrailingslashit( $plugin_dir ) . "/templates/{$slug}-{$name}.php" ) ) {
			$template = untrailingslashit( $plugin_dir ) . "/templates/{$slug}-{$name}.php";
		}


		/**
		 * Search for Template in Theme
		 *
		 * @in Theme
		 */
		if ( ! $template ) {
			$template = locate_template( array( "{$slug}.php", "woocngr/{$slug}.php" ) );
		}


		/**
		 * Allow 3rd party plugins to filter template file from their plugin.
		 *
		 * @filter woocngr_filters_get_template_part
		 */
		$template = apply_filters( 'woocngr_filters_get_template_part', $template, $slug, $name );


		if ( $template ) {
			load_template( $template, false );
		}
	}
}


if ( ! function_exists( 'woocngr_get_template' ) ) {
	/**
	 * Get Template
	 *
	 * @param $template_name
	 * @param array $args
	 * @param string $template_path
	 * @param string $default_path
	 * @param bool $main_template | When you call a template from extensions you can use this param as true to check from main template only
	 *
	 * @return WP_Error
	 */
	function woocngr_get_template( $template_name, $args = array(), $template_path = '', $default_path = '', $main_template = false ) {

		if ( ! empty( $args ) && is_array( $args ) ) {
			extract( $args ); // @codingStandardsIgnoreLine
		}

		/**
		 * Check directory for templates from Addons
		 */
		$backtrace      = debug_backtrace( 2, true );
		$backtrace      = empty( $backtrace ) ? array() : $backtrace;
		$backtrace      = reset( $backtrace );
		$backtrace_file = isset( $backtrace['file'] ) ? $backtrace['file'] : '';

		$located = woocngr_locate_template( $template_name, $template_path, $default_path, $backtrace_file, $main_template );


		if ( ! file_exists( $located ) ) {
			return new WP_Error( 'invalid_data', __( '%s does not exist.', 'woo-cargonizer' ), '<code>' . $located . '</code>' );
		}

		$located = apply_filters( 'woocngr_filters_get_template', $located, $template_name, $args, $template_path, $default_path );

		do_action( 'woocngr_before_template_part', $template_name, $template_path, $located, $args );

		include $located;

		do_action( 'woocngr_after_template_part', $template_name, $template_path, $located, $args );
	}
}


if ( ! function_exists( 'woocngr_locate_template' ) ) {
	/**
	 *  Locate template
	 *
	 * @param $template_name
	 * @param string $template_path
	 * @param string $default_path
	 * @param string $backtrace_file
	 * @param bool $main_template | When you call a template from extensions you can use this param as true to check from main template only
	 *
	 * @return mixed|void
	 */
	function woocngr_locate_template( $template_name, $template_path = '', $default_path = '', $backtrace_file = '', $main_template = false ) {

		$plugin_dir = WOOCNGR_PLUGIN_DIR;

		/**
		 * Template path in Theme
		 */
		if ( ! $template_path ) {
			$template_path = 'woocngr/';
		}

		/**
		 * Template default path from Plugin
		 */
		if ( ! $default_path ) {
			$default_path = untrailingslashit( $plugin_dir ) . '/templates/';
		}

		/**
		 * Look within passed path within the theme - this is priority.
		 */
		$template = locate_template(
			array(
				trailingslashit( $template_path ) . $template_name,
				$template_name,
			)
		);

		/**
		 * Get default template
		 */
		if ( ! $template ) {
			$template = $default_path . $template_name;
		}

		/**
		 * Return what we found with allowing 3rd party to override
		 *
		 * @filter woocngr_filters_locate_template
		 */
		return apply_filters( 'woocngr_filters_locate_template', $template, $template_name, $template_path );
	}
}


if ( ! function_exists( 'woocngr_generate_products_list' ) ) {
	/**
	 * Generate products list
	 *
	 * @param bool $only_returns
	 *
	 * @return array
	 */
	function woocngr_generate_products_list( $only_returns = false ) {

		$transfer_agreement = woocngr()->get_option( 'woocngr_transfer_agreement', array() );
		$agreements_data    = array();

		foreach ( woocngr()->get_args_option( 'transport-agreement', array(), $transfer_agreement ) as $agrrement ) {

			$agreement_id = woocngr()->get_args_option( 'id', '', $agrrement );
			$carrier_arr  = woocngr()->get_args_option( 'carrier', array(), $agrrement );
			$products_arr = woocngr()->get_args_option( 'products', array(), $agrrement );

			foreach ( woocngr()->get_args_option( 'product', array(), $products_arr ) as $product ) {

				$identifier   = woocngr()->get_args_option( 'identifier', '', $product );
				$product_name = woocngr()->get_args_option( 'name', '', $product );
				$services     = woocngr()->get_args_option( 'services', array(), $product );
				$service      = woocngr()->get_args_option( 'service', array(), $services );
				$data         = array(
					woocngr()->get_args_option( 'name', array(), $carrier_arr ),
					$product_name,
					woocngr()->get_args_option( 'number', '', $agrrement ),
					woocngr()->get_args_option( 'name', array(), $service ),
				);

				if ( empty( $identifier ) ) {
					continue;
				}

				if ( $only_returns && ( strpos( $identifier, 'return' ) !== false || strpos( $product_name, 'return' ) !== false ) ) {
					$agreements_data[ $identifier . '-' . $agreement_id ] = implode( ' | ', array_filter( $data ) );
				} else if ( ! $only_returns ) {
					$agreements_data[ $identifier . '-' . $agreement_id ] = implode( ' | ', array_filter( $data ) );
				}
			}
		}

		return $agreements_data;
	}
}


if ( ! class_exists( 'woocngr_services_data' ) ) {
	/**
	 * Return services data
	 *
	 * @return array
	 */
	function woocngr_services_data() {
		$agreement_data = get_option( 'woocngr_transfer_agreement' );
		$data           = array();

		foreach ( woocngr()->get_args_option( 'transport-agreement', array(), $agreement_data ) as $agreement ) {

			$products    = woocngr()->get_args_option( 'products', array(), $agreement );
			$all_product = woocngr()->get_args_option( 'product', array(), $products );

			foreach ( $all_product as $product ) {

				$p_identifier = woocngr()->get_args_option( 'identifier', '', $product );
				$services     = woocngr()->get_args_option( 'services', array(), $product );
				$services     = woocngr()->get_args_option( 'service', array(), $services );
				$services     = isset( $services[0] ) ? $services : array( $services );
				$_services    = array();

				foreach ( $services as $service ) {
					if ( empty( woocngr()->get_args_option( 'attributes', '', $service ) ) ) {
						if ( ! empty( $s_identifier = woocngr()->get_args_option( 'identifier', '', $service ) ) ) {
							$_services[ $s_identifier ] = woocngr()->get_args_option( 'name', '', $service );
						}
					}
				}

				if ( ! empty( $_services = array_filter( $_services ) ) ) {
					$data[ $p_identifier ] = $_services;
				}
			}
		}

		return $data;
	}
}


add_action( 'wp_footer', function () {
	if ( isset( $_GET['debug'] ) && $_GET['debug'] === 'yes' ) {


		$agreement_data = get_option( 'woocngr_transfer_agreement' );
		$data           = array();

		foreach ( woocngr()->get_args_option( 'transport-agreement', array(), $agreement_data ) as $agreement ) {

			$products    = woocngr()->get_args_option( 'products', array(), $agreement );
			$all_product = woocngr()->get_args_option( 'product', array(), $products );

			foreach ( $all_product as $product ) {

				$p_identifier = woocngr()->get_args_option( 'identifier', '', $product );
				$services     = woocngr()->get_args_option( 'services', array(), $product );
				$services     = woocngr()->get_args_option( 'service', array(), $services );
				$services     = isset( $services[0] ) ? $services : array( $services );
				$_services    = array();

				foreach ( $services as $service ) {
					if ( empty( woocngr()->get_args_option( 'attributes', '', $service ) ) ) {
						if ( ! empty( $s_identifier = woocngr()->get_args_option( 'identifier', '', $service ) ) ) {
							$_services[ $s_identifier ] = woocngr()->get_args_option( 'name', '', $service );
						}
					}
				}

				if ( ! empty( $_services = array_filter( $_services ) ) ) {
					$data[ $p_identifier ] = $_services;
				}
			}
		}
	}
} );