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

	/**
	* Add pages to database
	*/
	static function addRacketManagerPage( $pageDefinitions ) {

		foreach ( $pageDefinitions as $slug => $page ) {

			// Check that the page doesn't exist already
			if ( ! is_page($slug) ) {
				$pageTemplate = $page['page_template'];
				// Add the page using the data from the array above
				$page = array(
					'post_content'   => $page['content'],
					'post_name'      => $slug,
					'post_title'     => $page['title'],
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'ping_status'    => 'closed',
					'comment_status' => 'closed',
					'page_template' => $pageTemplate,
				);
				if ( $pageId = wp_insert_post( $page ) ) {
					$pageName = sanitize_title_with_dashes($page['post_title']);
					$option = 'racketmanager_page_'.$pageName.'_id';
					// Only update this option if `wp_insert_post()` was successful
					update_option( $option, $pageId );
				}
			}
		}
	}

	static function getCompetitionType( $type ) {
		switch ($type) {
			case 'WS': $desc = __( 'Ladies Singles', 'racketmanager' ); break;
			case 'WD': $desc = __( 'Ladies Doubles', 'racketmanager' ); break;
			case 'MS': $desc = __( 'Mens Singles', 'racketmanager' ); break;
			case 'MD': $desc = __( 'Mens Doubles', 'racketmanager' ); break;
			case 'XD': $desc = __( 'Mixed Doubles', 'racketmanager' ); break;
			case 'LD': $desc = __( 'The League', 'racketmanager' ); break;
			default: $desc = __('Unknown', 'racketmanager');
		}
		return $desc;
	}

	/**
	* get available league standing status
	*
	* @return array
	*/
	static function getStandingStatus() {
		return array(
			'C' => __( 'Champions', 'racketmanager' )
			,'P1' => __( 'Promoted in first place', 'racketmanager')
			,'P2' => __( 'Promoted in second place', 'racketmanager')
			,'P3' => __( 'Promoted in third place', 'racketmanager')
			,'W1' => __( 'League winners but league locked', 'racketmanager')
			,'W2' => __( 'Second place but league locked', 'racketmanager')
			,'RB' => __( 'Relegated in bottom place', 'racketmanager')
			,'RT' => __( 'Relegated as team in division above', 'racketmanager')
			,'BT' => __( 'Finished bottom but not relegated', 'racketmanager')
			,'NT' => __( 'New team', 'racketmanager')
			,'W' => __( 'Withdrawn', 'racketmanager')
		);
	}

}
