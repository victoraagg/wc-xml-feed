<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Btb_Products_Shortcodes' ) ) :

class Btb_Product_Shortcodes extends Btb_Shortcodes {

	function __construct() {

		$this->the_shortcodes = array(
			'btb_product_available_variations',
			'btb_product_average_rating',
			'btb_product_categories',
			'btb_product_categories_names',
			'btb_product_custom_field',
			'btb_product_description',
			'btb_product_excerpt',
			'btb_product_function',
			'btb_product_id',
			'btb_product_image_url',
			'btb_product_list_attribute',
			'btb_product_list_attributes',
			'btb_product_meta',
			'btb_product_name',
			'btb_product_price',
			'btb_product_regular_price',
			'btb_product_sale_price',
			'btb_product_shipping',
			'btb_product_shipping_class',
			'btb_product_short_description',
			'btb_product_sku',
			'btb_product_identifier',
			'btb_product_stock_availability',
			'btb_product_stock_quantity',
			'btb_product_condition',
			'btb_product_gtin',
			'btb_product_brand',
			'btb_product_tags',
			'btb_product_tax_class',
			'btb_product_terms',
			'btb_product_title',
			'btb_product_type',
			'btb_product_url'
		);

		$this->the_atts = array(
			'datetime_format' => get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
			'add_links' => 'yes',
			'apply_filters' => 'no',
			'days_to_cover' => 90,
			'hide_currency' => 'yes',
			'hide_if_no_sales' => 'no',
			'hide_if_zero' => 'no',
			'image_size' => 'shop_thumbnail',
			'length' => 0,
			'multiply_by' => '',
			'name' => '',
			'offset' => '',
			'order_status' => 'wc-completed',
			'precision' => 2,
			'product_id' => 0,
			'reverse' => 'no',
			'round'  => 'no',
			'sep' => ', ',
			'show_always' => 'yes',
			'taxonomy' => '',
			'to_unit' => '',
			'use_parent_id' => 'no',
		);

		$this->is_wc_version_below_3 = version_compare( get_option( 'woocommerce_version', null ), '3.0.0', '<' );

		parent::__construct();
	}

	function init_atts( $atts ) {

		// Atts
		if ( 0 == $atts['product_id'] ) {
			global $btb_product_id_for_shortcode;
			$atts['product_id'] = ( 0 != $btb_product_id_for_shortcode ? $btb_product_id_for_shortcode : get_the_ID() );
			if ( 0 == $atts['product_id'] ) {
				return false;
			}
		}

		// Checking post type
		$the_post_type = get_post_type( $atts['product_id'] );
		if ( 'product' !== $the_post_type && 'product_variation' !== $the_post_type ) {
			return false;
		}

		// Class properties
		$this->the_product = wc_get_product( $atts['product_id'] );
		if ( ! $this->the_product ) {
			return false;
		}

		// Maybe get parent product and ID
		if ( 'yes' === $atts['use_parent_id'] && 'product_variation' === $the_post_type ) {
			$atts['product_id'] = $this->the_product->get_parent_id();
			$this->the_product = wc_get_product( $atts['product_id'] );
			if ( ! $this->the_product ) {
				return false;
			}
		}

		return $atts;
	}

	function get_product_id( $_product ) {
		return ( $this->is_wc_version_below_3 ? $_product->id : $_product->get_id() );
	}

	function get_product_or_variation_parent_id( $_product ) {
		return ( $this->is_wc_version_below_3 ? $_product->id : ( $_product->is_type( 'variation' ) ? $_product->get_parent_id() : $_product->get_id() ) );
	}

	function get_product_short_description( $_product ) {
		if( $_product->is_type( 'variation' ) ){
			$parent_id = $_product->get_parent_id();
			$product = wc_get_product($parent_id);
			return $product->get_short_description();
		}else{
			return ( $this->is_wc_version_below_3 ? $_product->post->post_excerpt : $_product->get_short_description() );
		}
	}

	function get_product_tags( $_product, $sep ) {
		return ( $this->is_wc_version_below_3 ? $_product->get_tags( $sep ) : wc_get_product_tag_list( $_product->get_id(), $sep ) );
	}

