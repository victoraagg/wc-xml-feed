<?php
/*
 * @wordpress-plugin
 * Plugin Name: WooCommerce XML feed
 * Description: Generador de feed XML
 * Plugin URI: https://www.bthebrand.es
 * Version: 1.0.0
 * Author: bthebrand
 * Author URI: https://www.bthebrand.es
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Tested up to: 5.9
 * WC tested up to: 6.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! defined( 'BWA_PLUGIN_FILE' ) ) {
    define( 'WCFEED_PLUGIN_FILE', __FILE__ );
    define( 'WCFEED_ABSPATH', dirname( WCFEED_PLUGIN_FILE ) . '/' );
}

class wooCommerceFeed {

    public function __construct() {
        $this->includes();
    }

    public function includes() {
        require_once( WCFEED_ABSPATH.'shortcodes/parent.php' );
		require_once( WCFEED_ABSPATH.'shortcodes/products.php' );
        require_once( WCFEED_ABSPATH.'wc-xml-feed.php' );
        $this->xml = new WC_XML_Feed();
    }
    
}

$wooCommerceFeed = new wooCommerceFeed();