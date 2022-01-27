<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Btb_Shortcodes {

	public function __construct() {
		foreach( $this->the_shortcodes as $the_shortcode ) {
			add_shortcode( $the_shortcode, array( $this, 'btb_shortcode' ) );
		}
		add_filter( 'btb_shortcodes_list', array( $this, 'add_shortcodes_to_the_list' ) );
	}

	function add_extra_atts( $atts ) {
		if ( ! isset( $this->the_atts ) ) {
			$this->the_atts = array();
		}
		$final_atts = array_merge( $this->the_atts, $atts );
		return $final_atts;
	}

	function init_atts( $atts ) {
		return $atts;
	}

	function add_shortcodes_to_the_list( $shortcodes_list ) {
		foreach( $this->the_shortcodes as $the_shortcode ) {
			$shortcodes_list[] = $the_shortcode;
		}
		return $shortcodes_list;
	}

	function btb_shortcode( $atts, $content, $shortcode ) {

		if ( empty( $atts ) ) {
			$atts = array();
		}

		$atts = $this->add_extra_atts( $atts );

		$global_defaults = array(
			'before' => '',
			'after' => '',
			'find' => '',
			'replace' => '',
			'find_replace_sep' => '',
			'on_empty' => '',
			'on_empty_apply_shortcodes'  => 'no',
			'convert_currency_from' => '',
			'convert_currency_to' => '',
			'convert_currency_precision' => 2,
			'custom_function' => '',
			'strip_tags' => 'yes',
			'strip_shortcodes' => 'no',
			'cdata' => 'no',
		);
		$atts = array_merge( $global_defaults, $atts );

		// Check for required atts
		if ( false === ( $atts = $this->init_atts( $atts ) ) ) {
			return '';
		}

		// Run the shortcode function
		$shortcode_function = $shortcode;
		if ( '' !== ( $result = $this->$shortcode_function( $atts, $content ) ) ) {
			// Strip tags
			if ( 'yes' === $atts['strip_tags'] ) {
				$result = strip_tags( $result );
			}
			// Strip shortcodes
			if ( 'no' != $atts['strip_shortcodes'] ) {
				$result = ( 'yes' === $atts['strip_shortcodes'] ?
					strip_shortcodes( $result ) :
					preg_replace( "(\[(?:\[??[^\[]*?\]))", '', $result ) // `yes-force`
				);
			}
			// CDATA
			if ( 'yes' === $atts['cdata'] ) {
				$result = '<![CDATA[' . $result . ']]>';
			}
			// Before/After
			return $atts['before'] . $result . $atts['after'];
		} else {
			// On empty
			return ( 'yes' === $atts['on_empty_apply_shortcodes'] ? do_shortcode( str_replace( array( '{', '}' ), array( '[', ']' ), $atts['on_empty'] ) ) : $atts['on_empty'] );
		}
	}

}