	function get_product_categories( $_product ) {
		return ( $this->is_wc_version_below_3 ? $_product->get_categories() : wc_get_product_category_list( $_product->get_id() ) );
	}

	function list_product_attributes( $_product ) {
		if ( $this->is_wc_version_below_3 ) {
			$_product->list_attributes();
		} else {
			wc_display_product_attributes( $_product );
		}
	}

	function btb_product_id() {
		return $this->get_product_id( $this->the_product );
	}

	function btb_product_function( $atts ) {
		if ( ! isset( $atts['function'] ) ) {
			return '';
		}
		$function_name = $atts['function'];
		return ( is_callable( array( $this->the_product, $function_name ) ) ? $this->the_product->$function_name() : '' );
	}

	function btb_product_type() {
		return $this->the_product->get_type();
	}

	function btb_product_available_variations( $atts ) {
		if ( $this->the_product->is_type( 'variable' ) ) {
			$sep2 = ( isset( $atts['sep2'] ) ? $atts['sep2'] : ': ' );
			$sep3 = ( isset( $atts['sep3'] ) ? $atts['sep3'] : ' | ' );
			$variations = array();
			foreach ( $this->the_product->get_available_variations() as $variation ) {
				$attributes = array();
				foreach ( $variation['attributes'] as $attribute_slug => $attribute_name ) {
					if ( '' == $attribute_name ) {
						$attribute_name = __( 'Any', 'product-xml-feeds-for-woocommerce' );
					}
					$attributes[] = $attribute_name;
				}
				if ( ! empty( $attributes ) ) {
					$variations[] = implode( $atts['sep'], $attributes ) . $sep2 . $variation['price_html'];
				}
			}
			return ( ! empty( $variations ) ? implode( $sep3, $variations ) : '' );
		} else {
			return '';
		}
	}

	function btb_product_regular_price( $atts ) {
		if ( $this->the_product->is_on_sale() || 'yes' === $atts['show_always'] ) {
			if ( $this->the_product->is_type( 'variable' ) && ( ! isset( $atts['variable_price_type'] ) || 'range' === $atts['variable_price_type'] ) ) {
				// Variable
				$min = $this->the_product->get_variation_regular_price( 'min', false );
				$max = $this->the_product->get_variation_regular_price( 'max', false );
				if ( '' !== $atts['multiply_by'] && is_numeric( $atts['multiply_by'] ) ) {
					$min = $min * $atts['multiply_by'];
					$max = $max * $atts['multiply_by'];
				}
				if ( 'yes' !== $atts['hide_currency'] ) {
					$min = wc_price( $min );
					$max = wc_price( $max );
				}
				return ( $min != $max ) ? sprintf( '%s-%s', $min, $max ) : $min;
			} else {
				$the_price = $this->the_product->get_regular_price();
				return ( 'yes' === $atts['hide_currency'] ) ? $the_price : wc_price( $the_price );
			}
		}
		return '';
	}

	function btb_product_sale_price( $atts ) {
		if ( $this->the_product->is_on_sale() ) {
			if ( $this->the_product->is_type( 'variable' ) && ( ! isset( $atts['variable_price_type'] ) || 'range' === $atts['variable_price_type'] ) ) {
				// Variable
				$min = $this->the_product->get_variation_sale_price( 'min', false );
				$max = $this->the_product->get_variation_sale_price( 'max', false );
				if ( '' !== $atts['multiply_by'] && is_numeric( $atts['multiply_by'] ) ) {
					$min = $min * $atts['multiply_by'];
					$max = $max * $atts['multiply_by'];
				}
				if ( 'yes' !== $atts['hide_currency'] ) {
					$min = wc_price( $min );
					$max = wc_price( $max );
				}
				return ( $min != $max ) ? sprintf( '%s-%s', $min, $max ) : $min;
			} else {
				$the_price = $this->the_product->get_sale_price();
				return ( 'yes' === $atts['hide_currency'] ) ? $the_price : wc_price( $the_price );
			}
		}
		return '';
	}

	function btb_product_tax_class() {
		return $this->the_product->get_tax_class();
	}

