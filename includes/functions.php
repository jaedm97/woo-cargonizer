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

		$shop_name          = woocngr()->get_option( 'woocngr_shop_name', get_bloginfo( 'name' ) );
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


		/**
		 * Set product and agreement ID from available shipping methods
		 */
		$shi_methods = $order->get_shipping_methods();
		$shi_methods = reset( $shi_methods );

		if ( $shi_methods instanceof \WC_Order_Item_Shipping ) {

			$method_id    = $shi_methods->get_method_id();
			$instance_id  = $shi_methods->get_instance_id();
			$rate_id      = sprintf( '%s:%s', $method_id, $instance_id );
			$zone_id      = woocngr_get_zone_id_from_rate_id( $rate_id );
			$shi_product  = woocngr()->get_option( sprintf( 'woocngr_shi_product_%s_%s_%s', $zone_id, $method_id, $instance_id ) );
			$shi_product  = explode( '-', $shi_product );
			$shi_product  = array_map( 'trim', $shi_product );
			$product_id   = isset( $shi_product[0] ) ? $shi_product[0] : $product_id;
			$agreement_id = isset( $shi_product[1] ) ? $shi_product[1] : $agreement_id;

			$shi_services = woocngr()->get_option( sprintf( 'woocngr_shi_services_%s_%s_%s', $zone_id, $method_id, $instance_id ), array() );
			$shi_services = empty( $shi_services ) ? array() : $shi_services;

			foreach ( $shi_services as $service ) {
				$services[] = sprintf( '<service id="%s">true</service>', $service );
			}
		}


		/**
		 * Override product and agreement id from args
		 */
		$agreement_id = woocngr()->get_args_option( 'agreement_id', $agreement_id, $args );
		$product_id   = woocngr()->get_args_option( 'product_id', $product_id, $args );
		$btn_type     = woocngr()->get_args_option( 'btn_type', '', $args );


		/**
		 * Override product and agreement ID from extra buttons | Large case
		 */
		if ( $btn_type === 'large' ) {
			$large_services = woocngr()->get_option( 'woocngr_btn_large_services', array() );
			$large_services = is_array( $large_services ) ? $large_services : array();
			$large_product  = woocngr()->get_option( 'woocngr_btn_large_product' );
			$large_product  = explode( '-', $large_product );
			$large_product  = array_map( 'trim', $large_product );
			$product_id     = isset( $large_product[0] ) ? $large_product[0] : $product_id;
			$agreement_id   = isset( $large_product[1] ) ? $large_product[1] : $agreement_id;

			foreach ( $large_services as $service ) {
				$services[] = sprintf( '<service id="%s">true</service>', $service );
			}
		}


		/**
		 * Override product and agreement ID from extra buttons | Small case
		 */
		if ( $btn_type === 'small' ) {
			$small_services = woocngr()->get_option( 'woocngr_btn_small_services', array() );
			$small_services = is_array( $small_services ) ? $small_services : array();
			$small_product  = woocngr()->get_option( 'woocngr_btn_small_product' );
			$small_product  = explode( '-', $small_product );
			$small_product  = array_map( 'trim', $small_product );
			$product_id     = isset( $small_product[0] ) ? $small_product[0] : $product_id;
			$agreement_id   = isset( $small_product[1] ) ? $small_product[1] : $agreement_id;

			foreach ( $small_services as $service ) {
				$services[] = sprintf( '<service id="%s">true</service>', $service );
			}
		}


		$_s_partner  = '';
		$s_partner   = woocngr()->get_meta( 'woocngr_pickup_address', $order_id );
		$s_partner   = explode( '~', $s_partner );
		$sp_name     = isset( $s_partner[0] ) ? $s_partner[0] : '';
		$sp_number   = isset( $s_partner[1] ) ? $s_partner[1] : '';
		$sp_address1 = isset( $s_partner[2] ) ? $s_partner[2] : '';
		$sp_postcode = isset( $s_partner[3] ) ? $s_partner[3] : '';
		$sp_city     = isset( $s_partner[4] ) ? $s_partner[4] : '';
		$sp_country  = isset( $s_partner[5] ) ? $s_partner[5] : '';

		if ( ! empty( $sp_name ) && ! empty( $sp_number ) && ! empty( $sp_postcode ) && ! empty( $sp_city ) && ! empty( $sp_country ) ) {
			$_s_partner = sprintf( '<service_partner>%s</service_partner>', implode( '', array(
				sprintf( '<number>%s</number>', $sp_number ),
				sprintf( '<name>%s</name>', $sp_name ),
				sprintf( '<address1>%s</address1>', $sp_address1 ),
				sprintf( '<postcode>%s</postcode>', $sp_postcode ),
				sprintf( '<city>%s</city>', $sp_city ),
				sprintf( '<country>%s</country>', $sp_country ),
			) ) );
		}

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
				' . $_s_partner . '
				<items>' . implode( '', $product_items ) . '</items>
				<services>
					' . implode( '', $services ) . '
				</services>
				<references>
					<consignor>' . $shop_name . ' - Ordre #' . $order_id . '</consignor>
					<consignee>' . $shop_name . ' - Ordre #' . $order_id . '</consignee>
				</references>
			</consignment>
		</consignments>';

		update_option( 'request_submitted', $args_str );

		echo '<pre>';
		print_r( esc_html( $args_str ) );
		echo '</pre>';

		die();


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


