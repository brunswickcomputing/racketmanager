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

	/**
	* get available competition types
	*
	* @return array
	*/
	static function getCompetitionTypes() {
		$competitionTypes = array( 'cup' => __('cup', 'racketmanager'), 'league' => __('league', 'racketmanager'), 'tournament' => __('tournament', 'racketmanager') );
		return $competitionTypes;
	}

	/**
	* get available league modes
	*
	* @return array
	*/
	static function getModes() {
		$modes = array( 'default' => __('Default', 'racketmanager') );
		/**
		* Fired when league modes are built
		*
		* @param array $modes
		* @return array
		* @category wp-filter
		*/
		$modes = apply_filters( 'racketmanager_modes', $modes);
		return $modes;
	}

	/**
	* get available entry types
	*
	* @return array
	*/
	static function getEntryTypes() {
		$entryTypes = array( 'team' => __('Team', 'racketmanager'), 'player' => __('Player', 'racketmanager') );
		return $entryTypes;
	}

	/**
	* get array of supported point rules
	*
	* @return array
	*/
	static function getPointRules() {
		$rules = array( 'manual' => __( 'Update Standings Manually', 'racketmanager' ), 'one' => __( 'One-Point-Rule', 'racketmanager' ), 'two' => __('Two-Point-Rule','racketmanager'), 'three' => __('Three-Point-Rule', 'racketmanager'), 'score' => __( 'Score', 'racketmanager'), 'user' => __('User defined', 'racketmanager') );

		/**
		* Fired when league point rules are built
		*
		* @param array $rules
		* @return array
		* @category wp-filter
		*/
		$rules = apply_filters( 'racketmanager_point_rules_list', $rules );
		asort($rules);

		return $rules;
	}

	/**
	* get available point formats
	*
	* @return array
	*/
	static function getPointFormats() {
		$point_formats = array( '%s:%s' => '%s:%s', '%s' => '%s', '%d:%d' => '%d:%d', '%d - %d' => '%d - %d', '%d' => '%d', '%.1f:%.1f' => '%f:%f', '%.1f - %.1f' => '%f - %f', '%.1f' => '%f' );
		/**
		* Fired when league point formats are built
		*
		* @param array $point_formats
		* @return array
		* @category wp-filter
		*/
		$point_formats = apply_filters( 'racketmanager_point_formats', $point_formats );
		return $point_formats;
	}

}
