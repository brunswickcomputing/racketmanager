<?php
/**
 * Shortcodes_Club API: Shortcodes_Club class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerShortcodesClub
 */

namespace Racketmanager;

use stdClass;

/**
 * Class to implement shortcode functions
 */
class Shortcodes_Club extends Shortcodes {
	/**
	 * Function to display Clubs Info Page
	 *
	 *    [clubs template=X]
	 *
	 * @param array $atts attributes.
	 *
	 * @return string - the content
	 */
	public function show_clubs( array $atts ): string {
		global $racketmanager;
		$args     = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template = $args['template'];
		$clubs    = $racketmanager->get_clubs(
			array(
				'type' => 'current',
			)
		);
		$filename = ( ! empty( $template ) ) ? 'clubs-' . $template : 'clubs';
		return $this->load_template(
			$filename,
			array(
				'clubs'                  => $clubs,
				'user_can_update_club'   => false,
				'user_can_update_player' => false,
				'standalone'             => false,
			)
		);
	}

	/**
	 * Function to display Club Info Page
	 *
	 *    [club id=ID template=X]
	 *
	 * @param array $atts attributes.
	 *
	 * @return false|string - the content
	 */
	public function show_club( array $atts ): false|string {
		global $racketmanager;
		$args     = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template = $args['template'];
		// Get League by Name.
		$club_name = get_query_var( 'club_name' );
		$club_name = str_replace( '-', ' ', $club_name );

		$club = get_club( $club_name, 'shortcode' );

		if ( ! $club ) {
			return false;
		}
		$user_can_update_club   = false;
		$user_can_update_player = false;
		if ( is_user_logged_in() ) {
			$user   = wp_get_current_user();
			$userid = $user->ID;
			if ( current_user_can( 'manage_racketmanager' ) || ( null !== $club->matchsecretary && intval( $club->matchsecretary ) === $userid ) ) {
				$user_can_update_club   = true;
				$user_can_update_player = true;
			} else {
				$options = $racketmanager->get_options( 'rosters' );
				if ( isset( $options['rosterEntry'] ) && 'captain' === $options['rosterEntry'] && $club->is_player_captain( $userid ) ) {
					$user_can_update_player = true;
				}
			}
		}
		$club_players    = $club->get_players(
			array(
				'active' => true,
				'type'   => 'real',
				'cache'  => false,
			)
		);
		$player_requests = $club->get_players(
			array(
				'club'   => $club->id,
				'status' => 'outstanding',
			)
		);
		$keys            = $racketmanager->get_options( 'keys' );
		$google_maps_key = $keys['googleMapsKey'] ?? '';

		$club->single = true;

		$filename = ( ! empty( $template ) ) ? 'club-' . $template : 'club';
		return $this->load_template(
			$filename,
			array(
				'club'                   => $club,
				'club_players'           => $club_players,
				'player_requests'        => $player_requests,
				'google_maps_key'        => $google_maps_key,
				'user_can_update_club'   => $user_can_update_club,
				'user_can_update_player' => $user_can_update_player,
				'standalone'             => true,
			)
		);
	}
	/**
	 * Function to display Club Players
	 *
	 *  [club-players player_id=ID template=X]
	 *
	 * @param array $atts shortcode attributes.
	 *
	 * @return string - the content
	 */
	public function show_club_players( array $atts ): string {
		global $racketmanager;
		$args     = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template = $args['template'];
        $filename = ( ! empty( $template ) ) ? 'players-' . $template : 'players';
		// Get Club by Name.
		$club_name = get_query_var( 'club_name' );
		$club_name = un_seo_url( $club_name );
		$club      = get_club( $club_name, 'shortcode' );
		if ( $club ) {
            // Get Player by Name.
            $player_name = get_query_var( 'player_id' );
            if ( $player_name ) {
                $player = $this->get_player_details( $player_name, $club );
                if ( ! is_object( $player ) ) {
                    $msg = $player;
                } else {
                    $club->player = $player;
                }
             } else {
                $club->players = $club->get_players(
                    array(
                        'active' => true,
                        'type'   => 'real',
                        'cache'  => false,
                    )
                );
            }
            if ( empty( $msg ) ) {
                return $this->load_template(
                    $filename,
                    array(
                        'club'            => $club,
                        'user_can_update' => $club->can_user_update_players(),
                    ),
                    'club'
                );
            }
        } else {
            $msg = $this->club_not_found;
		}
        return $this->return_error( $msg );
	}

