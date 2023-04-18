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
			,'RQ' => __( 'Relegated by request', 'racketmanager')
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

	/**
	 * gets roster requests from database
	 *
	 * @param array $query_args
	 * @return array
	 */
	public static function getRosterRequests($query_args)
	{
		global $wpdb;

		$defaults = array('count' => false, 'club' => false, 'status' => false, 'orderby' => array('requested_date' => 'DESC', 'completed_date' => 'DESC', 'surname' => 'DESC', 'first_name' => 'DESC'));
		$query_args = array_merge($defaults, (array)$query_args);
		extract($query_args, EXTR_SKIP);

		$search_terms = array();
		$sql = "SELECT `id`, `first_name`, `surname`, `affiliatedclub`, `requested_date`, `requested_user`, `completed_date`, `completed_user`, `gender`, `btm`, `email` FROM {$wpdb->racketmanager_roster_requests} WHERE 1 = 1";

		if ($club) {
			if ( $club != 'all') {
				$search_terms[] = $wpdb->prepare("`affiliatedclub` = '%s'", $club);
			}
		}
		if ($status) {
			if ($status == 'outstanding') {
				$search_terms[] = "`completed_date` IS NULL";
			}
		}
		$search = "";
		if (!empty($search_terms)) {
			$search = implode(" AND ", $search_terms);
		}

		if ($count) {
			$sql = $sql = "SELECT COUNT(ID) FROM {$wpdb->racketmanager_roster_requests} WHERE 1 = 1";
			if ($search != "") {
				$sql .= " AND $search";
			}
			return $wpdb->get_var($sql);
		}

		$orderby_string = "";
		$i = 0;
		foreach ($orderby as $order => $direction) {
			if (!in_array($direction, array("DESC", "ASC", "desc", "asc"))) {
				$direction = "ASC";
			}
			$orderby_string .= "`" . $order . "` " . $direction;
			if ($i < (count($orderby) - 1)) {
				$orderby_string .= ",";
			}
			$i++;
		}
		$order = $orderby_string;
		if ($search != "") {
			$sql .= " AND $search";
		}
		if ($order != "") {
			$sql .= " ORDER BY $order";
		}

		$rosterRequests = wp_cache_get(md5($sql), 'rosterRequests');
		if (!$rosterRequests) {
			$rosterRequests = $wpdb->get_results($sql);
			wp_cache_set(md5($sql), $rosterRequests, 'rosterRequests');
		}

		$class = '';
		foreach ($rosterRequests as $i => $rosterRequest) {
			$class = ('alternate' == $class) ? '' : 'alternate';
			$rosterRequest->class = $class;
			$rosterRequest->clubName = get_club($rosterRequest->affiliatedclub)->name;
			$rosterRequest->requestedUserId = $rosterRequest->requested_user;
			$rosterRequest->requestedUser = get_userdata($rosterRequest->requested_user)->display_name;
			$rosterRequest->completedUserId = $rosterRequest->completed_user;
			if ($rosterRequest->completed_user != '') {
				$rosterRequest->completedUser = get_userdata($rosterRequest->completed_user)->display_name;
			} else {
				$rosterRequest->completedUser = '';
			}

			$rosterRequests[$i] = $rosterRequest;
		}

		return $rosterRequests;
	}
}
