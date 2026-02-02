<?php /** @noinspection PhpMissingParentConstructorInspection */

/**
 * Racketmanager_Shortcodes_Tournament API: Shortcodes_Tournament class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Shortcodes/Competition
 */

namespace Racketmanager\Public;

use JetBrains\PhpStorm\NoReturn;
use Racketmanager\Util\Util;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_match;
use function Racketmanager\get_player;
use function Racketmanager\get_player_id;
use function Racketmanager\get_tab;
use function Racketmanager\get_tournament;
use function Racketmanager\get_tournament_entry;
use function Racketmanager\seo_url;
use function Racketmanager\un_seo_url;

/**
 * Class to implement the Racketmanager_Shortcodes_Tournament object
 */
class Shortcodes_Tournament extends Shortcodes {
    private string $base_tournaments;
    /**
     * Initialize shortcodes
     */
    public function __construct( $plugin_instance ) {
        parent::__construct( $plugin_instance );
        $this->base_tournaments = '/tournaments/';
    }
    /**
     * Show tournament function
     *
     * @param array $atts function attributes.
     * @return string
     */
    public function show_tournament( array $atts ): string {
        global $racketmanager, $wp;
        $args        = shortcode_atts(
            array(
                'tournament' => false,
                'template'   => '',
            ),
            $atts
        );
        $tournament  = $args['tournament'];
        $template    = $args['template'];
        if ( ! $tournament ) {
            if ( ! empty( $_GET['tournament'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $tournament = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['tournament'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            } elseif ( isset( $wp->query_vars['tournament'] ) ) {
                $tournament = get_query_var( 'tournament' );
            }
            $tournament = un_seo_url( $tournament );
        }
        if ( ! $tournament ) {
            $active_tournaments = $racketmanager->get_tournaments( array( 'active' => true ) );
            if ( $active_tournaments ) {
                $tournament = $active_tournaments[0];
                $new_url    = '/tournament/' . seo_url( $tournament->name ) . '/';
            } else {
                $new_url = $this->base_tournaments;
            }
            echo '<script>location.href = "' . esc_url( $new_url ) . '"</script>';
            exit;
        } else {
            $tournament = get_tournament( $tournament, 'name' );
        }
        if ( ! $tournament ) {
            $msg = $this->tournament_not_found;
            return $this->return_error( $msg );
        }
        $tournaments = $racketmanager->get_tournaments(
            array(
                'age_group' => $tournament->competition->age_group,
                'orderby'   => array(
                    'season'         => 'DESC',
                    'competition_id' => 'DESC',
                ),
            )
        );
        $wp->set_query_var( 'season', $tournament->season );
        $tab      = get_tab();
        $filename = ( ! empty( $template ) ) ? 'tournament-' . $template : 'tournament';

        return $this->load_template(
            $filename,
            array(
                'tournament'  => $tournament,
                'tournaments' => $tournaments,
                'tab'         => $tab,
            )
        );
    }
    /**
     * Show tournament overview function
     *
     * @param array $atts function attributes.
     * @return string
     */
    public function show_tournament_overview( array $atts ): string {
        $args          = shortcode_atts(
            array(
                'id'       => false,
                'template' => '',
            ),
            $atts
        );
        $tournament_id = $args['id'];
        $template      = $args['template'];
        $tournament    = get_tournament( $tournament_id );
        if ( ! $tournament ) {
            $msg = $this->tournament_not_found;
            return $this->return_error( $msg );
        }
        $tournament->events      = $tournament->get_events();
        $tournament->num_entries = $tournament->get_entries( array( 'count' => true ) );

        $filename = ( ! empty( $template ) ) ? 'overview-' . $template : 'overview';

        return $this->load_template(
            $filename,
            array(
                'tournament' => $tournament,
            ),
            'tournament'
        );
    }
    /**
     * Show event function
     *
     * @param array $atts function attributes.
     * @return string
     */
    public function show_events( array $atts ): string {
        global $wp;
        $args               = shortcode_atts(
            array(
                'id'       => false,
                'events'   => false,
                'template' => '',
            ),
            $atts
        );
        $tournament_id      = $args['id'];
        $event_id           = $args['events'];
        $template           = $args['template'];
        $event              = null;
        $tournament         = get_tournament( $tournament_id );
        $tournament->events = $tournament->get_events();
        if ( ! $event_id ) {
            if ( ! empty( $_GET['event'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $event_id = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['event'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            } elseif ( isset( $wp->query_vars['event'] ) ) {
                $event_id = get_query_var( 'event' );
            }
            $event_id = str_replace( '-', ' ', $event_id );
        }
        if ( $event_id ) {
            if ( is_numeric( $event_id ) ) {
                $event = get_event( $event_id );
            } else {
                $event = $tournament->get_events( $event_id );
                if ( is_array( $event ) ) {
                    $msg = __( 'Event not found for tournament', 'racketmanager' );
                    return $this->return_error( $msg );
                }
            }
            if ( $event ) {
                $primary_league_id = $event->primary_league;
                if ( $primary_league_id ) {
                    $league = get_league( (string) $primary_league_id );
                    if ( $league ) {
                        $event->num_seeds = $league->championship->num_seeds ?? 0;
                    }
                }
                $teams = $event->get_teams(
                    array(
                        'season'  => $tournament->season,
                        'league'  => $event->primary_league,
                        'orderby' => array(
                            'rank' => 'ASC',
                        ),
                    )
                );
                if ( $teams ) {
                    $event->teams = $teams;
                } else {
                    $event->teams = array();
                }
            }
        }
        $tab      = 'events';
        $filename = ( ! empty( $template ) ) ? 'events-' . $template : 'events';

        return $this->load_template(
            $filename,
            array(
                'tournament' => $tournament,
                'event'      => $event,
                'tab'        => $tab,
            ),
            'tournament'
        );
    }
    /**
     * Show draw function
     *
     * @param array $atts function attributes.
     * @return string
     */
    public function show_draws( array $atts ): string {
        global $racketmanager, $league, $wp;
        $args          = shortcode_atts(
            array(
                'id'       => false,
                'draws'    => false,
                'template' => '',
            ),
            $atts
        );
        $tournament_id = $args['id'];
        $draw_id       = $args['draws'];
        $template      = $args['template'];
        $draw          = null;
        $tournament    = get_tournament( $tournament_id );
        if ( ! $draw_id ) {
            if ( ! empty( $_GET['draw'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $draw_id = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['draw'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            } elseif ( isset( $wp->query_vars['draw'] ) ) {
                $draw_id = get_query_var( 'draw' );
            }
            $draw_id = str_replace( '-', ' ', $draw_id );
        }
        if ( $draw_id ) {
            if ( is_numeric( $draw_id ) ) {
                $draw = get_event( $draw_id );
            } else {
                $draw = $tournament->get_events( $draw_id );
                if ( is_array( $draw ) ) {
                    $msg = __( 'Draw not found for tournament', 'racketmanager' );
                    return $this->return_error( $msg );
                }
            }
            if ( $draw ) {
                $draw->leagues = $this->get_draw( $draw, $tournament->season );
            }
            $matches = $racketmanager->get_matches(
                array(
                    'season'   => $tournament->season,
                    'event_id' => $draw->id,
                    'latest'   => true,
                    'orderby'  => array(
                        'date' => 'ASC',
                    ),
                )
            );
        } else {
            $matches = array();
            $events  = $tournament->get_events();
            $e       = 0;
            foreach ( $events as $event ) {
                if ( ! empty( $event->primary_league ) ) {
                    $league = get_league( $event->primary_league );
                } else {
                    $leagues = $event->get_leagues();
                    $league  = get_league( $leagues[0] );
                }
                $event->draw_size = $league->championship->num_teams_first_round;
                $events[ $e ]     = $event;
                ++$e;
            }
            $tournament->events = $events;
        }
        $tab      = 'draws';
        $filename = ( ! empty( $template ) ) ? 'draws-' . $template : 'draws';

        return $this->load_template(
            $filename,
            array(
                'tournament' => $tournament,
                'draw'       => $draw,
                'matches'    => $matches,
                'tab'        => $tab,
            ),
            'tournament'
        );
    }
    /**
     * Show tournament_players function
     *
     * @param array $atts function attributes.
     * @return string
     */
    public function show_tournament_players(array $atts ): string {
        $args          = shortcode_atts(
            array(
                'id'       => false,
                'players'  => false,
                'template' => '',
            ),
            $atts
        );
        $tournament_id = $args['id'];
        $player_id     = $args['players'];
        $template      = $args['template'];
        $player        = null;
        $tournament    = get_tournament( $tournament_id );
        if ( $tournament ) {
            if ( ! $player_id ) {
                $player_id = get_player_id();
            }
            if ( $player_id ) {
                $player = $this->get_player_info( $player_id, $tournament );
                if ( is_string( $player ) ) {
                    return $this->return_error( $player );
                }
            } else {
                $players             = $tournament->get_entries();
                $tournament->players = Util::get_players_list( $players );
            }
            $filename = ( ! empty( $template ) ) ? 'players-' . $template : 'players';
            return $this->load_template(
                $filename,
                array(
                    'tournament'        => $tournament,
                    'tournament_player' => $player,
                ),
                'tournament'
            );
        } else {
            $msg = $this->tournament_not_found;
            return $this->return_error( $msg );
        }
    }

    /**
     * Get tournament player information function
     *
     * @param int|string $player_id
     * @param object $tournament tournament object.
     *
     * @return object|string
     */
    public function get_player_info( int|string $player_id, object $tournament ): object|string {
        global $racketmanager;
        if ( is_numeric( $player_id ) ) {
            $player = get_player( $player_id ); // get player by name.

        } else {
            $player = get_player( $player_id, 'name' ); // get player by name.
        }
        if ( ! $player ) {
            return $this->player_not_found;
        }
        $key = $tournament->id . '_' . $player->id;
        $tournament_entry = get_tournament_entry( $key, 'key' );
        if ( $tournament_entry && $tournament_entry->club ) {
            $player->club = $tournament_entry->club;
        }
        $tournament->events = $tournament->get_events();
        foreach ( $tournament->events as $event ) {
            $event = get_event( $event );
            $teams = $event->get_teams(
                array(
                    'player' => $player->id,
                    'season' => $tournament->season,
                )
            );
            if ( $teams ) {
                $team = $teams[0];
                foreach ( $team->players as $team_player ) {
                    if ( $team_player->display_name !== $player->display_name ) {
                        $team->partner = $team_player;
                    }
                }
                $team->event     = $event->name;
                $team->event_id  = $event->id;
                $player->teams[] = $team;
            }
        }
        if ( ! empty( $team ) && empty( $player->club ) ) {
            $player->club      = $team->club;
        }
        $tournament->matches = $racketmanager->get_matches(
            array(
                'season'        => $tournament->season,
                'tournament_id' => $tournament->id,
                'player'        => $player->id,
                'orderby'       => array(
                    'date'      => 'ASC',
                    'event_id'  => 'ASC',
                    'league_id' => 'DESC',
                ),
            )
        );
        $opponents           = array( 'home', 'away' );
        $opponents_pt        = array( 'player1', 'player2' );
        foreach ( $tournament->matches as $match ) {
            if ( ! empty( $match->winner_id ) ) {
                $match_type         = strtolower( substr( $match->league->type, 1, 1 ) );
                $winner             = null;
                $loser              = null;
                $player_ref         = null;
                $player_team        = null;
                $player_team_status = null;
                foreach ( $opponents as $opponent ) {
                    if ( $match->winner_id === $match->teams[ $opponent ]->id ) {
                        $winner = $opponent;
                    }
                    if ( $match->loser_id === $match->teams[ $opponent ]->id ) {
                        $loser = $opponent;
                    }
                    if ( array_search( $player->display_name, $match->teams[ $opponent ]->player, true ) ) {
                        $player_team = $opponent;
                        if ( 'home' === $player_team ) {
                            $player_ref = 'player1';
                        } else {
                            $player_ref = 'player2';
                        }
                    }
                }
                if ( $winner === $player_team ) {
                    $player_team_status = 'winner';
                } elseif ( $loser === $player_team ) {
                    $player_team_status = 'loser';
                }
                if ( ! isset( $player->statistics[ $match_type ]['played'][ $player_team_status ] ) ) {
                    $player->statistics[ $match_type ]['played'][ $player_team_status ] = 0;
                }
                ++$player->statistics[ $match_type ]['played'][ $player_team_status ];
                if ( $match->is_walkover && 'winner' === $player_team_status ) {
                    if ( ! isset( $player->statistics[ $match_type ]['walkover'] ) ) {
                        $player->statistics[ $match_type ]['walkover'] = 0;
                    }
                    ++$player->statistics[ $match_type ]['walkover'];
                }
                $sets = ! empty( $match->custom['sets'] ) ? $match->custom['sets'] : array();
                foreach ( $sets as $set ) {
                    if ( isset( $set['player1'] ) && '' !== $set['player1'] && isset( $set['player2'] ) && '' !== $set['player2'] ) {
                        if ( $set['player1'] > $set['player2'] ) {
                            if ( 'player1' === $player_ref ) {
                                $stat_ref = 'winner';
                            } else {
                                $stat_ref = 'loser';
                            }
                        } elseif ( 'player1' === $player_ref ) {
                            $stat_ref = 'loser';
                        } else {
                            $stat_ref = 'winner';
                        }
                        if ( ! isset( $player->statistics[ $match_type ]['sets'][ $stat_ref ] ) ) {
                            $player->statistics[ $match_type ]['sets'][ $stat_ref ] = 0;
                        }
                        ++$player->statistics[ $match_type ]['sets'][ $stat_ref ];
                        foreach ( $opponents_pt as $opponent ) {
                            if ( $player_ref === $opponent ) {
                                if ( ! isset( $player->statistics[ $match_type ]['games']['winner'] ) ) {
                                    $player->statistics[ $match_type ]['games']['winner'] = 0;
                                }
                                $player->statistics[ $match_type ]['games']['winner'] += $set[ $opponent ];
                            } else {
                                if ( ! isset( $player->statistics[ $match_type ]['games']['loser'] ) ) {
                                    $player->statistics[ $match_type ]['games']['loser'] = 0;
                                }
                                $player->statistics[ $match_type ]['games']['loser'] += $set[ $opponent ];
                            }
                        }
                    }
                }
            }
        }
        $player->statistics = $player->get_stats( $player->statistics );
        return $player;
    }
    /**
     * Show tournament winners function
     *
     * @param array $atts function attributes.
     * @return string
     */
    public function show_tournament_winners(array $atts ): string {
        global $racketmanager;
        $args          = shortcode_atts(
            array(
                'id'       => false,
                'template' => '',
            ),
            $atts
        );
        $tournament_id = $args['id'];
        $template      = $args['template'];
        $tournament    = get_tournament( $tournament_id );
        if ( ! $tournament ) {
            $msg = $this->tournament_not_found;
            return $this->return_error( $msg );
        }
        $winners = $racketmanager->get_winners( $tournament->season, $tournament->competition_id, 'tournament', true );

        $filename = ( ! empty( $template ) ) ? 'winners-' . $template : 'winners';

        return $this->load_template(
            $filename,
            array(
                'tournament' => $tournament,
                'winners'    => $winners,
            ),
            'tournament'
        );
    }
    /**
     * Show tournament_players function
     *
     * @param array $atts function attributes.
     * @return string
     */
    public function show_tournament_matches(array $atts ): string {
        global $racketmanager, $wp;
        $args          = shortcode_atts(
            array(
                'id'         => false,
                'match_date' => false,
                'template'   => '',
            ),
            $atts
        );
        $tournament_id = $args['id'];
        $match_date    = $args['match_date'];
        $template      = $args['template'];
        $tournament    = get_tournament( $tournament_id );
        if ( ! $tournament ) {
            $msg = $this->tournament_not_found;
            return $this->return_error( $msg );
        }
        $order_of_play = array();
        if ( ! $match_date ) {
            if ( ! empty( $_GET['match_date'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $match_date = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['match_date'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            } elseif ( isset( $wp->query_vars['match_date'] ) ) {
                $match_date = get_query_var( 'match_date' );
            }
        }
        $tournament_matches = $tournament->get_match_dates();
        $match_dates        = array();
        foreach ( $tournament_matches as $match ) {
            $key                 = substr( $match->date, 0, 10 );
            $match_dates[ $key ] = substr( $match->date, 0, 10 );
        }
        $tournament->match_dates = $match_dates;

        if ( empty( $match_date ) && ! empty( $tournament->match_dates ) ) {
            $match_date = end( $tournament->match_dates );
        }
        if ( $match_date ) {
            $matches = $racketmanager->get_matches(
                array(
                    'season'         => $tournament->season,
                    'competition_id' => $tournament->competition_id,
                    'match_date'     => $match_date,
                    'final'          => 'all',
                    'orderby'        => array(
                        'date'      => 'ASC',
                        'location'  => 'ASC',
                    ),
                )
            );
            $tournament_matches = array();
            foreach ( $matches as $match ) {
                $key = substr( $match->date, 11, 5 );
                if ( '00:00' === $key) {
                    $key = '99:99';
                }
                if ( false === array_key_exists( $key, $tournament_matches ) ) {
                    $tournament_matches[ $key ] = array();
                }
                $tournament_matches[ $key ][] = $match;
            }
        }
        ksort( $tournament_matches );
        $tab      = 'matches';
        $filename = ( ! empty( $template ) ) ? 'matches-' . $template : 'matches';

        return $this->load_template(
            $filename,
            array(
                'tournament'         => $tournament,
                'order_of_play'      => $order_of_play,
                'tournament_matches' => $tournament_matches,
                'current_match_date' => $match_date,
                'tab'                => $tab,
            ),
            'tournament'
        );
    }
    /**
     * Display single tournament match
     *
     * [match id="1" template="name"]
     *
     * - id is the ID of the match to display
     * - template is the template used for displaying. Replace name appropriately. Templates must be named "match-template.php" (optional)
     *
     * @param array $atts shortcode attributes.
     * @return string
     */
    public function show_tournament_match( array $atts ): string {
        global $wp;
        $args       = shortcode_atts(
            array(
                'tournament' => false,
                'match_id'   => 0,
                'message'    => null,
                'template'   => '',
            ),
            $atts
        );
        $tournament = $args['tournament'];
        $match_id   = $args['match_id'];
        $message    = $args['message'];
        $template   = $args['template'];

        if ( ! $tournament ) {
            if ( ! empty( $_GET['tournament'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $tournament = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['tournament'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            } elseif ( isset( $wp->query_vars['tournament'] ) ) {
                $tournament = get_query_var( 'tournament' );
            }
            $tournament = un_seo_url( $tournament );
        }
        if ( ! $tournament ) {
            $msg = $this->tournament_not_found;
            return $this->return_error( $msg );
        }
        $tournament = get_tournament( $tournament, 'name' );
        // Get Match ID from shortcode or $_GET.
        if ( ! $match_id ) {
            $match_id = intval( get_query_var( 'match_id' ) );
        }
        if ( $match_id ) {
            $match = get_match( $match_id );
            if ( $match ) {
                $filename = ! empty( $template ) ? 'match-tournament' . $template : 'match-tournament';
                return $this->load_template(
                    $filename,
                    array(
                        'tournament'        => $tournament,
                        'match'             => $match,
                        'is_update_allowed' => $match->is_update_allowed(),
                        'message'           => $message,
                    )
                );
            } else {
                $msg = $this->match_not_found;
            }
        } else {
            $msg = $this->match_not_found;
        }
        return $this->return_error( $msg );
    }
    /**
     * Show latest tournament function
     *
     * @param array $atts function attributes.
     * @return string
     */
    #[NoReturn]
    public function show_latest_tournament( array $atts ): string {
        global $racketmanager, $wp;
        $args      = shortcode_atts(
            array(
                'age_group'  => false,
                'template'   => '',
            ),
            $atts
        );
        $age_group = $args['age_group'];
        if ( isset( $wp->query_vars['age_group'] ) ) {
            $age_group = get_query_var( 'age_group' );
        }
        $tournament_args['active']    = true;
        $tournament_args['age_group'] = $age_group;
        $active_tournaments           = $racketmanager->get_tournaments( $tournament_args );
        if ( $active_tournaments ) {
            $tournament = $active_tournaments[0];
            $new_url    = '/tournament/' . seo_url( $tournament->name ) . '/';
        } elseif ( $age_group ) {
            $new_url = $this->base_tournaments . $age_group . '/';
        } else {
            $new_url = $this->base_tournaments;
        }
        echo '<script>location.href = "' . esc_url( $new_url ) . '"</script>';
        exit;
    }
    /**
     * Function to display Tournament withdrawal modal
     *
     *    [tournament-withdrawal id=ID player_id=x modal=x template=X]
     *
     * @param array $atts shortcode attributes.
     * @return string the content
     */
    public function show_tournament_withdrawal_modal( array $atts ): string {
        $args           = shortcode_atts(
            array(
                'id'        => '',
                'modal'     => null,
                'player_id' => null,
            ),
            $atts
        );
        $tournament_id  = $args['id'];
        $modal          = $args['modal'];
        $player_id      = $args['player_id'];
        $tournament     = null;
        $player         = null;
        $events_entered = null;
        $msg            = null;
        $valid          = true;
        if ( $tournament_id ) {
            $tournament = get_tournament( $tournament_id );
            if ( $tournament ) {
                if ( $player_id ) {
                    $player         = get_player( $player_id );
                    $events_entered = $tournament->get_players(
                        array(
                            'count' => true,
                            'player' => $player_id,
                        )
                    );
                    if ( ! $events_entered ) {
                        $msg = __( 'You are not currently entered into any event.', 'racketmanager' );
                    }
                } else {
                    $valid = false;
                    $msg   = __( 'Player id not found', 'racketmanager' );
                }
            } else {
                $valid = false;
                $msg   = $this->tournament_not_found;
            }
        } else {
            $valid = false;
            $msg   = __( 'Tournament id not found', 'racketmanager' );
        }
        if ( $valid ) {
            $filename = 'withdrawal-modal';
            return $this->load_template(
                $filename,
                array(
                    'tournament'     => $tournament,
                    'player'         => $player,
                    'modal'          => $modal,
                    'msg'            => $msg,
                    'events_entered' => $events_entered,
                )
                ,'tournament'
            );
        } else {
            return $this->return_error( $msg, 'modal' );
        }

    }
}
