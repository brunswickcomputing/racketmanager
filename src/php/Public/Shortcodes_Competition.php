<?php /** @noinspection PhpMissingParentConstructorInspection */

/**
 * Shortcodes_Competition API: Shortcodes_Competition class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes/Competition
 */

namespace Racketmanager\Public;

use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Services\Stripe_Settings;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use stdClass;
use function Racketmanager\get_charge;
use function Racketmanager\get_club;
use function Racketmanager\get_event;
use function Racketmanager\get_player;
use function Racketmanager\get_tab;
use function Racketmanager\get_tournament;
use function Racketmanager\get_tournament_entry;
use function Racketmanager\seo_url;
use function Racketmanager\un_seo_url;

/**
 * Class to implement the Shortcodes_Competition object
 */
class Shortcodes_Competition extends Shortcodes {
    /**
     * Show competitions function
     *
     * @param array $atts attributes.
     * @return string display output
     */
    public function show_competitions( array $atts ): string {
        global $wp, $racketmanager;
        $args     = shortcode_atts(
            array(
                'type'      => false,
                'age_group' => false,
            ),
            $atts
        );
        $type      = $args['type'];
        $age_group = $args['age_group'];
        if ( ! $type ) {
            if ( isset( $_GET['competition_type'] ) && ! empty( $_GET['type'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $type = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['type'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            } elseif ( isset( $wp->query_vars['type'] ) ) {
                $type = get_query_var( 'type' );
            }
            if ( ! $type ) {
                $msg = __( 'Competition type not set', 'racketmanager' );
                return $this->return_error( $msg );
            }
        }
        $type = un_seo_url( $type );
        if ( isset( $wp->query_vars['age_group'] ) ) {
            $age_group = get_query_var( 'age_group' );
        }
        $player = null;
        if ( is_user_logged_in() ) {
            $player = get_player( get_current_user_id() );
        }
        $query_args = array();
        if ( $type ) {
            $query_args['type'] = $type;
        }
        if ( $age_group ) {
            $query_args['age_group'] = $age_group;
        }
        if ( 'tournament' === $type ) {
            $query_args['orderby'] = array( 'date' => 'DESC' );
            $tournaments           = $racketmanager->get_tournaments( $query_args );
            $competitions          = array();
            foreach ( $tournaments as $tournament ) {
                $tournament->type     = $type;
                $tournament->date_end = $tournament->date;
                $competitions[]       = $tournament;
            }
            $user_competitions = $player?->get_tournaments($query_args);
        } else {
            $competitions      = $this->competition_service->get_by_criteria( $query_args );
            $user_competitions = $player?->get_competitions($query_args);
        }
        $competition_type = match ($type) {
            'league'     => __('Leagues', 'racketmanager'),
            'cup'        => __('Cups', 'racketmanager'),
            'tournament' => __('Tournaments', 'racketmanager'),
            default      => __('Competitions', 'racketmanager'),
        };
        $filename = 'competitions';

        return $this->load_template(
            $filename,
            array(
                'competitions'      => $competitions,
                'type'              => $competition_type,
                'user_competitions' => $user_competitions,
            )
        );
    }
    /**
     * Show competition function
     *
     * @param array $atts attributes.
     * @return string display output
     */
    public function show_competition( array $atts ): string {
        global $wp;
        $args        = shortcode_atts(
            array(
                'competition' => false,
                'season'      => false,
                'template'    => '',
            ),
            $atts
        );
        $competition_id = $args['competition'];
        $season         = $args['season'];
        $template       = $args['template'];
        $competition    = null;
        $msg            = null;

        if ( ! $competition_id ) {
            if (! empty( $_GET['competition'] )) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $competition_id = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['competition'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            } elseif ( isset( $wp->query_vars['competition'] ) ) {
                $competition_id = get_query_var( 'competition' );
            }
            $competition_id = un_seo_url( $competition_id );
        }
        try {
            $competition = $this->competition_service->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            $msg = $e->getMessage();
        }
        if ( empty( $msg ) ) {
            $seasons = $competition->get_seasons();
            if ( empty( $seasons ) ) {
                $msg = __( 'No seasons found for competition', 'racketmanager' );
            } else {
                $competition->set_current_season( $season );
                if ( empty( $competition->current_season ) ) {
                    $msg = __( 'Season not found for competition', 'racketmanager' );
                } else {
                    $season             = $competition->current_season['name'];
                    $competition_season = $competition->current_season;
                    if ( ! empty( $competition_season['venue'] ) ) {
                        $venue_club = get_club( $competition_season['venue'] );
                        if ( $venue_club ) {
                            $competition_season['venue_name'] = $venue_club->shortcode;
                        }
                    }
                    $tab = get_tab();
                    if ( $competition->is_open && is_user_logged_in() ) {
                        $entry_link = '/entry-form/' . seo_url( $competition->name ) . '/' . $season . '/';
                        $clubs      = $this->club_selection_available( $competition );
                        if ( $clubs ) {
                            if ( ! is_array( $clubs ) ) {
                                $entry_link .= seo_url( $clubs->shortcode ) . '/';
                            }
                            $competition->entry_link = $entry_link;
                        }
                    }
                    $filename = ( ! empty( $template ) ) ? 'competition-' . $template : 'competition';

                    return $this->load_template(
                        $filename,
                        array(
                            'competition'        => $competition,
                            'competition_season' => $competition_season,
                            'tab'                => $tab,
                            'seasons'            => $seasons,
                        )
                    );
                }
            }
        }
        return $this->return_error( $msg );
    }
    /**
     * Show competition overview function
     *
     * @param array $atts function attributes.
     * @return string
     */
    public function show_competition_overview( array $atts ): string {
        $args           = shortcode_atts(
            array(
                'id'       => false,
                'season'   => null,
                'template' => '',
            ),
            $atts
        );
        $competition_id = $args['id'];
        $season         = $args['season'];
        $template       = $args['template'];
        try {
            $competition = $this->competition_service->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
        if ( $season ) {
            $competition->set_current_season( $season );
        } else {
            $season = $competition->current_season['name'];
        }
        $competition_overview = $this->competition_service->get_competition_overview( $competition->get_id(), $season );

        $filename = ( ! empty( $template ) ) ? 'overview-' . $template : 'overview';
        return $this->load_template(
            $filename,
            array(
                'competition'        => $competition,
                'overview'           => $competition_overview,
                'competition_season' => $competition->current_season,
            ),
            'competition'
        );
    }
    /**
     * Show events function
     *
     * @param array $atts function attributes.
     * @return string
     */
    public function show_competition_events( array $atts ): string {
        $args           = shortcode_atts(
            array(
                'id'       => false,
                'season'   => null,
                'template' => '',
            ),
            $atts
        );
        $competition_id = $args['id'];
        $season         = $args['season'];
        $template       = $args['template'];
        try {
            $competition = $this->competition_service->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
        if ( $season ) {
            $competition->set_current_season( $season );
        } else {
            $season = $competition->current_season['name'];
        }
        $events = $this->competition_service->get_events_with_details_for_competition( $competition_id, $season );

        $tab      = 'events';
        $filename = ( ! empty( $template ) ) ? 'events-' . $template : 'events';

        return $this->load_template(
            $filename,
            array(
                'competition' => $competition,
                'events'      => $events,
                'tab'         => $tab,
            ),
            'competition'
        );
    }
    /**
     * Show teams function
     *
     * @param array $atts function attributes.
     * @return string
     */
    public function show_competition_teams( array $atts ): string {
        $args           = shortcode_atts(
            array(
                'id'       => false,
                'season'   => null,
                'template' => '',
            ),
            $atts
        );
        $competition_id = $args['id'];
        $season         = $args['season'];
        $template       = $args['template'];
        try {
            $competition = $this->competition_service->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
        if ( $season ) {
            $competition->set_current_season( $season );
        } else {
            $season = $competition->current_season['name'];
        }
        $teams = $this->competition_service->get_teams_for_competition( $competition_id, $season );
        $competition->teams = $competition->get_teams(
            array(
                'status'  => 1,
                'season'  => $season,
                'orderby' => array( 'name' => 'ASC' ),
            )
        );

        $tab      = 'teams';
        $filename = ( ! empty( $template ) ) ? 'teams-' . $template : 'teams';

        return $this->load_template(
            $filename,
            array(
                'competition' => $competition,
                'teams'       => $teams,
                'tab'         => $tab,
            ),
            'competition'
        );
    }
    /**
     * Function to display competition Clubs
     *
     * @param array $atts shortcode attributes.
     * @return string the content
     */
    public function show_competition_clubs( array $atts ): string {
        global $wp;
        $args           = shortcode_atts(
            array(
                'id'       => 0,
                'season'   => null,
                'clubs'    => null,
                'template' => '',
            ),
            $atts
        );
        $competition_id = $args['id'];
        $season         = $args['season'];
        $club_id        = $args['clubs'];
        $template       = $args['template'];
        try {
            $competition = $this->competition_service->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
        if ( $season ) {
            $competition->set_current_season( $season );
        } else {
            $season = $competition->current_season['name'];
        }
        $clubs            = null;
        $competition_club = null;
        if ( ! $club_id && isset( $wp->query_vars['club_name'] ) ) {
            $club_id = get_query_var( 'club_name' );
            $club_id = str_replace( '-', ' ', $club_id );
        }
        if ( $club_id ) {
            if ( is_numeric( $club_id ) ) {
                $competition_club = get_club( $club_id );
            } else {
                $competition_club = get_club( $club_id, 'shortcode' );
            }
            if ( $competition_club ) {
                $competition_club->teams   = $competition->get_teams(
                    array(
                        'club'   => $competition_club->id,
                        'season' => $season,
                        'status' => 1,
                    )
                );
                $competition_club->matches = array();
                $matches                   = $competition->get_matches(
                    array(
                        'season'  => $season,
                        'club'    => $competition_club->id,
                        'time'    => 'next',
                        'orderby' => array(
                            'date'      => 'ASC',
                            'league_id' => 'DESC',
                        ),
                    )
                );
                foreach ( $matches as $match ) {
                    $key = substr( $match->date, 0, 10 );
                    if ( false === array_key_exists( $key, $competition_club->matches ) ) {
                        $competition_club->matches[ $key ] = array();
                    }
                    $competition_club->matches[ $key ][] = $match;
                }
                $competition_club->results = array();
                $matches                   = $competition->get_matches(
                    array(
                        'season'  => $season,
                        'club'    => $competition_club->id,
                        'time'    => 'latest',
                        'orderby' => array(
                            'date'      => 'ASC',
                            'league_id' => 'DESC',
                        ),
                    )
                );
                foreach ( $matches as $match ) {
                    $key = substr( $match->date, 0, 10 );
                    if ( false === array_key_exists( $key, $competition_club->results ) ) {
                        $competition_club->results[ $key ] = array();
                    }
                    $competition_club->results[ $key ][] = $match;
                }
                $competition_club->players = $competition->get_players(
                    array(
                        'club'   => $competition_club->id,
                        'season' => $season,
                        'stats'  => true,
                    )
                );
            } else {
                $msg = $this->club_not_found;
                return $this->return_error( $msg );
            }
        } else {
            $clubs = $this->competition_service->get_club_details_for_competition( $competition_id, $season );
        }
        $filename = ( ! empty( $template ) ) ? 'clubs-' . $template : 'clubs';
        return $this->load_template(
            $filename,
            array(
                'competition'      => $competition,
                'competition_club' => $competition_club,
                'clubs' => $clubs,
            ),
            'competition'
        );
    }
    /**
     * Function to display competition Players
     *
     * @param array $atts shortcode attributes.
     * @return string the content
     */
    public function show_competition_players( array $atts ): string {
        global $wp;
        $args           = shortcode_atts(
            array(
                'id'       => 0,
                'season'   => false,
                'players'  => null,
                'template' => '',
            ),
            $atts
        );
        $competition_id = $args['id'];
        $season         = $args['season'];
        $player_id      = $args['players'];
        $template       = $args['template'];
        try {
            $competition = $this->competition_service->get_by_id( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
        if ( $season ) {
            $competition->set_current_season( $season );
        }
        $competition->players = array();
        if ( ! $player_id && isset( $wp->query_vars['player_id'] ) ) {
            $player_id = un_seo_url( get_query_var( 'player_id' ) );
        }
        if ( $player_id ) {
            if ( is_numeric( $player_id ) ) {
                $player = get_player( $player_id ); // get player by id.
            } else {
                $player = get_player( $player_id, 'name' ); // get a player by name.
            }
            if ( $player ) {
                $player->matches = $player->get_matches( $competition, $competition->current_season['name'], 'competition' );
                asort( $player->matches );
                $player->stats       = $player->get_stats();
                $competition->player = $player;
            } else {
                echo $this->player_not_found;
            }
        } else {
            $players              = $this->player_service->get_players_for_competition( $competition_id, $season );
            $competition->players = Util::get_players_list( $players );
        }
        $filename = ( ! empty( $template ) ) ? 'players-' . $template : 'players';
        return $this->load_template(
            $filename,
            array(
                'competition' => $competition,
            ),
            'competition'
        );
    }
    /**
     * Function to display competition winners
     *
     * @param array $atts shortcode attributes.
     * @return string the content
     */
    public function show_competition_winners( array $atts ): string {
        $args           = shortcode_atts(
            array(
                'id'       => 0,
                'season'   => false,
                'template' => '',
            ),
            $atts
        );
        $competition_id = $args['id'];
        $season         = $args['season'];
        $template       = $args['template'];
        try {
            $competition = $this->competition_service->get_by_id( $competition_id );
            $winners     = $this->competition_service->get_winners_for_competition( $competition_id, $season );
        } catch ( Competition_Not_Found_Exception|Season_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
        $filename = ( ! empty( $template ) ) ? 'winners-' . $template : 'winners';
        return $this->load_template(
            $filename,
            array(
                'competition' => $competition,
                'winners'     => $winners,
            ),
            'competition'
        );
    }
    /**
     * Function to display Competition Entry Page
     *
     *    [competition-entry id=ID template=X]
     *
     * @param array $atts shortcode attributes.
     * @return string content
     */
    public function show_competition_entry( array $atts ): string {
        if ( ! is_user_logged_in() ) {
            $msg = __( 'You need to be logged in to enter', 'racketmanager' );
            return $this->return_error( $msg );
        }
        $args             = shortcode_atts(
            array(
                'template' => '',
            ),
            $atts
        );
        $template           = $args['template'];
        $valid              = true;
        $is_tournament      = false;
        $competition_name   = get_query_var( 'competition_name' );
        $competition_name   = un_seo_url( $competition_name );
        $tournament_name    = null;
        $season             = null;
        $competition_season = null;
        $club               = null;
        $tournament         = null;
        $player             = null;
        $msg                = null;
        if ( $competition_name ) {
            $type = get_query_var( 'competition_type' );
            if ( 'tournament' === $type ) {
                $is_tournament   = true;
                $tournament_name = $competition_name;
            }
        } else {
            $tournament_name = get_query_var( 'tournament' );
            if ( $tournament_name ) {
                $tournament_name = un_seo_url( $tournament_name );
                $is_tournament   = true;
            } else {
                $valid = false;
                $msg   = $this->competition_not_found;
            }
        }
        if ( $is_tournament ) {
            $tournament = get_tournament( $tournament_name, 'name' );
            if ( $tournament ) {
                $is_tournament = true;
            } else {
                $valid = false;
                $msg   = $this->tournament_not_found;
            }
        }
        if ( $valid ) {
            if ( $is_tournament ) {
                $competition_ref    = $tournament->competition_id;
                $competition_lookup = null;
            } else {
                $competition_ref    = $competition_name;
                $competition_lookup = 'name';
            }
            $competition = $this->competition_service->get_by_id( $competition_ref );
            if ( $competition ) {
                if ( $competition->is_tournament ) {
                    $player_id = get_query_var( 'player_id' );
                    if ( $player_id ) {
                        $player_id = un_seo_url( $player_id );
                        $player    = get_player( $player_id, 'name' );
                    } else {
                        $player_id = wp_get_current_user()->ID;
                        $player    = get_player( $player_id );
                    }
                    if ( $player ) {
                        if ( empty( $tournament ) ) {
                            $tournament = null;
                        }
                    } else {
                        $valid = false;
                        $msg   = $this->player_not_found;
                    }
                } else {
                    $season = get_query_var( 'season' );
                    if ( $season ) {
                        $competition_season = $competition->get_season_by_name( $season );
                        if ( $competition_season ) {
                            if ( ! empty( $competition_season['venue'] ) ) {
                                $venue_club = get_club( $competition_season['venue'] );
                                if ( $venue_club ) {
                                    $competition_season['venue_name'] = $venue_club->shortcode;
                                }
                            }
                            $club_name = get_query_var( 'club_name' );
                            if ( $club_name ) {
                                $club_name = un_seo_url( $club_name );
                                $club      = get_club( $club_name, 'shortcode' );
                                if ( $club ) {
                                    //check user authorised for club
                                    $can_enter = $this->club_selection_available( $competition, $club->id );
                                    if ( ! $can_enter ) {
                                        $valid = false;
                                        $msg   = __( 'User not authorised for club entry for this competition', 'racketmanager' );
                                    }
                                } else {
                                    $valid = false;
                                    $msg   = $this->club_not_found;
                                }
                            } else {
                                $club_choice = $this->show_club_selection( $competition, $season, $competition_season );
                                if ( ! $club_choice ) {
                                    $valid = false;
                                    $msg   = __( 'No club specified', 'racketmanager' );
                                }
                            }
                        } else {
                            $valid = false;
                            $msg   = $this->season_not_found;
                        }
                    } else {
                        $valid = false;
                        $msg   = __( 'No season specified', 'racketmanager' );
                    }
                }
            } else {
                $valid = false;
                $msg   = $this->competition_not_found;
            }
        }
        if ( $valid ) {
            if ( ! empty( $club_choice ) ) {
                $output = $club_choice;
            } else {
                $output = match ( $competition->type ) {
                    'league'     => $this->show_league_entry( $competition, $season, $competition_season, $club, $template ),
                    'cup'        => $this->show_cup_entry( $competition, $season, $competition_season, $club, $template ),
                    'tournament' => $this->show_tournament_entry( $tournament, $player, $template ),
                    default      => $this->return_error( __('Invalid competition type specified', 'racketmanager') ),
                };
            }
            return $output;
        } else {
            return $this->return_error( $msg );
        }
    }
    /**
     * Function to check if club selection is available
     *
     * @param object $competition competition object.
     * @param false|int $club_id (optional) club id.
     * @return false|object|boolean|int|array of clubs or individual club or indicator if club entry allowed or number of clubs
     */
    protected function club_selection_available( object $competition, false|int $club_id = false ): object|int|bool|array {
        global $racketmanager;
        $clubs        = null;
        $user         = wp_get_current_user();
        $userid       = $user->ID;
        $args['type'] = 'affiliated';
        if ( $club_id ) {
            $args['club']  = $club_id;
            $args['count'] = true;
        }
        if ( current_user_can( 'manage_racketmanager' ) ) {
            $clubs = $this->club_service->get_clubs( $args );
        } else {
            $competition_options = $racketmanager->get_options( $competition->type );
            if ( $competition_options ) {
                $entry_option = $competition_options['entry_level'] ?? null;
                if ( $entry_option ) {
                    $clubs = $this->player_service->find_all_associated_clubs( $userid, $entry_option, $club_id );
                }
            }
        }
        if ( $clubs ) {
            $result = $clubs;
        } else {
            $result = false;
        }
        return $result;
    }
    /**
     * Function to show club selection entry list
     *
     * @param object $competition competition object.
     * @param string $season season name.
     * @param array $competition_season competition season details.
     * @return string|boolean screen or no details
     */
    private function show_club_selection( object $competition, string $season, array $competition_season ): false|string {
        $clubs = $this->club_selection_available( $competition );
        if ( $clubs ) {
            return $this->load_template(
                'entry-form-clubs-list',
                array(
                    'competition'        => $competition,
                    'season'             => $season,
                    'competition_season' => $competition_season,
                    'clubs'              => $clubs,
                )
            );
        } else {
            return false;
        }
    }
    /**
     * Function to display competition payment Page
     *
     * @param array $atts shortcode attributes.
     * @return string the content
     */
    public function show_competition_entry_payment( array $atts ): string {
        $args     = shortcode_atts(
            array(
                'template'  => '',
            ),
            $atts
        );
        $template = $args['template'];
        $valid            = true;
        $msg              = null;
        $invoice_id       = null;
        $total_due        = null;
        $tournament_entry = null;
        $tournament       = null;
        $player           = null;
        $type             = get_query_var( 'competition_type' );
        if ( 'tournament' === $type ) {
            $tournament_name = get_query_var( 'tournament' );
            if ( $tournament_name ) {
                $tournament_name = un_seo_url( $tournament_name );
                $tournament      = get_tournament( $tournament_name, 'name' );
                if ( $tournament ) {
                    $charge_key = $tournament->competition_id . '_' . $tournament->season;
                    $charge     = get_charge( $charge_key );
                    if ( $charge ) {
                        $player_id = wp_get_current_user()->ID;
                        $player    = get_player( $player_id );
                        if ( $player ) {
                            $args['charge']       = $charge->id;
                            $args['player']       = $player_id;
                            $args['status']       = 'open';
                            $outstanding_payments = $this->finance_service->get_invoices_by_criteria( $args );
                            $total_due            = 0;
                            foreach ( $outstanding_payments as $invoice ) {
                                $total_due += $invoice->amount;
                                $invoice_id = $invoice->id;
                            }
                            $search           = $tournament->id . '_' . $player->id;
                            $tournament_entry = get_tournament_entry( $search, 'key' );
                        } else {
                            $valid = false;
                            $msg   = $this->player_not_found;
                        }
                    } else {
                        $valid = false;
                        $msg   = __( 'Charge not found', 'racketmanager' );
                    }
                } else {
                    $valid = false;
                    $msg   = $this->tournament_not_found;
                }
            } else {
                $valid = false;
                $msg   = __( 'No tournament name specified', 'racketmanager' );
            }
        }
        if ( $valid ) {
            $stripe_details = new Stripe_Settings( $this->racketmanager );
            $filename       = ( ! empty( $template ) ) ? 'tournament-payment-' . $template : 'tournament-payment';

            return $this->load_template(
                $filename,
                array(
                    'tournament'       => $tournament,
                    'player'           => $player,
                    'tournament_entry' => $tournament_entry,
                    'total_due'        => $total_due,
                    'invoice_id'       => $invoice_id,
                    'stripe'           => $stripe_details,
                ),
                'entry'
            );
        } else {
            return $this->return_error( $msg );
        }
    }
    /**
     * Function to display competition payment completion Page
     *
     * @return string the content
     */
    public function show_competition_entry_payment_complete(): string {
        $msg  = null;
        $type = get_query_var( 'competition_type' );
        if ( 'tournament' === $type ) {
            $tournament_name = get_query_var( 'tournament' );
            if ( $tournament_name ) {
                $tournament_name = un_seo_url( $tournament_name );
                $tournament      = get_tournament( $tournament_name, 'name' );
                if ( $tournament ) {
                    $player_id = wp_get_current_user()->ID;
                    $player    = get_player( $player_id );
                    if ( $player ) {
                        $search           = $tournament->id . '_' . $player->id;
                        $tournament_entry = get_tournament_entry( $search, 'key' );
                        $filename         = 'tournament-payment-complete';
                        return $this->load_template(
                            $filename,
                            array(
                                'tournament'       => $tournament,
                                'player'           => $player,
                                'tournament_entry' => $tournament_entry,
                            ),
                            'entry'
                        );
                    } else {
                        $msg   = $this->player_not_found;
                    }
                } else {
                    $msg   = $this->tournament_not_found;
                }
            } else {
                $msg   = __( 'No tournament name specified', 'racketmanager' );
            }
        }
        return $this->return_error( $msg );
    }
    /**
     * Function to display Cup Entry Page
     *
     * @param object $competition competition object.
     * @param string $season season.
     * @param array $competition_season competition season.
     * @param object $club club object.
     * @param string $template template name.
     * @return string the content
     */
    private function show_cup_entry( object $competition, string $season, array $competition_season, object $club, string $template ): string {
        $valid = true;
        $msg   = null;
        if ( ! $club ) {
            $valid = false;
            $msg   = $this->club_not_found;
        }
        if ( ! $competition ) {
            $valid = false;
            $msg   = __( 'Cup not found', 'racketmanager' );
        }
        if ( ! $season ) {
            $valid = false;
            $msg   = $this->season_not_found;
        }
        if ( $valid ) {
            $events = $competition->get_events();
            foreach ( $events as $i => $event ) {
                $event->status = '';
                $events[ $i ]  = $event;
                $event         = get_event( $event );
                $event->status = '';
                $event_teams   = $event->get_teams(
                    array(
                        'season' => $season,
                        'club'   => $club->id,
                    )
                );
                foreach ( $event_teams as $event_team ) {
                    $event_team->team_info     = $event->get_team_info( $event_team->team_id );
                    $event->team               = $event_team;
                    $event->status             = 'checked';
                    $club->entry[ $event->id ] = $event;
                }
            }
            $ladies_teams = $this->team_service->get_teams_for_club( $club->id, 'WD' );
            $mens_teams   = $this->team_service->get_teams_for_club( $club->id, 'MD' );
            $mixed_teams  = $this->team_service->get_teams_for_club( $club->id, 'XD' );
            $match_days   = Util_Lookup::get_match_days();

            $filename = ( ! empty( $template ) ) ? 'entry-cup-' . $template : 'entry-cup';
            return $this->load_template(
                $filename,
                array(
                    'club'               => $club,
                    'events'             => $events,
                    'ladies_teams'       => $ladies_teams,
                    'mens_teams'         => $mens_teams,
                    'mixed_teams'        => $mixed_teams,
                    'season'             => $season,
                    'competition'        => $competition,
                    'competition_season' => $competition_season,
                    'match_days'         => $match_days,
                ),
                'entry'
            );
        } else {
            return $this->return_error( $msg );
        }
    }
    /**
     * Function to display league Entry Page
     *
     * @param object $competition competition.
     * @param string $season season.
     * @param array  $competition_season competition season.
     * @param object $club club.
     * @param string $template template name.
     * @return string content
     */
    private function show_league_entry( object $competition, string $season, array $competition_season, object $club, string $template ): string {
        $valid = true;
        $msg   = null;
        if ( ! $club ) {
            $valid = false;
            $msg   = $this->club_not_found;
        }
        if ( ! $competition ) {
            $valid = false;
            $msg   = $this->league_not_found;
        }
        if ( ! $season ) {
            $valid = false;
            $msg   = $this->season_not_found;
        }
        if ( $valid ) {
            $events = $competition->get_events();
            foreach ( $events as $i => $event ) {
                $event         = get_event( $event );
                $event->status = '';
                $event_teams   = $event->get_teams(
                    array(
                        'season' => $season,
                        'club'   => $club->id,
                    )
                );
                foreach ( $event_teams as $c => $event_team ) {
                    $event_team->team_info = $event->get_team_info( $event_team->team_id );
                    if ( '0' === $event_team->profile || '1' === $event_team->profile || '2' === $event_team->profile ) {
                        $event_team->status = 'checked';
                        $event->status      = 'checked';
                    } else {
                        $event_team->status = '';
                    }
                    $event_teams[ $c ] = $event_team;
                }
                $event->event_teams = $event_teams;
                $event_type         = $event->type;
                if ( 'LD' === $event->type ) {
                    $event_type = 'XD';
                }
                $event->teams = $this->team_service->get_teams_for_club( $club->get_id(), $event_type );
                $key = 0;
                foreach ( $event->teams as $team ) {
                    $found = in_array( $team->id, array_column( $event->event_teams, 'team_id' ) );
                    if ( false !== $found ) {
                        unset( $event->teams[ $key ] );
                    } else {
                        $event_team            = new stdClass();
                        $event_team->team_id   = $team->id;
                        $event_team->name      = $team->title;
                        $event_team->league_id = 0;
                        $event_team->status    = null;
                        $event->event_teams[]  = $event_team;
                    }
                    ++$key;
                }
                $event_team            = new stdClass();
                $event_team->team_id   = 0;
                $event_team->name      = __( 'New team', 'racketmanager' );
                $event_team->league_id = 0;
                $event_team->status    = null;
                $event->event_teams[]  = $event_team;
                $events[ $i ]          = $event;
                if ( ! empty( $event->status ) ) {
                    $club->entry[ $event->id ] = true;
                }
            }
            $filename = ( ! empty( $template ) ) ? 'entry-league-' . $template : 'entry-league';
            return $this->load_template(
                $filename,
                array(
                    'club'               => $club,
                    'competition'        => $competition,
                    'events'             => $events,
                    'season'             => $season,
                    'competition_season' => $competition_season,
                ),
                'entry'
            );
        } else {
            return $this->return_error( $msg );
        }
    }
    /**
     * Function to display Tournament Entry Page
     *
     * @param object $tournament tournament object.
     * @param object|null $player player object.
     * @param string|null $template template name.
     * @return string content
     */
    private function show_tournament_entry( object $tournament, ?object $player = null, ?string $template = null ): string {
        global $racketmanager;
        if ( ! $tournament ) {
            return $this->return_error( $this->tournament_not_found );
        }
        $player->firstname = get_user_meta( $player->ID, 'first_name', true );
        $player->surname   = get_user_meta( $player->ID, 'last_name', true );
        $player->contactno = get_user_meta( $player->ID, 'contactno', true );
        $player->gender    = get_user_meta( $player->ID, 'gender', true );
        if ( empty( $player->year_of_birth ) ) {
            $player_age = 0;
        } else {
            $player_age = substr( $tournament->date, 0, 4 ) - intval( $player->year_of_birth );
        }
        $tournament->fees     = $tournament->get_fees();
        $args['player']       = $player->id;
        $args['status']       = 'paid';
        $tournament->payments = $tournament->get_payments( $args );

        $events = $tournament->get_events();
        $c      = 0;
        foreach ( $events as $event ) {
            $event       = get_event( $event );
            $entry_valid = false;
            if ( 'M' === $player->gender ) {
                if ( ! str_starts_with( $event->type, 'W' ) && ! str_starts_with( $event->type, 'G' ) ) {
                    $entry_valid = true;
                }
            } elseif ( 'F' === $player->gender ) {
                if ( ! str_starts_with( $event->type, 'M' ) && ! str_starts_with( $event->type, 'B' ) ) {
                    $entry_valid = true;
                }
            }
            if ( $entry_valid ) {
                if ( empty( $event->age_limit ) || 'open' === $event->age_limit ) {
                    $entry_valid = true;
                } elseif ( empty( $player_age ) ) {
                    $entry_valid = false;
                } elseif ( $event->age_limit >= 30 ) {
                    $age_limit = $event->age_limit;
                    if ( 'F' === $player->gender && ! empty( $event->age_offset ) ) {
                        $age_limit = $event->age_limit - $event->age_offset;
                    }
                    if ( $player_age < $age_limit ) {
                        $entry_valid = false;
                    } else {
                        $entry_valid = true;
                    }
                } elseif ( $player_age > $event->age_limit ) {
                    $entry_valid = false;
                } else {
                    $entry_valid = true;
                }
            }
            if ( $entry_valid ) {
                $player_entry = new stdClass();
                $teams        = $event->get_teams(
                    array(
                        'player' => $player->ID,
                        'season' => $tournament->season,
                    )
                );
                if ( $teams ) {
                    $team                  = $teams[0];
                    $player_entry->team_id = $team->id;
                    $p                     = 1;
                    foreach ( $team->players as $team_player ) {
                        if ( $team_player->id !== $player->ID ) {
                            $player_entry->partner    = $team_player;
                            $player_entry->partner_id = $team_player->id;
                            break;
                        }
                        ++$p;
                    }
                    $player_entry->event         = $event->name;
                    $player->entry[ $event->id ] = $player_entry;
                }
            } else {
                unset( $events[ $c ] );
            }
            ++$c;
        }

        $club_memberships = $racketmanager->get_club_players(
            array(
                'player' => $player->ID,
                'active' => true,
            )
        );
        $search           = $tournament->id . '_' . $player->id;
        $tournament_entry = get_tournament_entry( $search, 'key' );
        if ( $tournament_entry ) {
            $player->tournament_entry = $tournament_entry;
        }

        $filename = ( ! empty( $template ) ) ? 'entry-tournament-' . $template : 'entry-tournament';

        return $this->load_template(
            $filename,
            array(
                'tournament'       => $tournament,
                'events'           => $events,
                'player'           => $player,
                'club_memberships' => $club_memberships,
                'season'           => $tournament->season,
            ),
            'entry'
        );
    }
    /**
     * Function to display event dropdown
     *
     * [dropdown id=ID team_id=X template=X]
     *
     * @param array $atts shortcode attributes.
     *
     * @return string - the content
     */
    public function show_dropdown( array $atts ): string {
        $args     = shortcode_atts(
            array(
                'id'       => 0,
                'template' => '',
            ),
            $atts
        );
        $competition_id = $args['id'];
        $template       = $args['template'];
        try {
            $events      = $this->competition_service->get_events_for_competition( $competition_id );
        } catch ( Competition_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
        $filename = ! empty( $template ) ? 'dropdown-' . $template : 'dropdown';
        return $this->load_template(
            $filename,
            array(
                'events' => $events,
            ),
            'competition'
        );
    }
}
