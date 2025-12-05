<?php
/**
 * Shortcodes_Club API: Shortcodes_Club class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerShortcodesClub
 */

namespace Racketmanager\Public;

use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Util\Util_Lookup;
use stdClass;
use function Racketmanager\get_club;
use function Racketmanager\get_club_role;
use function Racketmanager\get_competition;
use function Racketmanager\get_event;
use function Racketmanager\get_invoice;
use function Racketmanager\get_player;
use function Racketmanager\get_team;
use function Racketmanager\show_invoice;
use function Racketmanager\un_seo_url;

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
                'clubs'                => $clubs,
                'user_can_update_club' => false,
                'standalone'           => false,
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
        try {
            $club = $this->club_service->get_club_by_shortcode( $club_name );
            $club_details = $this->club_service->get_club_details( $club->id );
        } catch ( Club_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
        $user_can_update_club = false;
        if ( is_user_logged_in() ) {
            $user   = wp_get_current_user();
            $userid = $user->ID;
            if ( current_user_can( 'manage_racketmanager' ) || $this->club_service->is_user_match_secretary( $userid, $club->id ) ) {
                $user_can_update_club   = true;
            }
        }
        $keys            = $racketmanager->get_options( 'keys' );
        $google_maps_key = $keys['googleMapsKey'] ?? '';

        $club->single = true;

        $filename = ( ! empty( $template ) ) ? 'club-' . $template : 'club';
        return $this->load_template(
            $filename,
            array(
                'club'                 => $club_details,
                'google_maps_key'      => $google_maps_key,
                'user_can_update_club' => $user_can_update_club,
                'standalone'           => true,
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
        $args     = shortcode_atts(
            array(
                'template' => '',
            ),
            $atts
        );
        $template = $args['template'];
        $filename = ( ! empty( $template ) ) ? 'players-' . $template : 'players';
        // Get Club by Name.
        $club_name   = get_query_var( 'club_name' );
        $club_name   = un_seo_url( $club_name );
        $players     = array();
        $club_player = null;
        try {
            $club = $this->club_service->get_club_by_shortcode( $club_name );
            // Get Player by Name.
            $player_name = get_query_var( 'player_id' );
            if ( $player_name ) {
                $player_name = un_seo_url( $player_name );
                $player      = $this->player_service->get_player_by_name( $player_name );
                $club_player = $this->club_player_service->get_player_for_club( $club->id, $player->id );
            } else {
                $players = $this->club_player_service->get_registered_players_list( 'active', null, $club->id, null );
            }

            return $this->load_template(
                $filename,
                array(
                    'club'            => $club,
                    'user_can_update' => $club->can_user_update_players(),
                    'players'         => $players,
                    'player'          => $club_player,
                ),
                'club'
            );
        } catch ( Club_Not_Found_Exception|Player_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
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
        $args     = shortcode_atts(
            array(
                'template' => '',
            ),
            $atts
        );
        $template = $args['template'];
        $filename = ( ! empty( $template ) ) ? 'competitions-' . $template : 'competitions';
        // Get Club by Name.
        $club_name = get_query_var( 'club_name' );
        $club_name = un_seo_url( $club_name );
        $club      = get_club( $club_name, 'shortcode' );
        if ( $club ) {
            $club_competitions = array();
            // Get competition by Name.
            $competition_name = get_query_var( 'competition_name' );
            if ( $competition_name ) {
                $competition_name = un_seo_url( $competition_name );
                $competition      = get_competition( $competition_name, 'name' );
                if ( $competition ) {
                    $club->competition = $competition;
                } else {
                    $msg = $this->competition_not_found;
                }
            } else {
                $competitions_types = array( 'cup', 'league' );
                foreach ( $competitions_types as $competition_type ) {
                    $club_competitions = $this->get_competitions( $club, $club_competitions, $competition_type );
                }
                $club->competitions = $club_competitions;
            }
            if ( empty( $msg ) ) {
                return $this->load_template(
                    $filename,
                    array(
                        'club'            => $club,
                        'user_can_update' => $club->can_user_update(),
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
     * Get competitions for club
     *
     * @param object $club
     * @param array $club_competitions
     * @param string $type
     *
     * @return array
     */
    private function get_competitions( object $club, array $club_competitions, string $type ): array {
        global $racketmanager;
        $competition_found = false;
        $c                 = 0;
        $competitions      = $racketmanager->get_competitions( array( 'type' => $type ) );
        foreach ( $competitions as $competition ) {
            $event_found = false;
            $events      = $competition->get_events();
            $e           = 0;
            foreach ( $events as $event ) {
                $teams        = $event->get_teams_info(
                    array(
                        'club'    => $club->id,
                        'orderby' => array( 'title' => 'ASC' ),
                    )
                );
                if ( $teams ) {
                    $event->teams = $teams;
                    $events[ $e ] = $event;
                    $event_found  = true;
                } else {
                    unset( $events[ $e ] );
                }
                ++$e;
            }
            if ( $event_found ) {
                $competition_found   = true;
                $competition->events = $events;
                $competitions[ $c ]  = $competition;
            } else {
                unset( $competitions[ $c ] );
            }
            ++$c;
        }
        if ( $competition_found ) {
            $club_competitions = array_merge( $club_competitions, $competitions );
        }
        return $club_competitions;
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
        $filename = ( ! empty( $template ) ) ? 'team-' . $template : 'team';
        $event    = null;
        $team     = null;
        // Get Club by Name.
        $club_name = get_query_var( 'club_name' );
        $club_name = un_seo_url( $club_name );
        $club      = get_club( $club_name, 'shortcode' );
        if ( ! $club ) {
            $msg = $this->club_not_found;
        }
        // Get team by Name.
        $team_name = get_query_var( 'team' );
        if ( $team_name ) {
            $team_name = un_seo_url( $team_name );
            $team      = get_team( $team_name );
            if ( ! $team ) {
                $msg = $this->team_not_found;
            }
        } else {
            $msg = __( 'Team not supplied', 'racketmanager' );
        }
        $event_name = get_query_var( 'event' );
        if ( $event_name ) {
            $event_name = un_seo_url( $event_name );
            $event      = get_event( $event_name, 'name' );
            if ( ! $event ) {
                $msg = $this->event_not_found;
            }
        } else {
            $msg = $this->no_event_id;
        }
        if ( empty( $msg ) ) {
            $team_info   = $event->get_team_info( $team->id );
            $team        = (object) array_merge( (array) $team, (array) $team_info );
            $club->event = $event;
            $club->team  = $team;
            return $this->load_template(
                $filename,
                array(
                    'club'            => $club,
                    'user_can_update' => $club->can_user_update(),
                ),
                'club'
            );
        }
        return $this->return_error( $msg );
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
        $filename = ( ! empty( $template ) ) ? 'event-' . $template : 'event';
        $event    = null;
        // Get Club by Name.
        $club_name = get_query_var( 'club_name' );
        $club_name = un_seo_url( $club_name );
        $club      = get_club( $club_name, 'shortcode' );
        if ( ! $club ) {
            $msg = $this->club_not_found;
        }
        $event_name = get_query_var( 'event' );
        if ( $event_name ) {
            $event_name = un_seo_url( $event_name );
            $event      = get_event( $event_name, 'name' );
            if ( ! $event ) {
                $msg = $this->event_not_found;
            }
        } else {
            $msg = $this->no_event_id;
        }
        $season = get_query_var( 'season' );
        if ( ! $season && ! isset( $event->current_season['name'] ) ) {
            $msg = __( 'No seasons for event', 'racketmanager' );
        }
        if ( empty( $msg ) ) {
            $season_dtls        = $event->current_season;
            $player_stats       = $event->get_player_stats(
                array(
                    'season' => $season_dtls['name'],
                    'club'   => $club->id,
                )
            );
            $club->event        = $event;
            $club->player_stats = $player_stats;
            return $this->load_template(
                $filename,
                array(
                    'club' => $club,
                ),
                'club'
            );
        }
        return $this->return_error( $msg );
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
        global $racketmanager;
        $args            = shortcode_atts(
            array(
                'template' => '',
            ),
            $atts
        );
        $template        = $args['template'];
        $msg             = null;
        $template_ref    = null;
        $user_can_update = null;
        // Get Club by Name.
        $club_name = get_query_var( 'club_name' );
        $club_name = un_seo_url( $club_name );
        $club      = get_club( $club_name, 'shortcode' );
        if ( ! $club ) {
            $msg = $this->club_not_found;
        }
        if ( empty( $msg ) ) {
            $user_can_update       = new stdClass();
            $user_can_update->club = false;
            if ( is_user_logged_in() ) {
                $user   = wp_get_current_user();
                $userid = $user->ID;
                if ( current_user_can( 'manage_racketmanager' ) || ( ! empty( $club->match_secretary->id ) && intval( $club->match_secretary->id ) === $userid ) ) {
                    $user_can_update->club   = true;
                }
            }
            // Get Invoice.
            $invoice_ref = get_query_var( 'invoice' );
            if ( $invoice_ref ) {
                $invoice = get_invoice( $invoice_ref );
                if ( $invoice ) {
                    if ( $invoice->club_id === $club->id ) {
                        $invoice->details = show_invoice( $invoice->id );
                        $club->invoice    = $invoice;
                        $template_ref     = 'invoice';
                    } else {
                        $msg = __( 'Invoice not for this club', 'racketmanager' );
                    }
                } else {
                    $msg = __( 'Invoice not found', 'racketmanager' );
                }
            } else {
                $club->invoices = $racketmanager->get_invoices( array( 'club' => $club->id ));
                $template_ref   = 'invoices';
            }
        }
        if ( empty( $msg ) ) {
            $filename = ( ! empty( $template ) ) ? $template_ref . '-' . $template : $template_ref;

            return $this->load_template(
                $filename,
                array(
                    'club'            => $club,
                    'user_can_manage' => $user_can_update,
                ),
                'club'
            );
        } else{
            return $this->return_error( $msg );
        }
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
        $event_team = null;
        if ( $team && $event ) {
            $event_team = $event->get_team_info( $team_id );
        } elseif ( ! $team ) {
            $msg = $this->team_not_found;
        } else {
            $msg = $this->event_not_found;
        }
        if ( empty( $msg ) ) {
            $match_days = Util_Lookup::get_match_days();
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
    /**
     * Function to display Club Roles
     *
     *  [club-roles template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string - the content
     */
    public function show_club_roles( array $atts ): string {
        $args     = shortcode_atts(
            array(
                'template' => '',
            ),
            $atts
        );
        $template = $args['template'];
        $filename = ( ! empty( $template ) ) ? 'roles-' . $template : 'roles';
        // Get Club by Name.
        $club_name = get_query_var( 'club_name' );
        $club_name = un_seo_url( $club_name );
        $club      = get_club( $club_name, 'shortcode' );
        if ( $club ) {
            return $this->load_template(
                $filename,
                array(
                    'club' => $club,
                ),
                'club'
            );
        } else {
            $msg = $this->club_not_found;
        }
        return $this->return_error( $msg );
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
    public function show_club_role_modal( array $atts ): string {
        $args          = shortcode_atts(
            array(
                'id'       => 0,
                'modal'    => null,
                'template' => '',
            ),
            $atts
        );
        $club_role_id  = $args['id'];
        $modal         = $args['modal'];
        $template      = $args['template'];
        $filename      = ( ! empty( $template ) ) ? 'club-role-modal-' . $template : 'club-role-modal';
        $club_role     = null;
        if ( $club_role_id ) {
            $club_role = get_club_role( $club_role_id );
        } else {
            $msg = __( 'Club role not found', 'racketmanager' );
        }
        if ( empty( $msg ) ) {
            $club_roles = Util_Lookup::get_club_roles();
            return $this->load_template(
                $filename,
                array(
                    'club_role'  => $club_role,
                    'modal'      => $modal,
                    'club_roles' => $club_roles,
                ),
                'club'
            );
        }
        return $this->return_error( $msg, 'modal' );
    }
}