	function btb_product_list_attributes() {
		$postID = $this->the_product->get_id();
		$oldAttr = get_post_meta($postID);
		if (array_key_exists('_product_attributes', $oldAttr) && !empty($oldAttr["_product_attributes"][0])) {
			$oldAttr = unserialize( $oldAttr["_product_attributes"][0] );
			$newAttr = [];
			$listAttr = '';
			foreach ($oldAttr as $key => $attr) {				
				foreach ( get_the_terms($postID, $attr['name']) as $key => $term) {
					if(!is_array($term) && term_exists($term->term_id)){
						$term->url = get_term_link($term->term_id, $term->taxonomy);
						$nameAttr = ucfirst(strtolower(str_replace(array('pa_', '-'), array('', ' '), $attr['name'])));
						$valueAttr = ucfirst(strtolower($term->name));
						$listAttr .= "\t" . '<g:product_detail>';
						$listAttr .= "\t\t" . '<g:attribute_name>'.$nameAttr.'</g:attribute_name>';
						$listAttr .= "\t\t" . '<g:attribute_value><![CDATA[ '.$valueAttr.' ]]></g:attribute_value>';
						$listAttr .= "\t" . '</g:product_detail>';
					}
				}
			}
			return $listAttr;
		}else{
			return '';
		}
	}

	function btb_product_list_attribute( $atts ) {
		return $this->the_product->get_attribute( $atts['name'] );
	}

	function btb_product_stock_quantity() {
		return ( '' != ( $stock_quantity = $this->the_product->get_stock_quantity() ) ? $stock_quantity : '' );
	}

	function btb_product_gtin() {
		return '';
	}

	function btb_product_brand() {
		$product_id = $this->get_product_id( $this->the_product );
		$_brand = get_the_terms( $product_id, 'product_brand' );
		if(!is_wp_error($_brand)){
			return $_brand[0]->name;
		}else{
			return get_bloginfo( 'name' );
		}
	}

	function btb_product_average_rating() {
		return $this->the_product->get_average_rating();
	}

	function btb_product_categories() {
		return $this->get_product_categories( $this->the_product );
	}
	function btb_product_name() {
		return $this->the_product->get_name();
	}

	function btb_product_stock_availability() {
		$stock_availability_array = $this->the_product->get_availability();
		return ( $stock_availability_array['availability'] ) ? $stock_availability_array['availability'] : 'in stock';
	}

	function btb_product_condition() {
		return 'new';
	}

	function btb_product_shipping() {
		$zone_id = get_option('_btb_id_zone_shipping', 0);
		if($zone_id){
			$shipping_zone = new WC_Shipping_Zone($zone_id);
			$shipping_methods = $shipping_zone->get_shipping_methods( true, 'values' );
			foreach ( $shipping_methods as $shipping_method ) {
				if($shipping_method->cost){
					$price = str_replace(',', '.', $shipping_method->cost);
					return $price.' '.get_woocommerce_currency();
				}
			}
		}else{
			return '0 '.get_woocommerce_currency();
		}
	}

	function btb_product_shipping_class() {
		$shipping_classes = get_terms( array('taxonomy' => 'product_shipping_class', 'hide_empty' => false ) );
		if ( $shipping_classes ) {
			return $shipping_classes[0]->name;
		}
		return '';
	}

	function btb_product_tags( $atts ) {
		if ( 'yes' === $atts['add_links'] ) {
			return $this->get_product_tags( $this->the_product, $atts['sep'] );
		}
		$product_tags = get_the_terms( $atts['product_id'], 'product_tag' );
		$product_tags_names = array();
		foreach ( $product_tags as $product_tag ) {
			$product_tags_names[] = $product_tag->name;
		}
		return implode( $atts['sep'], $product_tags_names );
	}

	function btb_product_meta( $atts ) {
		if(isset($atts['product_id'])) {
			$meta = get_post_meta( $atts['product_id'], $atts['name'], true );
			if(isset($atts['array_key'])){
				$key = $atts['array_key'];
				if(isset($meta[$key])){
					$key_child = $atts['array_key_child'];
					if(isset($meta[$key][$key_child])){
						return $meta[$key][$key_child];
					}
					return $meta[$key];
				}
			}
			return $meta;
		} else {
			return get_post_meta( $this->the_product->get_id(), $atts['name'], true );
		}
	}

