<?php
/**
 * Shipping metabox
 */

global $post;

$popup_fields = array(
	array(
		'id'    => 'woocngr_so_transport_product',
		'title' => esc_html__( 'Transport Product', WOOCNGR_TD ),
		'type'  => 'select',
		'args'  => woocngr_generate_products_list(),
	),
	array(
		'id'    => 'woocngr_so_package',
		'title' => esc_html__( 'Package', WOOCNGR_TD ),
		'type'  => 'number',
	),
	array(
		'id'    => 'woocngr_so_weight',
		'title' => esc_html__( 'Weight', WOOCNGR_TD ),
		'type'  => 'number',
	),
	array(
		'id'    => 'woocngr_so_length',
		'title' => esc_html__( 'Length (cm)', WOOCNGR_TD ),
		'type'  => 'number',
	),
	array(
		'id'    => 'woocngr_so_width',
		'title' => esc_html__( 'Width (cm)', WOOCNGR_TD ),
		'type'  => 'number',
	),
	array(
		'id'    => 'woocngr_so_height',
		'title' => esc_html__( 'Height (cm)', WOOCNGR_TD ),
		'type'  => 'number',
	),
);
$popup_fields = array( array( 'options' => $popup_fields ) );

?>

<div class="woocngr-shipping-return">
    <div class="button woocngr-btn-send"
         data-order_id="<?php echo esc_attr( $post->ID ); ?>"><?php esc_html_e( 'Send', WOOCNGR_TD ) ?></div>
    <div class="button woocngr-btn-shipping woocngr-popup-opener"
         data-target="override-shipping"><?php esc_html_e( 'Override Shipping', WOOCNGR_TD ) ?></div>
    <div class="button woocngr-btn-return woocngr-popup-opener"
         data-target="return-shipping"><?php esc_html_e( 'Return', WOOCNGR_TD ) ?></div>
</div>


<div class="woocngr-popup-container override-shipping">
    <div class="woocngr-popup-box">
		<?php woocngr()->PB_Settings()->generate_fields( $popup_fields, $post->ID, false ); ?>
        <div class="woocngr-popup-buttons">
            <div class="button button-hero button-secondary woocngr-popup-send"
                 data-order_id="<?php echo esc_attr( $post->ID ); ?>"><?php esc_html_e( 'Override Shipping and Send', WOOCNGR_TD ) ?></div>
            <div class="button button-hero button-secondary woocngr-popup-cancel"><?php esc_html_e( 'Cancel', WOOCNGR_TD ) ?></div>
        </div>
    </div>
</div>


<div class="woocngr-popup-container return-shipping">
    <div class="woocngr-popup-box">
		<?php woocngr()->PB_Settings()->generate_fields( array(
			array(
				'options' => array(
					array(
						'id'    => 'woocngr_so_transport_product',
						'title' => esc_html__( 'Return Product', WOOCNGR_TD ),
						'type'  => 'select',
						'args'  => woocngr_generate_products_list( true ),
					),
				)
			)
		), $post->ID, false ); ?>
        <div class="woocngr-popup-buttons">
            <div class="button button-hero button-secondary woocngr-popup-send"
                 data-order_id="<?php echo esc_attr( $post->ID ); ?>"><?php esc_html_e( 'Send Return', WOOCNGR_TD ) ?></div>
            <div class="button button-hero button-secondary woocngr-popup-cancel"><?php esc_html_e( 'Cancel', WOOCNGR_TD ) ?></div>
        </div>
    </div>
</div>
