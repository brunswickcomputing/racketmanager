<?php
defined('ABSPATH') or die("Access denied !");

/**
 * Helper and Util functions
 *
 * @package racketmanager
 * @subpackage include
 * @since 1.0.0
 * @author PaulMoffat
 *
 */
class Racketmanager_Util
{

	/**
	 * get upload directory
	 *
	 * @param string|false $file
	 * @return string upload path
	 */
	public static function getFilePath($file = false)
	{
		$base = WP_CONTENT_DIR . '/uploads/leagues';

		if ($file) {
			return $base . '/' . basename($file);
		} else {
			return $base;
		}
	}

	/**
	 * Add pages to database
	 */
	public static function addRacketManagerPage($pageDefinitions)
	{

		foreach ($pageDefinitions as $slug => $page) {

			// Check that the page doesn't exist already
			if (!is_page($slug)) {
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
				if ($pageId = wp_insert_post($page)) {
					$pageName = sanitize_title_with_dashes($page['post_title']);
					$option = 'racketmanager_page_' . $pageName . '_id';
					// Only update this option if `wp_insert_post()` was successful
					update_option($option, $pageId);
				}
			}
		}
	}

	public static function getCompetitionType($type)
	{
		switch ($type) {
			case 'WS':
				$desc = __('Ladies Singles', 'racketmanager');
				break;
			case 'WD':
				$desc = __('Ladies Doubles', 'racketmanager');
				break;
			case 'MS':
				$desc = __('Mens Singles', 'racketmanager');
				break;
			case 'MD':
				$desc = __('Mens Doubles', 'racketmanager');
				break;
			case 'XD':
				$desc = __('Mixed Doubles', 'racketmanager');
				break;
			case 'LD':
				$desc = __('The League', 'racketmanager');
				break;
			default:
				$desc = __('Unknown', 'racketmanager');
		}
		return $desc;
	}

	/**
	 * get available league standing status
	 *
	 * @return array
	 */
	public static function getStandingStatus()
	{
		$standingStatus = array();
		$standingStatus['C'] = __('Champions', 'racketmanager');
		$standingStatus['P1'] = __('Promoted in first place', 'racketmanager');
		$standingStatus['P2'] = __('Promoted in second place', 'racketmanager');
		$standingStatus['P3'] = __('Promoted in third place', 'racketmanager');
		$standingStatus['W1'] = __('League winners but league locked', 'racketmanager');
		$standingStatus['W2'] = __('Second place but league locked', 'racketmanager');
		$standingStatus['RB'] = __('Relegated in bottom place', 'racketmanager');
		$standingStatus['RQ'] = __('Relegated by request', 'racketmanager');
		$standingStatus['RT'] = __('Relegated as team in division above', 'racketmanager');
		$standingStatus['BT'] = __('Finished bottom but not relegated', 'racketmanager');
		$standingStatus['NT'] = __('New team', 'racketmanager');
		$standingStatus['W'] = __('Withdrawn', 'racketmanager');
		return $standingStatus;
	}

	/**
	 * get available competition types
	 *
	 * @return array
	 */
	public static function getCompetitionTypes()
	{
		$competitionTypes= array();
		$competitionTypes['cup'] = __('cup', 'racketmanager');
		$competitionTypes['league'] = __('league', 'racketmanager');
		$competitionTypes['tournament'] = __('tournament', 'racketmanager');
		return $competitionTypes;
	}

	/**
	 * get available league modes
	 *
	 * @return array
	 */
	public static function getModes()
	{
		$modes = array();
		$modes['default'] = __('Default', 'racketmanager');
		/**
		 * Fired when league modes are built
		 *
		 * @param array $modes
		 * @return array
		 * @category wp-filter
		 */
		$modes = apply_filters('racketmanager_modes', $modes);
		return $modes;
	}

	/**
	 * get available entry types
	 *
	 * @return array
	 */
	public static function getEntryTypes()
	{
		$entryTypes = array();
		$entryTypes['team'] = __('Team', 'racketmanager');
		$entryTypes['player'] = __('Player', 'racketmanager');
		return $entryTypes;
	}