if ( ! class_exists( 'woocngr_get_zone_id_from_rate_id' ) ) {
	/**
	 * Return shipping zone id from shipping method rate ID
	 *
	 * @param $method_rate_id
	 *
	 * @return mixed
	 */
	function woocngr_get_zone_id_from_rate_id( $method_rate_id ) {
		global $wpdb;

		$data        = explode( ':', $method_rate_id );
		$method_id   = $data[0];
		$instance_id = $data[1];
		$zone_id     = $wpdb->get_col( "SELECT wszm.zone_id FROM {$wpdb->prefix}woocommerce_shipping_zone_methods as wszm WHERE wszm.instance_id = '$instance_id' AND wszm.method_id LIKE '$method_id'" );

		return reset( $zone_id );
	}
}


if ( ! function_exists( 'update_managerships_id' ) ) {
	/**
	 * Update managership id and store profile information
	 */
	function update_managerships_id() {
		if ( ! is_wp_error( $response = woocngr_get_curl_response( 'profile', array(), false, false ) ) ) {

			update_option( 'woocngr_profile', $response );

			$managerships   = woocngr()->get_args_option( 'managerships', array(), $response );
			$managership    = woocngr()->get_args_option( 'managership', array(), $managerships );
			$managership_id = woocngr()->get_args_option( 'id', '', $managership );

			if ( is_array( $managership_id ) && isset( $managership_id[0] ) ) {
				$managership_id = $managership_id[0];
			}

			update_option( 'woocngr_managerships_id', $managership_id );
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

			if ( is_array( $agreement_id ) && isset( $agreement_id[0] ) ) {
				$agreement_id = $agreement_id[0];
			}

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
		$_printers = empty( $_printers ) ? woocngr()->get_args_option( 'printer', array(), $response ) : $_printers;
		$_printers = isset( $_printers[0] ) ? $_printers : array( $_printers );

		foreach ( $_printers as $printer ) {
			if ( ! empty( $printer_id = woocngr()->get_args_option( 'id', '', $printer ) ) ) {
				if ( is_array( $printer_id ) && isset( $printer_id[0] ) ) {
					$printer_id = $printer_id[0];
				}
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

		$curl_url = woocngr()->get_args_option( 'url', woocngr()->get_curl_url( $endpoint ), $args );
		$default  = array(
			CURLOPT_URL            => $curl_url,
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

		update_option( 'woocngr_debug_last_response', array(
			'url'      => $curl_url,
			'request'  => $default,
			'response' => $response,
			'error'    => $error,
		) );

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


		woocngr_create_consignment( 62 );


		/**
		 * Display any option value
		 */
		if ( ! empty( $opt_id = woocngr()->get_args_option( 'opt_id', '', wp_unslash( $_GET ) ) ) ) {
			echo '<pre>';
			print_r( woocngr()->get_option( $opt_id ) );
			echo '</pre>';
		}
	}
} );