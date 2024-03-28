<?php
/**
 * RacketManager_Util API: RacketManager-util class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Util
 */

namespace Racketmanager;

defined( 'ABSPATH' ) || die( 'Access denied !' );
/**
 * Helper and Util functions
 *
 * @package racketmanager
 * @subpackage include
 * @since 1.0.0
 * @author PaulMoffat
 */
class Racketmanager_Util {

	/**
	 * Get upload directory
	 *
	 * @param string|false $file file name.
	 * @return string upload path
	 */
	public static function get_file_path( $file = false ) {
		$base = WP_CONTENT_DIR . '/uploads/leagues';

		if ( $file ) {
			return $base . '/' . basename( $file );
		} else {
			return $base;
		}
	}

	/**
	 * Add pages to database
	 *
	 * @param array $page_definitions page definition array.
	 */
	public static function add_racketmanager_page( $page_definitions ) {
		foreach ( $page_definitions as $slug => $page ) {

			// Check that the page doesn't exist already.
			if ( ! is_page( $slug ) ) {
				$page_template = $page['page_template'];
				// Add the page using the data from the array above.
				$page    = array(
					'post_content'   => $page['content'],
					'post_name'      => $slug,
					'post_title'     => $page['title'],
					'post_status'    => 'publish',
					'post_type'      => 'page',
					'ping_status'    => 'closed',
					'comment_status' => 'closed',
					'page_template'  => $page_template,
				);
				$page_id = wp_insert_post( $page );
				if ( $page_id ) {
					$page_name = sanitize_title_with_dashes( $page['post_title'] );
					$option    = 'racketmanager_page_' . $page_name . '_id';
					// Only update this option if `wp_insert_post()` was successful.
					update_option( $option, $page_id );
				}
			}
		}
	}
	/**
	 * Get event types
	 *
	 * @return array event types.
	 */
	public static function get_event_types() {
		$event_types       = array();
		$event_types['WS'] = __( 'Ladies Singles', 'racketmanager' );
		$event_types['MS'] = __( 'Mens Singles', 'racketmanager' );
		$event_types['WD'] = __( 'Ladies Doubles', 'racketmanager' );
		$event_types['MD'] = __( 'Mens Doubles', 'racketmanager' );
		$event_types['XD'] = __( 'Mixed Doubles', 'racketmanager' );
		$event_types['LD'] = __( 'The League', 'racketmanager' );
		return $event_types;
	}

	/**
	 * Get event type
	 *
	 * @param string $type event.
	 * @return string event description.
	 */
	public static function get_event_type( $type ) {
		$event_types = self::get_event_types();
		if ( empty( $event_types[ $type ] ) ) {
			return __( 'Unknown', 'racketmanager' );
		} else {
			return $event_types[ $type ];
		}
	}

	/**
	 * Get weekdays
	 *
	 * @return array
	 */
	public static function get_weekdays() {
		$weekdays              = array();
		$weekdays['Monday']    = __( 'Monday', 'racketmanager' );
		$weekdays['Tuesday']   = __( 'Tuesday', 'racketmanager' );
		$weekdays['Wednesday'] = __( 'Wednesday', 'racketmanager' );
		$weekdays['Thursday']  = __( 'Thursday', 'racketmanager' );
		$weekdays['Friday']    = __( 'Friday', 'racketmanager' );
		$weekdays['Saturday']  = __( 'Saturday', 'racketmanager' );
		$weekdays['Sunday']    = __( 'Sunday', 'racketmanager' );
		return $weekdays;
	}