    /**
     * Get player details
     *
     * @param string $player_name
     * @param object $club
     *
     * @return object|string|null
     */
    private function get_player_details( string $player_name, object $club ): object|string|null {
        $player_name = un_seo_url( $player_name );
        $player      = get_player( $player_name, 'name' ); // get player by name.
        if ( $player ) {
            $club_player = $club->get_players( array( 'player' => $player->id ) );
            if ( $club_player ) {
                $player->club              = $club;
                $player->created_date      = $club_player[0]->created_date;
                $player->created_user      = $club_player[0]->created_user;
                $player->created_user_name = $club_player[0]->created_user_name;
                return $player;
            } else {
                return $this->club_player_not_found;
            }
        } else {
            return $this->player_not_found;
        }
    }
	/**
	 * Function to display Club competitions
	 *
	 *  [club-competitions club= competition_name= template=X]
	 *
	 * @param array $atts shortcode attributes.
	 *
	 * @return string - the content
	 */
	public function show_club_competitions( array $atts ): string {
		global $racketmanager;
		$args     = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template = $args['template'];
		// Get Club by Name.
		$club_name = get_query_var( 'club_name' );
		$club_name = un_seo_url( $club_name );
		$club      = get_club( $club_name, 'shortcode' );
		if ( ! $club ) {
			return $this->club_not_found;
		}
		$club_competitions = array();
		// Get competition by Name.
		$competition_name = get_query_var( 'competition_name' );
		if ( $competition_name ) {
			$competition_name = un_seo_url( $competition_name );
			$competition      = get_competition( $competition_name, 'name' );
			if ( ! $competition ) {
				return $this->competition_not_found;
			}
			$club->competition = $competition;
		} else {
			$competitions_types = array( 'cup', 'league' );
			foreach ( $competitions_types as $competition_type ) {
				$c            = 0;
				$competitions = $racketmanager->get_competitions( array( 'type' => $competition_type ) );
				foreach ( $competitions as $competition ) {
					$events = $competition->get_events();
					$e      = 0;
					foreach ( $events as $event ) {
						$teams        = $event->get_teams_info(
							array(
								'club'    => $club->id,
								'orderby' => array( 'title' => 'ASC' ),
							)
						);
						$event->teams = $teams;
						$events[ $e ] = $event;
						++$e;
					}
					$competition->events = $events;
					$competitions[ $c ]  = $competition;
					++$c;
				}
				$club_competitions = array_merge( $club_competitions, $competitions );
			}
		}
		$club->competitions = $club_competitions;
		$user_can_update    = false;
		if ( is_user_logged_in() ) {
			$user   = wp_get_current_user();
			$userid = $user->ID;
			if ( current_user_can( 'manage_racketmanager' ) || ( null !== $club->matchsecretary && intval( $club->matchsecretary ) === $userid ) ) {
				$user_can_update = true;
			}
		}
		$filename = ( ! empty( $template ) ) ? 'competitions-' . $template : 'competitions';
		return $this->load_template(
			$filename,
			array(
				'club'            => $club,
				'user_can_update' => $user_can_update,
			),
			'club'
		);
	}
	/**
	 * Function to display Club team
	 *
	 *  [club-team club= team= template=X]
	 *
	 * @param array $atts shortcode attributes.
	 *
	 * @return string - the content
	 */
	public function show_club_team( array $atts ): string {
		$args     = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template = $args['template'];
		// Get Club by Name.
		$club_name = get_query_var( 'club_name' );
		$club_name = un_seo_url( $club_name );
		$club      = get_club( $club_name, 'shortcode' );
		if ( ! $club ) {
			return $this->club_not_found;
		}
		// Get team by Name.
		$team_name = get_query_var( 'team' );
		if ( $team_name ) {
			$team_name = un_seo_url( $team_name );
			$team      = get_team( $team_name );
			if ( ! $team ) {
				return $this->team_not_found;
			}
		} else {
			return __( 'Team not supplied', 'racketmanager' );
		}
		$event_name = get_query_var( 'event' );
		if ( $event_name ) {
			$event_name = un_seo_url( $event_name );
			$event      = get_event( $event_name, 'name' );
			if ( ! $event ) {
				return $this->event_not_found;
			}
		} else {
			return $this->no_event_id;
		}
		$team_info       = $event->get_team_info( $team->id );
		$team            = (object) array_merge( (array) $team, (array) $team_info );
		$club->event     = $event;
		$club->team      = $team;
		$user_can_update = false;
		if ( is_user_logged_in() ) {
			$user   = wp_get_current_user();
			$userid = $user->ID;
			if ( current_user_can( 'manage_racketmanager' ) || ( null !== $club->matchsecretary && intval( $club->matchsecretary ) === $userid ) ) {
				$user_can_update = true;
			}
		}
		$filename = ( ! empty( $template ) ) ? 'team-' . $template : 'team';
		return $this->load_template(
			$filename,
			array(
				'club'            => $club,
				'user_can_update' => $user_can_update,
			),
			'club'
		);
	}
	/**
	 * Function to display Club event
	 *
	 *  [club-event club= event= template=X]
	 *
	 * @param array $atts shortcode attributes.
	 *
	 * @return string - the content
	 */
	public function show_club_event( array $atts ): string {
		$args     = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template = $args['template'];
		// Get Club by Name.
		$club_name = get_query_var( 'club_name' );
		$club_name = un_seo_url( $club_name );
		$club      = get_club( $club_name, 'shortcode' );
		if ( ! $club ) {
			return $this->club_not_found;
		}
		$event_name = get_query_var( 'event' );
		if ( $event_name ) {
			$event_name = un_seo_url( $event_name );
			$event      = get_event( $event_name, 'name' );
			if ( ! $event ) {
				return $this->event_not_found;
			}
		} else {
			return $this->no_event_id;
		}
		$season = get_query_var( 'season' );
		if ( ! $season && ! isset( $event->current_season['name'] ) ) {
            return __( 'No seasons for event', 'racketmanager' );
        }
		$season_dtls        = $event->current_season;
		$player_stats       = $event->get_player_stats(
			array(
				'season' => $season_dtls['name'],
				'club'   => $club->id,
			)
		);
		$club->event        = $event;
		$club->player_stats = $player_stats;
		$filename           = ( ! empty( $template ) ) ? 'event-' . $template : 'event';
		return $this->load_template(
			$filename,
			array(
				'club' => $club,
			),
			'club'
		);
	}
	/**
	 * Function to display Club Invoices
	 *
	 *  [club-invoices invoice_id=ID template=X]
	 *
	 * @param array $atts shortcode attributes.
	 *
	 * @return string - the content
	 */
	public function show_club_invoices( array $atts ): string {
		$args     = shortcode_atts(
			array(
				'template' => '',
			),
			$atts
		);
		$template = $args['template'];
		// Get Club by Name.
		$club_name = get_query_var( 'club_name' );
		$club_name = un_seo_url( $club_name );
		$club      = get_club( $club_name, 'shortcode' );
		if ( ! $club ) {
			return $this->return_error( $this->club_not_found );
		}
		// Get Invoice.
		$invoice_ref = get_query_var( 'invoice' );
		if ( $invoice_ref ) {
			$invoice = get_invoice( $invoice_ref );
			if ( ! $invoice ) {
				return $this->return_error( __( 'Invoice not found', 'racketmanager' ) );
			}
			$invoice->details = show_invoice( $invoice->id );
			$club->invoice    = $invoice;
		} else {
			$club->invoices = $club->get_invoices();
		}
		$user_can_update         = new stdClass();
		$user_can_update->club   = false;
		$user_can_update->player = false;
		if ( is_user_logged_in() ) {
			$user   = wp_get_current_user();
			$userid = $user->ID;
			if ( current_user_can( 'manage_racketmanager' ) || ( null !== $club->matchsecretary && intval( $club->matchsecretary ) === $userid ) ) {
				$user_can_update->club   = true;
			}
		}
		$filename = ( ! empty( $template ) ) ? 'invoices-' . $template : 'invoices';
		return $this->load_template(
			$filename,
			array(
				'club'            => $club,
				'user_can_manage' => $user_can_update,
			),
			'club'
		);
	}
    /**
     * Function to display Club Invoices
     *
     *  [team-edit id=team_ID template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string - the content
     */
    public function show_team_edit_modal( array $atts ): string {
        $args     = shortcode_atts(
            array(
                'id'       => 0,
                'modal'    => null,
                'event_id' => null,
                'template' => '',
            ),
            $atts
        );
        $team_id  = $args['id'];
        $modal    = $args['modal'];
        $event_id = $args['event_id'];
        $template = $args['template'];
        $team     = null;
        $event    = null;
        $filename = ( ! empty( $template ) ) ? 'team-edit-modal-' . $template : 'team-edit-modal';
        if ( $team_id ) {
            $team = get_team( $team_id );
        } else {
            $msg = $this->no_team_id;
        }
        if ( $event_id ) {
            $event = get_event( $event_id );
        } else {
            $msg = $this->no_event_id;
        }
        if ( $team && $event ) {
            $event_team = $event->get_team_info( $team_id );
        } elseif ( ! $team ) {
            $msg = $this->team_not_found;
        } else {
            $msg = $this->event_not_found;
        }
        if ( empty( $msg ) ) {
            $match_days = Racketmanager_Util::get_match_days();
            return $this->load_template(
                $filename,
                array(
                    'team'       => $team,
                    'event'      => $event,
                    'modal'      => $modal,
                    'match_days' => $match_days,
                    'event_team' => $event_team,
                ),
                'club'
            );
        } else {
            $msg = __( 'Event team not found', 'racketmanager' );
        }
        return $this->return_error( $msg, 'modal' );
    }
}