	function btb_product_custom_field( $atts ) {
		$product_custom_fields = get_post_custom( $atts['product_id'] );
		return ( isset( $product_custom_fields[ $atts['name'] ][0] ) ) ? $product_custom_fields[ $atts['name'] ][0] : '';
	}

	function btb_product_price( $atts ) {
		if ( $this->the_product->is_type( 'variable' ) && ( ! isset( $atts['variable_price_type'] ) || 'range' === $atts['variable_price_type'] ) ) {
			$min = $this->the_product->get_variation_price( 'min', true );
			$price = str_replace([',',get_woocommerce_currency_symbol()], ['.',''], $min);
			return $price.' '.get_woocommerce_currency();
		} else {
			$price = $this->the_product->get_price();
			if ( '' !== $atts['multiply_by'] && is_numeric( $atts['multiply_by'] ) ) {
				$price = $price * $atts['multiply_by'];
			}
			if('yes' === $atts['hide_currency']){
				$finalPrice = $price;
			}else{
				$finalPrice = wc_price( $price );
			}
			return $finalPrice.' '.get_woocommerce_currency();
		}
	}

	function btb_product_description() {
		if( $this->the_product->is_type( 'variation' ) ){
			$parent_id = $this->the_product->get_parent_id();
			$product = wc_get_product($parent_id);
			return $product->get_description();
		}else{
			return $this->the_product->get_description();
		}
	}

	function btb_product_short_description( $atts ) {
		$short_description = $this->get_product_short_description( $this->the_product );
		if ( 'yes' === $atts['apply_filters'] ) {
			apply_filters( 'woocommerce_short_description', $short_description );
		}
		if ( 0 != $atts['length'] ) {
			$excerpt_more = apply_filters( 'excerpt_more', ' ' . '[&hellip;]' );
			$short_description = wp_trim_words( $short_description, $atts['length'], $excerpt_more );
		}
		return $short_description;
	}

	function custom_excerpt_length() {
		return $this->product_excerpt_length;
	}

	function btb_product_excerpt( $atts ) {
		$the_excerpt = $this->btb_product_short_description( $atts );
		if ( '' === $the_excerpt ) {
			if ( 0 != $atts['length'] ) {
				$this->product_excerpt_length = $atts['length'];
				add_filter( 'excerpt_length', array( $this, 'custom_excerpt_length' ), PHP_INT_MAX );
				$the_excerpt = get_the_excerpt( $atts['product_id'] );
				remove_filter( 'excerpt_length', array( $this, 'custom_excerpt_length' ), PHP_INT_MAX );
			} else {
				$the_excerpt = get_the_excerpt( $atts['product_id'] );
			}
		}
		return $the_excerpt;
	}

	function btb_product_sku() {
		$sku = $this->the_product->get_sku();
		if($sku != ''){
			return $sku;
		}else{
			return '-';
		}
	}

	function btb_product_identifier() {
		if($this->the_product->get_sku()){
			return 'yes';
		}else{
			return 'no';
		}
	}

	function btb_product_title() {
		return $this->the_product->get_title();
	}

	function btb_product_image_url( $atts ) {
		$product_id = $this->get_product_id( $this->the_product );
		$image_size = $atts['image_size'];
		if ( has_post_thumbnail( $product_id ) ) {
			$image_url = get_the_post_thumbnail_url( $product_id, $image_size );
		} elseif ( ( $parent_id = wp_get_post_parent_id( $product_id ) ) && has_post_thumbnail( $parent_id ) ) {
			$image_url = get_the_post_thumbnail_url( $parent_id, $image_size );
		} else {
			$image_url = wc_placeholder_img_src();
		}
		return $image_url;
	}

	function btb_product_url() {
		return $this->the_product->get_permalink();
	}

	function btb_product_categories_names( $atts ) {
		$product_cats = get_the_terms( $this->get_product_or_variation_parent_id( $this->the_product ), 'product_cat' );
		$cats = array();
		if ( ! empty( $product_cats ) && is_array( $product_cats ) ) {
			foreach ( $product_cats as $product_cat ) {
				$cats[] = $product_cat->name;
			}
		}
		return implode( $atts['sep'], $cats );
	}

}

endif;

return new Btb_Product_Shortcodes();