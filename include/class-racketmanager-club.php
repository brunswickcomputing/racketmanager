<?php
/**
 * Racketmanager_Club API: Club class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Club
 */

namespace Racketmanager;

/**
 * Class to implement the Club object
 */
final class Racketmanager_Club {
	/**
	 * Id
	 *
	 * @var int
	 */
	public $id;
	/**
	 * Match secretary contact name
	 *
	 * @var string
	 */
	public $match_secretary_name;
	/**
	 * Match secretary contact email
	 *
	 * @var string
	 */
	public $match_secretary_email;
	/**
	 * Match secretary contact number
	 *
	 * @var string
	 */
	public $match_secretary_contact_no;
	/**
	 * Match secretary ID
	 *
	 * @var int
	 */
	public $matchsecretary;
	/**
	 * Description
	 *
	 * @var string
	 */
	public $desc;
	/**
	 * Name
	 *
	 * @var string
	 */
	public $name;
	/**
	 * Short code
	 *
	 * @var string
	 */
	public $shortcode;
	/**
	 * Type
	 *
	 * @var string
	 */
	public $type;
	/**
	 * Website
	 *
	 * @var string
	 */
	public $website;
	/**
	 * Contact nuy=mber
	 *
	 * @var string
	 */
	public $contactno;
	/**
	 * Founded
	 *
	 * @var int
	 */
	public $founded;
	/**
	 * Facilities
	 *
	 * @var string
	 */
	public $facilities;
	/**
	 * Address
	 *
	 * @var string
	 */
	public $address;
	/**
	 * Longitude
	 *
	 * @var string
	 */
	public $longitude;
	/**
	 * Latitude
	 *
	 * @var string
	 */
	public $latitude;
	/**
	 * Number of players.
	 *
	 * @var int
	 */
	public $num_players;
	/**
	 * Retrieve club instance
	 *
	 * @param int    $club_id club id or name.
	 * @param string $search_term search.
	 */
	public static function get_instance( $club_id, $search_term = 'id' ) {
		global $wpdb;

		switch ( $search_term ) {
			case 'name':
				$search = $wpdb->prepare(
					'`name` = %s',
					$club_id
				);
				break;
			case 'shortcode':
				$search = $wpdb->prepare(
					'`shortcode` = %s',
					$club_id
				);
				break;
			case 'id':
			default:
				$club_id = (int) $club_id;
				$search  = $wpdb->prepare(
					'`id` = %d',
					$club_id
				);
				break;
		}

		if ( ! $club_id ) {
			return false;
		}

		$club = wp_cache_get( $club_id, 'clubs' );

		if ( ! $club ) {
			$club = $wpdb->get_row(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				"SELECT `id`, `name`, `website`, `type`, `address`, `latitude`, `longitude`, `contactno`, `founded`, `facilities`, `shortcode`, `matchsecretary` FROM {$wpdb->racketmanager_clubs} WHERE " . $search . ' LIMIT 1'
			); // db call ok.

			if ( ! $club ) {
				return false;
			}

			$club = new Racketmanager_Club( $club );

			wp_cache_set( $club_id, $club, 'clubs' );
		}

		return $club;
	}

	/**
	 * Constructor
	 *
	 * @param object $club Club object.
	 */
	public function __construct( $club = null ) {
		if ( ! is_null( $club ) ) {
			foreach ( get_object_vars( $club ) as $key => $value ) {
				$this->$key = $value;
			}

			if ( ! isset( $this->id ) ) {
				$this->add();
			}
			$this->match_secretary_name       = '';
			$this->match_secretary_email      = '';
			$this->match_secretary_contact_no = '';
			if ( isset( $this->matchsecretary ) && '0' !== $this->matchsecretary ) {
				$match_secretary_dtls = get_userdata( $this->matchsecretary );
				if ( $match_secretary_dtls ) {
					$this->match_secretary_name       = $match_secretary_dtls->display_name;
					$this->match_secretary_email      = $match_secretary_dtls->user_email;
					$this->match_secretary_contact_no = get_user_meta( $this->matchsecretary, 'contactno', true );
				}
			}
			$this->desc = '';
		}
	}

