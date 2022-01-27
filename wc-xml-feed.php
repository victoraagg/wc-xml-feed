<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WC_XML_Feed {

	public $path_xml = 'wp-content/feeds/';
	private $optionsField = [];

	function __construct() {
		add_action( 'init', array( $this, 'init_create_products_xml' ) );
		add_action( 'admin_menu', array($this, 'admin_menu') );
		$this->optionsField = [
			'_btb_google_product_category' => ['ID categoría Google', 'text'],
			'_btb_id_zone_shipping' => ['ID zona de envío', 'text'],
			'_btb_code_shipping_country' => ['Código zona de envío', 'text']
		];
	}

	public function admin_menu() {
        add_menu_page(
            'Feeds XML', 
            'Feeds XML', 
            'manage_categories', 
            'generate-xml', 
            array($this, 'set_xml_view')
        );
	}
	
	public function set_xml_view() {	
		$hidden_field_name = 'btb_options_hidden';
		foreach ($this->optionsField as $key => $option) {
			$opt_val = get_option($key);
			if (!empty($opt_val)) {
				array_push($this->optionsField[$key], $opt_val);
			} else {
				array_push($this->optionsField[$key], '');
			}
		}
		if (isset($_POST[$hidden_field_name]) && $_POST[$hidden_field_name] == 'Y') {
			foreach ($_POST as $key => $value) {
				$datatype = substr($key, 0, 5);
				if ($datatype == '_btb_') {
					update_option($key, $value);
				}
			}
			echo '<div class="updated"><p><strong>'.__('Guardado', 'btb').'</strong></p></div>';
		}
		$view = home_url()."/?woocommerce_gpf=google";
		$generate = home_url()."/?woocommerce_gpf=create";
		echo '<div class="wrap">';
		echo '<a href="'.$view.'" target="_blank" class="button-primary">Ver XML publicado</a>';
		echo '<hr>';
		echo '<a href="'.$generate.'" target="_blank" class="button-primary">Generar nuevo XML</a>';
		echo '<hr>';
		echo '<form name="options" method="post" action="">';
		echo '<input type="hidden" name="'.$hidden_field_name.'" value="Y">';
		foreach ($this->optionsField as $key => $option) {
			echo '<label for="'.$key.'">'.$option[0].'</label>';
			echo '<br>';
			echo '<input id="'.$key.'" type="'.$option[1].'" name="'.$key.'" value="'.$option[2].'">';
			echo '<hr>';
		}
		echo '<input type="submit" name="submit" class="button-primary" value="'.__('Guardar', 'btb').'" />';
		echo '</form>';
		echo '</div>';
    }

	function init_create_products_xml() {
		$file_name = 'wc_'.date('YmdHis');
		if ( isset( $_GET['woocommerce_gpf'] ) ) {
			switch ($_GET['woocommerce_gpf']) {
				case 'google':
					$files = scandir(ABSPATH.$this->path_xml, SCANDIR_SORT_DESCENDING);
					$result = ABSPATH.$this->path_xml.$files[0];
					break;
				case 'create':
					$result = $this->create_products_xml( $file_name );
					break;
				default:
					$result = '';
					break;
			}
			if (file_exists($result)) {
				header($_SERVER["SERVER_PROTOCOL"] . " 200 OK");
				header("Cache-Control: public");
				header("Content-Type: text/xml");
				header("Content-Transfer-Encoding: Binary");
				header("Content-Length:".filesize($result));
				readfile($result);
				die();        
			} else {
				die("Error: File not found.");
			} 
		}
	}

	function get_default_template( $part ) {
		switch ( $part ) {
			case 'header':
				return '<?xml version="1.0" encoding="utf-8" ?>' . PHP_EOL .
				'<rss xmlns:g="http://base.google.com/ns/1.0" xmlns:c="http://base.google.com/cns/1.0" version="2.0">' . PHP_EOL .
				'<channel>' . PHP_EOL .
				'<title><![CDATA[ Products Feed ]]></title>' . PHP_EOL .
				'<link>'.home_url().'/?woocommerce_gpf=google</link>' . PHP_EOL .
				'<description>Products Feed - '.get_bloginfo( 'name' ).'</description>' . PHP_EOL;
			case 'item':
				return '<item>' . PHP_EOL .
				"\t" . '<g:id>[btb_product_id]</g:id>' . PHP_EOL .
				"\t" . '<title>[btb_product_name cdata="yes"]</title>' . PHP_EOL .
				"\t" . '<description>[btb_product_short_description cdata="yes"]</description>' . PHP_EOL .
				"\t" . '<g:item_group_id>[btb_product_id]</g:item_group_id>' . PHP_EOL .
				"\t" . '<link>[btb_product_url cdata="yes"]</link>' . PHP_EOL .
				"\t" . '<g:product_type>[btb_product_categories cdata="yes"]</g:product_type>' . PHP_EOL .
				"\t" . '<g:google_product_category>'.get_option('_btb_google_product_category', '0').'</g:google_product_category>' . PHP_EOL .
				"\t" . '<g:image_link>[btb_product_image_url image_size="full" cdata="yes"]</g:image_link>' . PHP_EOL .
				"\t" . '<g:condition>[btb_product_condition]</g:condition>' . PHP_EOL .
				"\t" . '<g:price>[btb_product_price hide_currency="yes"]</g:price>' . PHP_EOL .
				"\t" . '<g:mpn>[btb_product_sku]</g:mpn>' . PHP_EOL .
				"\t" . '<g:brand>[btb_product_brand cdata="yes"]</g:brand>' . PHP_EOL .
				"\t" . '<g:availability>[btb_product_stock_availability]</g:availability>' . PHP_EOL .
				"\t" . '<g:shipping>' . PHP_EOL .
				"\t\t" . '<g:country>'.get_option('_btb_code_shipping_country', 'ES').'</g:country>' . PHP_EOL .
				"\t\t" . '<g:price>[btb_product_shipping]</g:price>' . PHP_EOL .
				"\t\t" . '<g:service>[btb_product_shipping_class]</g:service>' . PHP_EOL .
				"\t" . '</g:shipping>' . PHP_EOL .
				"\t" . '<g:identifier_exists>[btb_product_identifier]</g:identifier_exists>' . PHP_EOL .
				'</item>' . PHP_EOL;
			case 'footer':
				return '</channel>' . PHP_EOL .
				'</rss>';
		}
	}

	function create_products_xml( $file_name ) {
		// Time limit (will be used in loop) in seconds
		$php_time_limit = 30;
		// Get options
		$xml_header_template = $this->get_default_template( 'header' );
		$xml_footer_template = $this->get_default_template( 'footer' );
		$xml_item_template = $this->get_default_template( 'item' );
		$sorting_orderby = 'meta_value_num';
		$sorting_order = 'DESC';
		$meta_key = 'total_sales';
		$offset = 0;
		$total_products = 5000;
		$query_post_type = array( 'product' );

		// Get products and feed
		$xml_items = '';
		$block_size = 512;
		$current_products = 0;

		while ( true ) {
			// Time limit
			if ( -1 != $php_time_limit ) {
				set_time_limit( $php_time_limit );
			}
			// Args
			$args = array(
				'post_type' => $query_post_type,
				'post_status' => 'publish',
				'posts_per_page' => $block_size,
				'meta_key' => $meta_key,
				'orderby' => $sorting_orderby,
				'order' => $sorting_order,
				'offset' => $offset,
			);
			$loop = new WP_Query( $args );

			if ( !$loop->have_posts() ) {
				break;
			}

			while ( $loop->have_posts() ) {
				$loop->the_post();
				$_product = wc_get_product( get_the_ID() );
				if($_product->is_in_stock() && $_product->get_price() != 0 && $_product->get_catalog_visibility() != 'hidden'){
					$xml_items .= str_replace( '&', '&amp;', html_entity_decode( do_shortcode( $xml_item_template ) ) );
				}
				$current_products++;
				if ( 0 != $total_products && $current_products >= $total_products ) {
					break;
				}
			}
			$offset += $block_size;
			if ( 0 != $total_products && $current_products >= $total_products ) {
				break;
			}
		}
		
		wp_reset_postdata();

		// Create XML feed file
		if (!file_exists($this->path_xml)) {
			mkdir($this->path_xml, 0777, true);
		}
		$route = $this->path_xml . $file_name . '.xml';
		file_put_contents( ABSPATH.$route, do_shortcode( $xml_header_template ) . $xml_items . do_shortcode( $xml_footer_template ) );
		return $route;
	}

}