	/**
	 * get array of supported point rules
	 *
	 * @return array
	 */
	public static function getPointRules()
	{
		$rules = array();
		$rules['manual'] = __('Update Standings Manually', 'racketmanager');
		$rules['one'] = __('One-Point-Rule', 'racketmanager');
		$rules['two'] = __('Two-Point-Rule', 'racketmanager');
		$rules['three'] = __('Three-Point-Rule', 'racketmanager');
		$rules['score'] = __('Score', 'racketmanager');
		$rules['user'] = __('User defined', 'racketmanager');

		/**
		 * Fired when league point rules are built
		 *
		 * @param array $rules
		 * @return array
		 * @category wp-filter
		 */
		$rules = apply_filters('racketmanager_point_rules_list', $rules);
		asort($rules);

		return $rules;
	}

	/**
	 * get available point formats
	 *
	 * @return array
	 */
	public static function getPointFormats()
	{
		$point_formats = array();
		$point_formats['%s:%s'] = '%s:%s';
		$point_formats['%s'] = '%s';
		$point_formats['%d:%d'] = '%d:%d';
		$point_formats['%d - %d'] = '%d - %d';
		$point_formats['%d'] = '%d';
		$point_formats['%.1f:%.1f'] = '%f:%f';
		$point_formats['%.1f - %.1f'] = '%f - %f';
		$point_formats['%.1f'] = '%f';
		/**
		 * Fired when league point formats are built
		 *
		 * @param array $point_formats
		 * @return array
		 * @category wp-filter
		 */
		$point_formats = apply_filters('racketmanager_point_formats', $point_formats);
		return $point_formats;
	}

	/**
	 * gets club player requests from database
	 *
	 * @param array $query_args
	 * @return array
	 */
	public static function getPlayerRequests($query_args)
	{
		global $wpdb;

		$defaults = array('count' => false, 'club' => false, 'status' => false, 'orderby' => array('requested_date' => 'DESC', 'completed_date' => 'DESC', 'surname' => 'DESC', 'first_name' => 'DESC'));
		$query_args = array_merge($defaults, (array)$query_args);
		extract($query_args, EXTR_SKIP);

		$search_terms = array();
		$sql = "SELECT `id`, `first_name`, `surname`, `player_id`, `affiliatedclub`, `requested_date`, `requested_user`, `completed_date`, `completed_user`, `gender`, `btm`, `email` FROM {$wpdb->racketmanager_club_player_requests} WHERE 1 = 1";

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
			$sql = $sql = "SELECT COUNT(ID) FROM {$wpdb->racketmanager_club_player_requests} WHERE 1 = 1";
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

		$playerRequests = wp_cache_get(md5($sql), 'playerRequests');
		if (!$playerRequests) {
			$playerRequests = $wpdb->get_results($sql);
			wp_cache_set(md5($sql), $playerRequests, 'playerRequests');
		}

		$class = '';
		foreach ($playerRequests as $i => $playerRequest) {
			$class = ('alternate' == $class) ? '' : 'alternate';
			$playerRequest->class = $class;
			if ($playerRequest->player_id != 0) {
				$player = get_player($playerRequest->player_id);
				$playerRequest->first_name = $player->firstname;
				$playerRequest->surname = $player->surname;
				$playerRequest->gender = $player->gender;
				$playerRequest->btm = $player->btm;
				$playerRequest->email = $player->email;
			}
			$playerRequest->clubName = get_club($playerRequest->affiliatedclub)->name;
			$playerRequest->requestedUserId = $playerRequest->requested_user;
			$playerRequest->requestedUser = get_userdata($playerRequest->requested_user)->display_name;
			$playerRequest->completedUserId = $playerRequest->completed_user;
			if ($playerRequest->completed_user != '') {
				$playerRequest->completedUser = get_userdata($playerRequest->completed_user)->display_name;
			} else {
				$playerRequest->completedUser = '';
			}

			$playerRequests[$i] = $playerRequest;
		}

		return $playerRequests;
	}
}