	/**
	 * Create new club
	 */
	private function add() {
		global $wpdb;

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"INSERT INTO {$wpdb->racketmanager_clubs} (`name`, `type`, `shortcode`, `contactno`, `website`, `founded`, `facilities`, `address`, `latitude`, `longitude`) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s,%s )",
				$this->name,
				$this->type,
				$this->shortcode,
				$this->contactno,
				$this->website,
				$this->founded,
				$this->facilities,
				$this->address,
				$this->latitude,
				$this->longitude
			)
		);
		$this->id = $wpdb->insert_id;
	}

	/**
	 * Update club
	 *
	 * @param object $club updated club information.
	 * @param string $prev_shortcode previous short code.
	 */
	public function update( $club, $prev_shortcode = false ) {
		global $wpdb;

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$wpdb->racketmanager_clubs} SET `name` = %s, `type` = %s, `shortcode` = %s,`matchsecretary` = %d, `contactno` = %s, `website` = %s, `founded`= %s, `facilities` = %s, `address` = %s, `latitude` = %s, `longitude` = %s WHERE `id` = %d",
				$club->name,
				$club->type,
				$club->shortcode,
				$club->matchsecretary,
				$club->contactno,
				$club->website,
				$club->founded,
				$club->facilities,
				$club->address,
				$club->latitude,
				$club->longitude,
				$this->id
			)
		);

		if ( $prev_shortcode && $prev_shortcode !== $this->shortcode ) {
			$teams = $this->get_teams();
			foreach ( $teams as $team ) {
				$team      = get_team( $team->id );
				$team_ref  = substr( $team->title, strlen( $prev_shortcode ) + 1, strlen( $team->title ) );
				$new_title = $club->shortcode . ' ' . $team_ref;
				$team->update_title( $new_title );
			}
		}
		if ( '' !== $club->matchsecretary ) {
			$player = get_player( $club->matchsecretary );
			$player->update_contact( $club->match_secretary_contact_no, $club->match_secretary_email );
		}
	}

	/**
	 * Delete Club
	 */
	public function delete() {
		global $wpdb;

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_club_player_requests} WHERE `affiliatedclub` = %d",
				$this->id
			)
		);
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_club_players} WHERE `affiliatedclub` = %d",
				$this->id
			)
		);
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$wpdb->racketmanager_clubs} WHERE `id` = %d",
				$this->id
			)
		);
	}

	/**
	 * Create team in database
	 *
	 * @param string $type type of team to create.
	 */
	public function add_team( $type ) {
		global $racketmanager;

		switch ( substr( $type, 0, 1 ) ) {
			case 'B':
				$type_name = 'Boys';
				break;
			case 'G':
				$type_name = 'Girls';
				break;
			case 'W':
				$type_name = 'Ladies';
				break;
			case 'M':
				$type_name = 'Mens';
				break;
			case 'X':
				$type_name = 'Mixed';
				break;
			default:
				$type_name = 'error';
				break;
		}

		if ( 'error' === $type_name ) {
			$racketmanager->set_message( __( 'Type not selected', 'racketmanager' ), 'error' );
			return false;
		}
		$team_count = $this->has_teams( $type );
		++$team_count;
		$team                 = new \stdClass();
		$team->title          = $this->shortcode . ' ' . $type_name . ' ' . $team_count;
		$team->stadium        = $this->name;
		$team->affiliatedclub = $this->id;
		$team->type           = $type;
		return new Racketmanager_Team( $team );
	}

	/**
	 * Does the club have teams?
	 *
	 * @param string $type the type of team to count. If this is specified, only non-player teams will be counted.
	 * @return int count number of teams
	 */
	public function has_teams( $type = false ) {
		global $wpdb;

		$args   = array();
		$args[] = intval( $this->id );
		$sql    = "SELECT count(*) FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = '%d'";
		if ( $type ) {
			$sql   .= " AND `type` = '%s' AND (`team_type` IS NULL OR `team_type` != 'P')";
			$args[] = $type;
		}
		return $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql,
				$args
			)
		);
	}

	/**
	 * Get teams for club
	 *
	 * @param string $players player.
	 * @param string $type player type.
	 * @return object
	 */
	public function get_teams( $players = false, $type = false ) {
		global $wpdb;

		$args   = array();
		$sql    = "SELECT `id` FROM {$wpdb->racketmanager_teams} WHERE `affiliatedclub` = '%d'";
		$args[] = intval( $this->id );
		if ( ! $players ) {
			$sql .= " AND (`team_type` is null OR `team_type` != 'P')";
		} else {
			$sql .= " AND `team_type` = 'P'";
		}
		if ( $type ) {
			if ( 'OS' === $type ) {
				$sql   .= " AND `type` like '%%%s%%'";
				$args[] = 'S';
			} else {
				$sql   .= " AND `type` = '%s'";
				$args[] = $type;
			}
		}

		$sql .= ' ORDER BY `title`';
		$sql  = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql,
			$args
		);

		$teams = wp_cache_get( md5( $sql ), 'teams' );
		if ( ! $teams ) {
			$teams = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $teams, 'teams' );
		}

		$class = '';
		foreach ( $teams as $i => $team ) {
			$class       = ( 'alternate' === $class ) ? '' : 'alternate';
			$team        = get_team( $team->id );
			$teams[ $i ] = $team;
		}

		return $teams;
	}

	/**
	 * Get single player request
	 *
	 * @param int $player_request_id player request id.
	 * @return object
	 */
	private function get_player_request( $player_request_id ) {
		global $wpdb;

		$player_request = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT `first_name`, `surname`, `gender`, `btm`, `email`, `player_id`, `requested_date`, `requested_user`, `completed_date`, `completed_user` FROM {$wpdb->racketmanager_club_player_requests} WHERE `id` = %d",
				intval( $player_request_id )
			)
		);

		if ( ! $player_request ) {
			return false;
		}

		return $player_request;
	}

	/**
	 * Approve Club Player Request
	 *
	 * @param int $player_request_id player request id.
	 * @return boolean
	 */
	public function approve_player_request( $player_request_id ) {
		global $wpdb, $racketmanager;
		$player_request = $this->get_player_request( $player_request_id );
		if ( empty( $player_request->completed_date ) ) {
			$this->add_club_player( $player_request->player_id, false );
			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"UPDATE {$wpdb->racketmanager_club_player_requests} SET `completed_date` = now(), `completed_user` = %d WHERE `id` = %d ",
					get_current_user_id(),
					$player_request_id
				)
			);
			$racketmanager->set_message( __( 'Player added to club', 'racketmanager' ) );
		}

		return true;
	}

	/**
	 * Register player for Club
	 *
	 * @param object $new_player player details.
	 */
	public function register_player( $new_player ) {
		global $racketmanager;
		$valid      = true;
		$old_player = false;
		$player     = get_player( $new_player->user_login, 'login' ); // get player by login.
		if ( ! $player ) {
			$player = get_player( $new_player->email, 'email' );
			if ( $player ) {
				$old_player = true;
				$valid      = false;
				$racketmanager->set_message( __( 'Email address already used', 'racketmanager' ), true );
			} else {
				$player = get_player( $new_player->btm, 'btm' );
				if ( $player ) {
					$old_player = true;
					$valid      = false;
					$racketmanager->set_message( __( 'LTA Tennis Number already used', 'racketmanager' ), true );
				} else {
					$player = new Racketmanager_Player( $new_player );
					if ( ! $player ) {
						$valid = false;
						$racketmanager->set_message( __( 'Error creating player', 'racketmanager' ), true );
					}
				}
			}
		} else {
			$old_player = true;
		}
		if ( $valid ) {
			$player_change = false;
			if ( $old_player ) {
				$updated_player = clone $player;
				if ( empty( $player->email ) ) {
					if ( ! empty( $new_player->email ) ) {
						$player_change         = true;
						$updated_player->email = $new_player->email;
					}
				} elseif ( ! empty( $new_player->email ) && $player->email !== $new_player->email ) {
					$valid = false;
					$racketmanager->set_message( __( 'Email address is different from existing', 'racketmanager' ), true );
				}
				if ( empty( $player->btm ) ) {
					if ( ! empty( $new_player->btm ) ) {
						$player_change       = true;
						$updated_player->btm = $new_player->btm;
					}
				} elseif ( ! empty( $new_player->btm ) && intval( $player->btm ) !== $new_player->btm ) {
					$valid = false;
					$racketmanager->set_message( __( 'LTA Tennis Number is different from existing', 'racketmanager' ), true );
				}
				if ( empty( $player->gender ) ) {
					if ( ! empty( $new_player->gender ) ) {
						$player_change          = true;
						$updated_player->gender = $new_player->gender;
					}
				} elseif ( ! empty( $new_player->gender ) && $player->gender !== $new_player->gender ) {
					$valid = false;
					$racketmanager->set_message( __( 'Gender is different from existing', 'racketmanager' ), true );
				}
				if ( empty( $player->year_of_birth ) ) {
					if ( ! empty( $new_player->year_of_birth ) ) {
						$player_change                 = true;
						$updated_player->year_of_birth = $new_player->year_of_birth;
					}
				} elseif ( ! empty( $new_player->year_of_birth ) && $player->year_of_birth !== $new_player->year_of_birth ) {
					$valid = false;
					$racketmanager->set_message( __( 'Year of birth is different from existing', 'racketmanager' ), true );
				}
			}
		}
		if ( $valid ) {
			if ( $player_change ) {
				$player->update( $updated_player );
			}
			$player_active = $this->player_active( $player->id );
			if ( ! $player_active ) {
				$player_pending = $this->is_player_pending( $player->id );
				if ( $player_pending ) {
					$racketmanager->set_message( __( 'Player registration already pending', 'racketmanager' ), true );
				} else {
					$player_request_id = $this->add_player_request( $player->id );
					$options           = $racketmanager->get_options( 'rosters' );
					if ( 'auto' === $options['rosterConfirmation'] || current_user_can( 'edit_teams' ) ) {
						$this->approve_player_request( $player_request_id );
						$action = 'add';
						$msg    = __( 'Player added to club', 'racketmanager' );
					} else {
						$action = 'request';
						$msg    = __( 'Player registration pending', 'racketmanager' );
					}
					if ( ! empty( $options['rosterConfirmationEmail'] ) ) {
						$headers = array();
						$user    = wp_get_current_user();
						if ( $this->matchsecretary !== $user->ID ) {
							$headers[] = 'cc: ' . $this->match_secretary_name . ' <' . $this->match_secretary_email . '>';
						}
						$email_to                  = $user->display_name . ' <' . $user->user_email . '>';
						$message_args              = array();
						$message_args['requestor'] = $user->display_name;
						$message_args['action']    = $action;
						$message_args['club']      = $this->shortcode;
						$message_args['player']    = $player->fullname;
						$message_args['btm']       = empty( $player->btm ) ? null : $player->btm;
						$headers[]                 = 'from: ' . $racketmanager->site_name . ' <' . $options['rosterConfirmationEmail'] . '>';
						$subject                   = $racketmanager->site_name . ' - ' . $msg . ' - ' . $this->shortcode;
						$message                   = racketmanager_club_players_notification( $message_args );
						wp_mail( $email_to, $subject, $message, $headers );
					}
					$racketmanager->set_message( $msg );
				}
			} else {
				$valid = false;
				$racketmanager->set_message( __( 'Player already registered', 'racketmanager' ), true );
			}
		}
	}

	/**
	 * Check for player registered active
	 *
	 * @param int $player playerid.
	 * @return boolean is player registered active for club
	 */
	public function player_active( $player ) {
		global $wpdb;
		return $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT count(*) FROM {$wpdb->racketmanager_club_players} WHERE `affiliatedclub` = %d AND `player_id` = %d AND `removed_date` IS NULL",
				intval( $this->id ),
				intval( $player )
			)
		);
	}

	/**
	 * Check for player pending registration
	 *
	 * @param int $player player id.
	 * @return boolean is player pending registration for club
	 */
	private function is_player_pending( $player ) {
		global $wpdb;
		return $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT count(*) FROM {$wpdb->racketmanager_club_player_requests} WHERE `affiliatedclub` = %d AND `player_id` = %d AND `completed_date` IS NULL",
				intval( $this->id ),
				intval( $player )
			)
		);
	}

	/**
	 * Add new club player
	 *
	 * @param int $player_id player id.
	 * @return int | false
	 */
	private function add_club_player( $player_id ) {
		global $wpdb, $racketmanager;
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"INSERT INTO {$wpdb->racketmanager_club_players} (`affiliatedclub`, `player_id`, `created_date`, `created_user` ) VALUES (%d, %d, now(), %d)",
				$this->id,
				$player_id,
				get_current_user_id()
			)
		);
		$racketmanager->set_message( __( 'Club Player added', 'racketmanager' ) );
		return $wpdb->insert_id;
	}

	/**
	 * Add new player request
	 *
	 * @param int $player id.
	 * @return int player request id.
	 */
	private function add_player_request( $player ) {
		global $wpdb, $racketmanager;

		$userid = get_current_user_id();
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"INSERT INTO {$wpdb->racketmanager_club_player_requests} (`affiliatedClub`, `first_name`, `surname`, `gender`, `player_id`, `requested_date`, `requested_user`) values (%d, %s, %s, %s, %d, now(), %d)",
				$this->id,
				'',
				'',
				'',
				$player,
				$userid
			)
		);

		$racketmanager->set_message( __( 'Player request added', 'racketmanager' ) );

		return $wpdb->insert_id;
	}

	/**
	 * Gets club players from database
	 *
	 * @param array $args query arguments.
	 * @return object
	 */
	public function get_players( $args ) {
		global $racketmanager, $wpdb;
		$options    = $racketmanager->get_options( 'rosters' );
		$defaults   = array(
			'count'      => false,
			'team'       => false,
			'player'     => false,
			'gender'     => false,
			'active'     => true,
			'cache'      => true,
			'type'       => false,
			'age_offset' => false,
			'age_limit'  => false,
			'orderby'    => array( 'display_name' => 'ASC' ),
		);
		$args       = array_merge( $defaults, (array) $args );
		$count      = $args['count'];
		$team       = $args['team'];
		$player     = $args['player'];
		$gender     = $args['gender'];
		$active     = $args['active'];
		$cache      = $args['cache'];
		$type       = $args['type'];
		$orderby    = (array) $args['orderby'];
		$age_limit  = $args['age_limit'];
		$age_offset = $args['age_offset'];

		$search_terms = array();
		if ( $team ) {
			$search_terms[] = $wpdb->prepare(
				"`affiliatedclub` in (select `affiliatedclub` from {$wpdb->racketmanager_teams} where `id` = %d)",
				intval( $team )
			);
		}

		if ( $player ) {
			$search_terms[] = $wpdb->prepare(
				'`player_id` = %d',
				intval( $player )
			);
		}

		if ( $gender ) {
			$gender         = htmlspecialchars( wp_strip_all_tags( $gender ) );
			$search_terms[] = $wpdb->prepare(
				'%s = %s',
				$gender,
				$gender
			);
		}

		if ( $type ) {
			$search_terms[] = '`system_record` IS NULL';
		}

		if ( $active ) {
			$search_terms[] = '`removed_date` IS NULL';
		}

		$search = '';
		if ( ! empty( $search_terms ) ) {
			$search = implode( ' AND ', $search_terms );
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

		if ( $count ) {
			$sql = "SELECT COUNT(ID) FROM {$wpdb->racketmanager_club_players} WHERE `affiliatedclub` = " . $this->id;
			if ( '' !== $search ) {
				$sql .= " AND $search";
			}
			$cachekey = md5( $sql );
			if ( isset( $this->num_players[ $cachekey ] ) && $cache && $count ) {
				return intval( $this->num_players[ $cachekey ] );
			} else {
				$this->num_players[ $cachekey ] = $wpdb->get_var(
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$sql
				); // db call ok.
				return $this->num_players[ $cachekey ];
			}
		}

		$sql = $wpdb->prepare(
			"SELECT A.`id` as `roster_id`, A.`player_id`, `display_name` as fullname, `affiliatedclub`, A.`removed_date`, A.`removed_user`, A.`created_date`, A.`created_user` FROM {$wpdb->racketmanager_club_players} A INNER JOIN {$wpdb->users} B ON A.`player_id` = B.`ID` WHERE `affiliatedclub` = %d",
			$this->id
		);
		if ( '' !== $search ) {
			$sql .= " AND $search";
		}
		if ( '' !== $order ) {
			$sql .= " ORDER BY $order";
		}

		$players = wp_cache_get( md5( $sql ), 'clubplayers' );
		if ( ! $players ) {
			$players = $wpdb->get_results(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			$i     = 0;
			$class = '';
			foreach ( $players as $player ) {
				$class                    = ( 'alternate' === $class ) ? '' : 'alternate';
				$players[ $i ]->class     = $class;
				$players[ $i ]->roster_id = $player->roster_id;
				$players[ $i ]->player_id = $player->player_id;
				$players[ $i ]->gender    = get_user_meta( $player->player_id, 'gender', true );
				if ( $gender && $gender !== $players[ $i ]->gender ) {
					unset( $players[ $i ] );
				} else {
					$players[ $i ]->removed_date = $player->removed_date;
					$players[ $i ]->removed_user = $player->removed_user;
					if ( $player->removed_user ) {
						$players[ $i ]->removed_user_name = get_userdata( $player->removed_user )->display_name;
					} else {
						$players[ $i ]->removed_user_nName = '';
					}
					$players[ $i ]->created_date = $player->created_date;
					$players[ $i ]->created_user = $player->created_user;
					if ( $player->created_user ) {
						$players[ $i ]->created_user_name = get_userdata( $player->created_user )->display_name;
					} else {
						$players[ $i ]->created_user_name = '';
					}
					$player                          = get_player( $player->player_id );
					$players[ $i ]->fullname         = $player->display_name;
					$players[ $i ]->type             = $player->type;
					$players[ $i ]->btm              = $player->btm;
					$players[ $i ]->email            = $player->user_email;
					$players[ $i ]->locked           = $player->locked;
					$players[ $i ]->locked_date      = $player->locked_date;
					$players[ $i ]->locked_user      = $player->locked_user;
					$players[ $i ]->locked_user_name = $player->locked_user_name;
					$players[ $i ]->year_of_birth    = $player->year_of_birth;
					if ( $player->year_of_birth ) {
						$player->age = gmdate( 'Y' ) - intval( $player->year_of_birth );
					} else {
						$player->age = null;
					}
					$players[ $i ]->age = $player->age;
					if ( isset( $options['ageLimitCheck'] ) && 'true' === $options['ageLimitCheck'] && $age_limit && 'open' !== $age_limit ) {
						if ( ! empty( $player->age ) ) {
							if ( $age_limit >= 30 ) {
								$age_limit_check = $age_limit;
								if ( ! empty( $age_offset ) && 'F' === $player->gender ) {
									$age_limit_check -= $age_offset;
								}
								if ( $player->age < $age_limit_check ) {
									unset( $players[ $i ] );
								}
							} elseif ( $player->age > $age_limit ) {
								unset( $players[ $i ] );
							}
						} else {
							unset( $players[ $i ] );
						}
					}
				}

				++$i;
			}
			wp_cache_set( md5( $sql ), $players, 'clubplayers' );
		}

		return $players;
	}

	/**
	 * Gets player for club from database
	 *
	 * @param array $player_id player id.
	 * @return object
	 */
	public function get_player( $player_id ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT A.`id` as `roster_id`, B.`ID` as `player_id`, `display_name` as fullname, `affiliatedclub`, A.`removed_date`, A.`removed_user`, A.`created_date`, A.`created_user` FROM {$wpdb->racketmanager_club_players} A INNER JOIN {$wpdb->users} B ON A.`player_id` = B.`ID` WHERE `affiliatedclub` = %d AND `player_id` = %d",
			$this->id,
			intval( $player_id )
		);

		$player = wp_cache_get( md5( $sql ), 'players' );
		if ( ! $player ) {
			$player = $wpdb->get_row(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$sql
			); // db call ok.
			wp_cache_set( md5( $sql ), $player, 'players' );
		}

		if ( $player ) {
			$player->gender = get_user_meta( $player->player_id, 'gender', true );
			$player->type   = get_user_meta( $player->player_id, 'racketmanager_type', true );
			if ( $player->removed_user ) {
				$player->removed_user_name = get_userdata( $player->removed_user )->display_name;
			} else {
				$player->removed_user_name = '';
			}
			$player->btm = get_user_meta( $player->player_id, 'btm', true );
			if ( $player->created_user ) {
				$player->created_user_name = get_userdata( $player->created_user )->display_name;
			} else {
				$player->created_user_name = '';
			}
			$player->locked      = get_user_meta( $player->player_id, 'locked', true );
			$player->locked_date = get_user_meta( $player->player_id, 'locked_date', true );
			$player->locked_user = get_user_meta( $player->player_id, 'locked_user', true );
			if ( $player->locked_user ) {
				$player->locked_user_name = get_userdata( $player->locked_user )->display_name;
			} else {
				$player->locked_user_name = '';
			}
			$player->year_of_birth = get_user_meta( $player->player_id, 'year_of_birth', true );
		}

		return $player;
	}

	/**
	 * Check if player is captain
	 *
	 * @param int $player player id.
	 * @return boolean
	 */
	public function is_player_captain( $player ) {
		global $wpdb;
		return $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT count(*) FROM {$wpdb->racketmanager_team_events} te, {$wpdb->racketmanager_teams} t, {$wpdb->racketmanager_clubs} c WHERE c.`id` = %d AND c.`id` = t.`affiliatedclub` AND (t.`team_type` IS NULL OR t.`team_type` != 'P') AND t.`id` = te.`team_id` AND te.`captain` = %d",
				intval( $this->id ),
				intval( $player )
			)
		);
	}

	/**
	 * Cup entry function
	 *
	 * @param object $club_entry club cup entry object.
	 */
	public function cup_entry( $club_entry ) {
		global $racketmanager;
		$cup_entries = array();
		foreach ( $club_entry->events as $event_entry ) {
			$cup_entry = array();
			$event     = get_event( $event_entry->id );
			if ( isset( $event->primary_league ) ) {
				$league = get_league( $event->primary_league );
			} else {
				$league = get_league( array_key_first( $event->league_index ) );
			}
			$team_id   = $event_entry->team_id;
			$team      = get_team( $team_id );
			$team_info = $event->get_team_info( $team_id );
			if ( ! $team_info ) {
				$team->add_event( $event->id, $event_entry->captain_id, $event_entry->telephone, $event_entry->email, $event_entry->match_day, $event_entry->match_time );
			} else {
				$team->update_event( $event->id, $event_entry->captain_id, $event_entry->telephone, $event_entry->email, $event_entry->match_day, $event_entry->match_time );
			}
			$league->add_team( $team_id, $club_entry->season );
			$cup_entry['event']        = $event->name;
			$cup_entry['teamName']     = $team->title;
			$cup_entry['captain']      = $event_entry->captain;
			$cup_entry['contactno']    = $event_entry->telephone;
			$cup_entry['contactemail'] = $event_entry->email;
			$cup_entry['matchday']     = $event_entry->match_day;
			$cup_entry['matchtime']    = $event_entry->match_time;
			$cup_entries[]             = $cup_entry;
		}
		$email_to        = $this->match_secretary_name . ' <' . $this->match_secretary_email . '>';
		$email_from      = $racketmanager->get_confirmation_email( 'cup' );
		$email_subject   = $racketmanager->site_name . ' - ' . ucfirst( $club_entry->competition->name ) . ' ' . $club_entry->season . ' League Entry - ' . $this->shortcode;
		$headers         = array();
		$secretary_email = __( 'Cup Secretary', 'racketmanager' ) . ' <' . $email_from . '>';
		$headers[]       = 'From: ' . $secretary_email;
		$headers[]       = 'Cc: ' . $secretary_email;

		$template                          = 'cup-entry';
		$template_args['cup_entries']      = $cup_entries;
		$template_args['organisation']     = $racketmanager->site_name;
		$template_args['season']           = $club_entry->season;
		$template_args['competition_name'] = $club_entry->competition->name;
		$template_args['club']             = $this->name;
		$template_args['contact_email']    = $email_from;
		$template_args['comments']         = $club_entry->comments;
		$racketmanager->email_entry_form( $template, $template_args, $email_to, $email_subject, $headers );
	}

	/**
	 * League entry function
	 *
	 * @param object $club_entry club league entry object.
	 */
	public function league_entry( $club_entry ) {
		global $racketmanager;
		$competition = get_competition( $club_entry->competition );
		foreach ( $club_entry->event as $event_entry ) {
			$event                       = get_event( $event_entry->id );
			$league_event_entry['event'] = $event_entry->name;
			$league_entries              = array();
			foreach ( $event_entry->team as $team_entry ) {
				$match_day = Racketmanager_Util::get_match_day( $team_entry->match_day );
				if ( empty( $team_entry->id ) ) {
					$team = $this->add_team( $event->type );
				} else {
					$team = get_team( $team_entry->id );
				}
				$team_info = $event->get_team_info( $team->id );
				if ( ! $team_info ) {
					$team->add_event( $event_entry->id, $team_entry->captain_id, $team_entry->telephone, $team_entry->email, $match_day, $team_entry->match_time );
				} else {
					$team->update_event( $event_entry->id, $team_entry->captain_id, $team_entry->telephone, $team_entry->email, $match_day, $team_entry->match_time );
				}
				if ( $team_entry->existing ) {
					$event->mark_teams_entered( $team->id, $club_entry->season );
				} else {
					$event->add_team_to_event( $team->id, $club_entry->season );
				}
				$league_entry                 = array();
				$league_entry['teamName']     = $team->title;
				$league_entry['captain']      = $team_entry->captain;
				$league_entry['contactno']    = $team_entry->telephone;
				$league_entry['contactemail'] = $team_entry->email;
				$league_entry['matchday']     = $match_day;
				$league_entry['matchtime']    = substr( $team_entry->match_time, 0, 5 );
				$league_entries[]             = $league_entry;
			}
			if ( ! empty( $event_entry->withdrawn_teams ) ) {
				foreach ( $event_entry->withdrawn_teams as $team ) {
					if ( $team ) {
						$event->mark_teams_withdrawn( $club_entry->season, $this->id, $team );
					}
				}
			}
			$league_event_entry['teams'] = $league_entries;
			$event_details[]             = $league_event_entry;
		}
		$event_entries['events']               = $event_details;
		$event_entries['num_courts_available'] = $club_entry->num_courts_available;
		if ( ! empty( $club_entry->withdrawn_events ) ) {
			foreach ( $club_entry->withdrawn_events as $event_id ) {
				$event = get_event( $event_id );
				$event->mark_teams_withdrawn( $club_entry->season, $this->id );
			}
		}
		$competition->settings['num_courts_available'][ $this->id ] = $club_entry->num_courts_available;
		$competition->set_settings( $competition->settings );
		$email_to        = $this->match_secretary_name . ' <' . $this->match_secretary_email . '>';
		$email_from      = $racketmanager->get_confirmation_email( 'league' );
		$email_subject   = $racketmanager->site_name . ' - ' . ucfirst( $club_entry->competition->name ) . ' ' . $club_entry->season . ' League Entry - ' . $this->shortcode;
		$headers         = array();
		$secretary_email = __( 'League Secretary', 'racketmanager' ) . ' <' . $email_from . '>';
		$headers[]       = 'From: ' . $secretary_email;
		$headers[]       = 'Cc: ' . $secretary_email;

		$template                          = 'league-entry';
		$template_args['event_entries']    = $event_entries;
		$template_args['organisation']     = $racketmanager->site_name;
		$template_args['season']           = $club_entry->season;
		$template_args['competition_name'] = $club_entry->competition->name;
		$template_args['club']             = $this->name;
		$template_args['contact_email']    = $email_from;
		$template_args['comments']         = $club_entry->comments;
		$racketmanager->email_entry_form( $template, $template_args, $email_to, $email_subject, $headers );
	}
}