	/**
	 * Get available league standing status
	 *
	 * @param string $status status value.
	 * @return array||string
	 */
	public static function get_standing_status( $status = null ) {
		$standing_status       = array();
		$standing_status['C']  = __( 'Champions', 'racketmanager' );
		$standing_status['P1'] = __( 'Promoted in first place', 'racketmanager' );
		$standing_status['P2'] = __( 'Promoted in second place', 'racketmanager' );
		$standing_status['P3'] = __( 'Promoted in third place', 'racketmanager' );
		$standing_status['P4'] = __( 'Promoted in fourth place', 'racketmanager' );
		$standing_status['W1'] = __( 'League winners but league locked', 'racketmanager' );
		$standing_status['W2'] = __( 'Second place but league locked', 'racketmanager' );
		$standing_status['W3'] = __( 'Third place but league locked', 'racketmanager' );
		$standing_status['RB'] = __( 'Relegated in bottom place', 'racketmanager' );
		$standing_status['RQ'] = __( 'Relegated by request', 'racketmanager' );
		$standing_status['RT'] = __( 'Relegated as team in division above', 'racketmanager' );
		$standing_status['BT'] = __( 'Finished bottom but not relegated', 'racketmanager' );
		$standing_status['NT'] = __( 'New team', 'racketmanager' );
		$standing_status['W']  = __( 'Withdrawn', 'racketmanager' );
		$standing_status['+']  = __( 'Move up', 'racketmanager' );
		$standing_status['-']  = __( 'Move down', 'racketmanager' );
		$standing_status['=']  = __( 'No movement', 'racketmanager' );
		if ( ! is_null( $status ) ) {
			if ( ! empty( $standing_status[ $status ] ) ) {
				return $standing_status[ $status ];
			} else {
				return null;
			}
		} else {
			return $standing_status;
		}
	}

	/**
	 * Get available competition types
	 *
	 * @return array
	 */
	public static function get_competition_types() {
		$competition_types               = array();
		$competition_types['cup']        = __( 'cup', 'racketmanager' );
		$competition_types['league']     = __( 'league', 'racketmanager' );
		$competition_types['tournament'] = __( 'tournament', 'racketmanager' );
		return $competition_types;
	}

	/**
	 * Get available league modes
	 *
	 * @return array
	 */
	public static function get_modes() {
		$modes                 = array();
		$modes['default']      = __( 'Default', 'racketmanager' );
		$modes['championship'] = __( 'Championship', 'racketmanager' );
		/**
		 * Fired when league modes are built
		 *
		 * @param array $modes
		 * @return array
		 * @category wp-filter
		 */
		$modes = apply_filters( 'racketmanager_modes', $modes );
		return $modes;
	}

	/**
	 * Get available entry types
	 *
	 * @return array
	 */
	public static function get_entry_types() {
		$entry_types           = array();
		$entry_types['team']   = __( 'Team', 'racketmanager' );
		$entry_types['player'] = __( 'Player', 'racketmanager' );
		return $entry_types;
	}
	/**
	 * Get array of supported scoring rules
	 *
	 * @return array
	 */
	public static function get_scoring_types() {
		$scoring_types        = array();
		$scoring_types['F4']  = __( 'Fast 4', 'racketmanager' );
		$scoring_types['FM']  = __( 'Fast 4 with match tie break', 'racketmanager' );
		$scoring_types['PR']  = __( 'Pro', 'racketmanager' );
		$scoring_types['TB']  = __( 'Tie break', 'racketmanager' );
		$scoring_types['TBM'] = __( 'Tie break with match tie break in final', 'racketmanager' );
		$scoring_types['TM']  = __( 'Tie break with match tie break', 'racketmanager' );
		$scoring_types['TP']  = __( 'Tie break with tie break playoff', 'racketmanager' );
		$scoring_types['MP']  = __( 'Tie break with match tie break playoff', 'racketmanager' );
		$scoring_types['MPL'] = __( 'Tie break with match tie break playoff in 2nd Leg', 'racketmanager' );
		return $scoring_types;
	}
	/**
	 * Get array of supported point rules
	 *
	 * @return array
	 */
	public static function get_point_rules() {
		$rules           = array();
		$rules['manual'] = __( 'Update Standings Manually', 'racketmanager' );
		$rules['one']    = __( 'One-Point-Rule', 'racketmanager' );
		$rules['two']    = __( 'Two-Point-Rule', 'racketmanager' );
		$rules['three']  = __( 'Three-Point-Rule', 'racketmanager' );
		$rules['score']  = __( 'Score', 'racketmanager' );
		$rules['user']   = __( 'User defined', 'racketmanager' );

		/**
		 * Fired when league point rules are built
		 *
		 * @param array $rules
		 * @return array
		 * @category wp-filter
		 */
		$rules = apply_filters( 'racketmanager_point_rules_list', $rules );
		asort( $rules );

		return $rules;
	}

