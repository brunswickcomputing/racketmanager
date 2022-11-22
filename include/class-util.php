<?php
defined( 'ABSPATH' ) or die( "Access denied !" );

/**
 * Helper and Util functions
 *
 * @package racketmanager
 * @subpackage include
 * @since 1.0.0
 * @author PaulMoffat
 *
 */
class Racketmanager_Util {

  /**
	* get upload directory
	*
	* @param string|false $file
	* @return string upload path
	*/
	static function getFilePath( $file = false ) {
		$base = WP_CONTENT_DIR.'/uploads/leagues';

		if ( $file ) {
			return $base .'/'. basename($file);
		} else {
			return $base;
		}
	}

}
