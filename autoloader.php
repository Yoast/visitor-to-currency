<?php
/**
 * Plugin Name: Yoast.com settings
 * Description: Adds settings to manage yoast.com, adds meta fields, setting screens, etc.
 * Author: Team Yoast
 * Author URI:  https://yoast.com
 */

namespace Yoast\YoastCom\VisitorCurrency;

spl_autoload_register( function( $classname ) {
	if ( false !== strpos( $classname, 'Yoast\\YoastCom\\VisitorCurrency\\' ) ) {
		$classname = str_replace( 'Yoast\\YoastCom\\VisitorCurrency\\', '', $classname );

		$classname = strtolower( $classname );
		$classname = str_replace( '_', '-', $classname );

		require_once( __DIR__ . '/php/class-' . $classname . '.php' );
	}
});
