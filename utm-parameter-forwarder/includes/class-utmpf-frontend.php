<?php
/**
 * Front end: loads the small script that forwards the parameters.
 *
 * All work happens in the visitor's browser. The server never reads,
 * stores or echoes the incoming parameter values.
 *
 * @package UTM_Parameter_Forwarder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enqueues the forwarding script.
 */
final class UTMPF_Frontend {

	const HANDLE = 'utm-parameter-forwarder';

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	/**
	 * Enqueue the script with its configuration.
	 */
	public static function enqueue() {
		$settings = UTMPF_Settings::get();

		wp_register_script(
			self::HANDLE,
			UTMPF_URL . 'assets/js/utm-forwarder.js',
			array(),
			UTMPF_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		$home_host = wp_parse_url( home_url(), PHP_URL_HOST );

		// Only static, admin-controlled values go into the page. No request data.
		$config = array(
			'params'  => array_values( $settings['params'] ),
			'persist' => (bool) $settings['persist'],
			'hosts'   => array_values( array_filter( array( is_string( $home_host ) ? strtolower( $home_host ) : '' ) ) ),
		);

		wp_add_inline_script(
			self::HANDLE,
			'window.utmpfConfig = ' . wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';',
			'before'
		);

		wp_enqueue_script( self::HANDLE );
	}
}