	/**
	 * Get available point formats
	 *
	 * @return array
	 */
	public static function get_point_formats() {
		$point_formats                = array();
		$point_formats['%s:%s']       = '%s:%s';
		$point_formats['%s']          = '%s';
		$point_formats['%d:%d']       = '%d:%d';
		$point_formats['%d - %d']     = '%d - %d';
		$point_formats['%d']          = '%d';
		$point_formats['%.1f:%.1f']   = '%f:%f';
		$point_formats['%.1f - %.1f'] = '%f - %f';
		$point_formats['%.1f']        = '%f';
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
	 * Get list of players by initial function
	 *
	 * @param array $players list of players.
	 * @return array list of players by initial
	 */
	public static function get_players_list( $players ) {
		$player_list = array();
		$players_new = array();
		foreach ( $players as $player_name ) {
			$player_names = explode( ' ', $player_name );
			$i            = 0;
			$surname      = null;
			foreach ( $player_names as $name ) {
				if ( 0 === $i ) {
					$firstname = $name;
				} elseif ( 1 === $i ) {
					$surname = $name;
				} else {
					$surname .= ' ' . $name;
				}
				++$i;
			}
			$player_index  = $surname . ' ' . $firstname;
			$players_new[] = $player_index;
		}
		asort( $players_new );
		foreach ( $players_new as $player_name ) {
			$player_names = explode( ' ', $player_name );
			$surname      = null;
			$count        = count( $player_names );
			$i            = 1;
			foreach ( $player_names as $name ) {
				if ( $count === $i ) {
					$firstname = $name;
				} elseif ( 1 === $i ) {
					$surname = $name;
				} else {
					$surname .= ' ' . $name;
				}
				++$i;
			}
			$key = strtoupper( substr( $surname, 0, 1 ) );
			if ( false === array_key_exists( $key, $player_list ) ) {
				$player_list[ $key ] = array();
			}
			$player                = new \stdClass();
			$player->display_name  = $firstname . ' ' . $surname;
			$player->firstname     = $firstname;
			$player->surname       = $surname;
			$player->index         = $surname . ', ' . $firstname;
			$player_list[ $key ][] = $player;
		}
		return $player_list;
	}
	/**
	 * Gets club player requests from database
	 *
	 * @param array $query_args query arguments.
	 * @return array
	 */
	public static function get_player_requests( $query_args ) {
		global $wpdb;

		$defaults   = array(
			'count'   => false,
			'club'    => false,
			'status'  => false,
			'orderby' => array(
				'requested_date' => 'DESC',
				'completed_date' => 'DESC',
				'surname'        => 'DESC',
				'first_name'     => 'DESC',
			),
		);
		$query_args = array_merge( $defaults, (array) $query_args );
		$count      = $query_args['count'];
		$club       = $query_args['club'];
		$status     = $query_args['status'];
		$orderby    = $query_args['orderby'];

		$search_terms = array();
		$sql          = "SELECT `id`, `first_name`, `surname`, `player_id`, `affiliatedclub`, `requested_date`, `requested_user`, `completed_date`, `completed_user`, `gender`, `btm`, `email` FROM {$wpdb->racketmanager_club_player_requests} WHERE 1 = 1";

		if ( $club && 'all' !== $club ) {
			$search_terms[] = $wpdb->prepare( '`affiliatedclub` = %s', $club );
		}
		if ( $status && 'outstanding' === $status ) {
			$search_terms[] = '`completed_date` IS NULL';
		}
		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search = implode( ' AND ', $search_terms );
		}

		if ( $count ) {
			$sql = "SELECT COUNT(ID) FROM {$wpdb->racketmanager_club_player_requests} WHERE 1 = 1";
			if ( '' !== $search ) {
				$sql .= " AND $search";
			}
			return $wpdb->get_var(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
		}

		$orderby_string = '';
		$i              = 0;
		foreach ( $orderby as $order => $direction ) {
			if ( ! in_array( $direction, array( 'DESC', 'ASC', 'desc', 'asc' ), true ) ) {
				$direction = 'ASC';
			}
			$orderby_string .= '`' . $order . '` ' . $direction;
			if ( $i < ( count( $orderby ) - 1 ) ) {
				$orderby_string .= ',';
			}
			++$i;
		}
		$order = $orderby_string;
		if ( '' !== $search ) {
			$sql .= " AND $search";
		}
		if ( '' !== $order ) {
			$sql .= " ORDER BY $order";
		}

		$player_requests = wp_cache_get( md5( $sql ), 'playerRequests' );
		if ( ! $player_requests ) {
			$player_requests = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $player_requests, 'playerRequests' );
		}

		$class = '';
		foreach ( $player_requests as $i => $player_request ) {
			$class                 = ( 'alternate' === $class ) ? '' : 'alternate';
			$player_request->class = $class;
			if ( $player_request->player_id ) {
				$player                     = get_player( $player_request->player_id );
				$player_request->first_name = $player->firstname;
				$player_request->surname    = $player->surname;
				$player_request->gender     = $player->gender;
				$player_request->btm        = $player->btm;
				$player_request->email      = $player->email;
			}
			$player_request->club_name         = get_club( $player_request->affiliatedclub )->name;
			$player_request->requested_user_id = $player_request->requested_user;
			$player_request->requested_user    = get_userdata( $player_request->requested_user )->display_name;
			$player_request->completed_user_id = $player_request->completed_user;
			if ( ! empty( $player_request->completed_user ) ) {
				$player_request->completed_user = get_userdata( $player_request->completed_user )->display_name;
			} else {
				$player_request->completed_user = '';
			}

			$player_requests[ $i ] = $player_request;
		}

		return $player_requests;
	}
	/**
	 * Get set type function
	 *
	 * @param string $scoring scoring format.
	 * @param string $round round.
	 * @param int    $num_sets number of sets.
	 * @param int    $set set number.
	 * @param int    $rubber_number rubber number.
	 * @param int    $num_rubbers number of rubbers.
	 * @param int    $leg leg number.
	 * @return string set type.
	 */
	public static function get_set_type( $scoring, $round = null, $num_sets = 99, $set = 1, $rubber_number = null, $num_rubbers = null, $leg = null ) {
		if ( 'TB' === $scoring ) {
			$set_type = 'TB';
		} elseif ( 'TBM' === $scoring ) {
			if ( 'final' === $round ) {
				if ( intval( $num_sets ) === $set ) {
					$set_type = 'MTB';
				} else {
					$set_type = 'TB';
				}
			} else {
				$set_type = 'TB';
			}
		} elseif ( 'TM' === $scoring ) {
			if ( intval( $num_sets ) === $set ) {
				$set_type = 'MTB';
			} else {
				$set_type = 'TB';
			}
		} elseif ( 'F4' === $scoring ) {
			$set_type = 'fast4';
		} elseif ( 'FM' === $scoring ) {
			if ( intval( $num_sets ) === $set ) {
				$set_type = 'MTB';
			} else {
				$set_type = 'fast4';
			}
		} elseif ( 'PR' === $scoring ) {
			$set_type = 'pro';
		} elseif ( 'TP' === $scoring ) {
			$set_type = 'TB';
			if ( $rubber_number && $rubber_number === $num_rubbers && 1 !== $set ) {
				$set_type = 'null';
			}
		} elseif ( 'MP' === $scoring ) {
			if ( intval( $num_sets ) === $set ) {
				$set_type = 'MTB';
			} else {
				$set_type = 'TB';
			}
			if ( $rubber_number && intval( $num_rubbers ) === $rubber_number ) {
				$set_type = 'MTB';
				if ( 1 !== $set ) {
					$set_type = 'null';
				}
			}
		} elseif ( 'MPL' === $scoring ) {
			if ( intval( $num_sets ) === $set ) {
				$set_type = 'MTB';
			} else {
				$set_type = 'TB';
			}
			if ( ( '2' === $leg || 'final' === $round ) && $rubber_number && intval( $num_rubbers ) === $rubber_number ) {
				$set_type = 'MTB';
				if ( 1 !== $set ) {
					$set_type = 'null';
				}
			}
		}
		return $set_type;
	}
	/**
	 * Get set info function
	 *
	 * @param string $set_type set type.
	 * @return object set information.
	 */
	public static function get_set_info( $set_type ) {
		$tiebreak_allowed  = false;
		$tiebreak_required = false;
		if ( 'TB' === $set_type ) {
			$max_win          = 7;
			$min_win          = 6;
			$max_loss         = $max_win - 2;
			$min_loss         = $min_win - 2;
			$tiebreak_allowed = true;
		} elseif ( 'MTB' === $set_type ) {
			$max_win  = 99;
			$min_win  = 10;
			$max_loss = $max_win - 2;
			$min_loss = $min_win - 2;
		} elseif ( 'fast4' === $set_type ) {
			$max_win          = 4;
			$min_win          = 4;
			$max_loss         = $max_win - 1;
			$min_loss         = $min_win - 1;
			$tiebreak_allowed = true;
		} elseif ( 'standard' === $set_type ) {
			$max_win  = 99;
			$min_win  = 6;
			$max_loss = $max_win - 2;
			$min_loss = $min_win - 2;
		} elseif ( 'pro' === $set_type ) {
			$max_win  = 9;
			$min_win  = 8;
			$max_loss = $max_win - 2;
			$min_loss = $min_win - 2;
		} elseif ( 'null' === $set_type ) {
			$max_win  = 0;
			$min_win  = 0;
			$max_loss = 0;
			$min_loss = 0;
		}
		$set_info                    = new \stdClass();
		$set_info->max_win           = $max_win;
		$set_info->min_win           = $min_win;
		$set_info->max_loss          = $max_loss;
		$set_info->min_loss          = $min_loss;
		$set_info->tiebreak_allowed  = $tiebreak_allowed;
		$set_info->tiebreak_required = $tiebreak_required;
		return $set_info;
	}
	/**
	 * Get match satus value function
	 *
	 * @param int $status status.
	 * @return string status text
	 */
	public function get_match_status( $status ) {
		switch ( $status ) {
			case 0:
				$status_value = __( 'Complete', 'racketmanager' );
				break;
			case 1:
				$status_value = __( 'Walkover', 'racketmanager' );
				break;
			case 2:
				$status_value = __( 'Retired', 'racketmanager' );
				break;
			case 3:
				$status_value = __( 'Shared', 'racketmanager' );
				break;
			default:
				$status_value = __( 'Unknown', 'racketmanager' );
		}
		return $status_value;
	}
	/**
	 * Get match days function
	 *
	 * @return array of match days
	 */
	public static function get_match_days() {
		$match_days      = array();
		$match_days['0'] = __( 'Monday', 'racketmanager' );
		$match_days['1'] = __( 'Tuesday', 'racketmanager' );
		$match_days['2'] = __( 'Wednesday', 'racketmanager' );
		$match_days['3'] = __( 'Thursday', 'racketmanager' );
		$match_days['4'] = __( 'Friday', 'racketmanager' );
		$match_days['5'] = __( 'Saturday', 'racketmanager' );
		$match_days['6'] = __( 'Sunday', 'racketmanager' );
		return $match_days;
	}
	/**
	 * Get match day number from day name function
	 *
	 * @param string $match_day match day name.
	 * @return int match day number
	 */
	public static function get_match_day_number( $match_day ) {
		$match_days = self::get_match_days();
		$day        = array_search( $match_day, $match_days, true );
		if ( false === $day ) {
			$day = 0;
		}
		return intval( $day );
	}
	/**
	 * Get match day name from day number function
	 *
	 * @param string $match_day_num match day number.
	 * @return string match day name
	 */
	public static function get_match_day( $match_day_num ) {
		$match_days = self::get_match_days();
		return empty( $match_days[ intval( $match_day_num ) ] ) ? __( 'Unknown', 'racketmanager' ) : $match_days[ $match_day_num ];
	}
	/**
	 * Get users for favourite
	 *
	 * @param string $type type of favourite.
	 * @param string $key key of favourite.
	 * @return array list of users
	 */
	public static function get_users_for_favourite( $type, $key ) {
		return get_users(
			array(
				'meta_key'   => 'favourite-' . $type, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'     => 'ids',
			)
		);
	}
}
