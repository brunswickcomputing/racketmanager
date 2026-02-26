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
use Racketmanager\Domain\Tournament;
use Racketmanager\Exceptions\Event_Not_Found_Exception;
use Racketmanager\Exceptions\Fixture_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Entry_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;

/**
 * Class to implement the Racketmanager_Shortcodes_Tournament object
 */
class Shortcodes_Tournament extends Shortcodes {
    private string $base_tournaments;

    /**
     * Initialise shortcodes
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
        global $wp;
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
            $tournament = Util::un_seo_url( $tournament );
        }
        if ( $tournament ) {
            try {
                $tournament_details = $this->tournament_service->get_tournament_with_details( $tournament );
            } catch ( Tournament_Not_Found_Exception $e ) {
                return $this->return_error( $e->getMessage() );
            }
        } else {
            $this->show_latest_tournament();
        }
        if ( ! $tournament_details ) {
            $msg = $this->tournament_not_found;
            return $this->return_error( $msg );
        }
        $tournament_args['age_group'] = $tournament_details->competition->age_group;
        $tournament_args['orderby']   = array(
            'season'         => 'DESC',
            'competition_id' => 'DESC',
        );
        $tournaments = $this->tournament_service->get_tournaments( $tournament_args );
        $wp->set_query_var( 'season', $tournament_details->tournament->season );
        $tab      = Util_Lookup::get_tab();
        $filename = ( ! empty( $template ) ) ? 'tournament-' . $template : 'tournament';

        return $this->load_template(
            $filename,
            array(
                'tournament_details'  => $tournament_details,
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
        try {
            $tournament = $this->tournament_service->get_tournament_overview( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }

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
    public function show_tournament_events( array $atts ): string {
        global $wp;
        $args          = shortcode_atts(
            array(
                'id'       => false,
                'events'   => false,
            ),
            $atts
        );
        $tournament_id = $args['id'];
        $event_id      = $args['events'];
        try {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
        if ( ! $event_id ) {
            if ( ! empty( $_GET['event'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $event_id = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['event'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            } elseif ( isset( $wp->query_vars['event'] ) ) {
                $event_id = get_query_var( 'event' );
            }
            $event_id = str_replace( '-', ' ', $event_id );
        }
        if ( $event_id ) {
            return $this->show_tournament_event( $tournament, $event_id );
        }
        $events = $this->tournament_service->get_events_with_details_for_tournament( $tournament_id );
        return $this->load_template(
            'events',
            array(
                'tournament' => $tournament,
                'events'     => $events,
                'tab'        => 'events',
            ),
            'tournament'
        );
    }

    /**
     * Show individual event for a tournament
     *
     * @param Tournament $tournament
     * @param int|string|null $event_id
     *
     * @return string
     */
    public function show_tournament_event( Tournament $tournament, int|string|null $event_id ): string {
        try {
            $event = $this->tournament_service->get_event_details_for_tournament( $tournament, $event_id );
        } catch ( Tournament_Not_Found_Exception|Event_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
        return $this->load_template(
            'event',
            array(
                'tournament' => $tournament,
                'event'      => $event,
                'tab'        => 'events',
            ),
            'tournament'
        );
    }

    /**
     * Show draws for a tournament
     *
     * @param array $atts function attributes.
     * @return string
     */
    public function show_draws( array $atts ): string {
        global $wp;
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
        try {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
        if ( ! $draw_id ) {
            if ( ! empty( $_GET['draw'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $draw_id = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['draw'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            } elseif ( isset( $wp->query_vars['draw'] ) ) {
                $draw_id = get_query_var( 'draw' );
            }
            $draw_id = str_replace( '-', ' ', $draw_id );
        }
        if ( $draw_id ) {
            return $this->show_draw( $tournament, $draw_id );
        }
        $draws = $this->tournament_service->get_events_with_details_for_tournament( $tournament_id );

        return $this->load_template(
            'draws',
            array(
                'tournament' => $tournament,
                'draws'      => $draws,
                'tab'        => 'draws',
                ),
        'tournament'
        );
    }

    public function show_draw( Tournament $tournament, int|string|null $draw_id ): string {
        try {
            $draw = $this->tournament_service->get_draw_details_for_tournament( $tournament, $draw_id );
        } catch ( Event_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
        return $this->load_template(
            'draw',
            array(
                'tournament' => $tournament,
                'draw'       => $draw,
                'tab'        => 'draws',
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
            ),
            $atts
        );
        $tournament_id = $args['id'];
        $player_id     = $args['players'];
        try {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
        $player_id = Util_Lookup::get_player_id( $player_id );
        if ( $player_id ) {
            return $this->show_tournament_player( $tournament, $player_id );
        }
        $tournament_players = $this->tournament_service->get_players_for_tournament( $tournament->get_id() );
        return $this->load_template(
            'players',
            array(
                'tournament'         => $tournament,
                'tournament_players' => $tournament_players,
                'tab'                => 'players',
            ),
            'tournament'
        );
    }

    public function show_tournament_player( Tournament $tournament, int|string|null $player_id ): string {
        try {
            $tournament_player = $this->tournament_service->get_player_details_for_tournament( $tournament, $player_id );
        } catch ( Player_Not_Found_Exception|Tournament_Entry_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
        return $this->load_template(
            'player',
            array(
                'tournament'        => $tournament,
                'tournament_player' => $tournament_player,
                'tab'               => 'players',
            ),
            'tournament'
        );

    }

    /**
     * Show tournament winners function
     *
     * @param array $atts function attributes.
     * @return string
     */
    public function show_tournament_winners(array $atts ): string {
        $args          = shortcode_atts(
            array(
                'id'       => false,
                'template' => '',
            ),
            $atts
        );
        $tournament_id = $args['id'];
        $template      = $args['template'];
        try {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
        try {
            $tournament_winners = $this->tournament_service->get_winners_for_tournament( $tournament );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
        $filename = ( ! empty( $template ) ) ? 'winners-' . $template : 'winners';
        return $this->load_template(
            $filename,
            array(
                'tournament' => $tournament,
                'winners'    => $tournament_winners,
            ),
            'tournament'
        );
    }
    /**
     * Show matches for a tournament
     *
     * @param array $atts function attributes.
     * @return string
     */
    public function show_tournament_matches(array $atts ): string {
        global $wp;
        $args          = shortcode_atts(
            array(
                'id'         => false,
                'match_date' => false,
                'template'   => '',
            ),
            $atts
        );
        $tournament_id = $args['id'];
        $fixture_date  = $args['match_date'];
        $template      = $args['template'];
        try {
            $tournament = $this->tournament_service->get_tournament( $tournament_id );
        } catch ( Tournament_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
        $fixture_dates = $this->tournament_service->get_fixture_dates_for_tournament( $tournament );
        if ( ! $fixture_date ) {
            if ( ! empty( $_GET['match_date'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $fixture_date = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['match_date'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            } elseif ( isset( $wp->query_vars['match_date'] ) ) {
                $fixture_date = get_query_var( 'match_date' );
            }
        }

        if ( empty( $fixture_date ) && ! empty( $fixture_dates ) ) {
            $fixture_date = end( $fixture_dates );
        }
        $tournament_fixtures = $this->tournament_service->get_fixtures_by_date_for_tournament( $tournament, $fixture_date );
        $tab      = 'matches';
        $filename = ( ! empty( $template ) ) ? 'matches-' . $template : 'matches';

        return $this->load_template(
            $filename,
            array(
                'tournament'         => $tournament,
                'match_dates'        => $fixture_dates,
                'tournament_matches' => $tournament_fixtures,
                'current_match_date' => $fixture_date,
                'tab'                => $tab,
            ),
            'tournament'
        );
    }
    /**
     * Display a single tournament match
     *
     * [tournament-match ]
     *
     *
     * @return string
     */
    public function show_tournament_match(): string {
        global $wp;
        if ( ! empty( $_GET['tournament'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $tournament = htmlspecialchars( wp_strip_all_tags( wp_unslash( $_GET['tournament'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        } elseif ( isset( $wp->query_vars['tournament'] ) ) {
            $tournament = get_query_var( 'tournament' );
        } else {
            $tournament = null;
        }
        $tournament = Util::un_seo_url( $tournament );
        $fixture_id = intval( get_query_var( 'match_id' ) );
        try {
            $tournament_details = $this->tournament_service->get_tournament_with_details_by_name( $tournament );
            $tournament         = $tournament_details->tournament;
            $fixture            = $this->fixture_service->get_tournament_fixture_with_details( $fixture_id );
        } catch ( Tournament_Not_Found_Exception|Fixture_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage() );
        }
        $is_update_allowed = $this->fixture_service->is_update_allowed( $fixture_id );
        return $this->load_template(
            'match-tournament',
            array(
                'tournament'        => $tournament,
                'fixture_details'   => $fixture,
                'is_update_allowed' => $is_update_allowed,
            )
        );
    }
    /**
     * Show latest tournament function
     *
     * @param array $atts function attributes.
     * @return string
     */
    #[NoReturn]
    public function show_latest_tournament( array $atts = array() ): string {
        global $wp;
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
        $active_tournament = $this->tournament_service->get_active_tournament( $age_group );
        if ( $active_tournament ) {
            $new_url    = '/tournament/' . Util::seo_url( $active_tournament->get_name() ) . '/';
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
        $msg            = null;
        try {
            $tournament_entries = $this->tournament_service->get_tournament_event_entry_details_for_player( $player_id, $tournament_id );
        } catch ( Tournament_Not_Found_Exception|Player_Not_Found_Exception $e ) {
            return $this->return_error( $e->getMessage(), 'modal' );
        }
        if ( empty( $tournament_entries ) ) {
            $msg = __( 'You are not currently entered into any event.', 'racketmanager' );
        }
        $filename = 'withdrawal-modal';
        return $this->load_template(
            $filename,
            array(
                'tournament_id'  => $tournament_id,
                'player_id'      => $player_id,
                'modal'          => $modal,
                'msg'            => $msg,
                'events_entered' => $tournament_entries,
            )
            ,'tournament'
        );
    }
}
