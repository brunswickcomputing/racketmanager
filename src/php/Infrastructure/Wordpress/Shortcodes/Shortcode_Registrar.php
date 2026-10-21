<?php
/**
 * Shortcode Registrar
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Shortcodes
 */

declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

use Racketmanager\Services\Container\Simple_Container;

/**
 * Registers all WordPress shortcodes and shortcode-related hooks handled by containerized
 * shortcode adapters and controllers.
 */
class Shortcode_Registrar {

    /**
     * Shortcode tag registrations.
     * Format: [ tag, service_id, method ].
     */
    public const array SHORTCODE_DEFINITIONS = [
        [ 'dailymatches', 'matches_shortcode_adapter', 'show_daily_matches' ],
        [ 'latest_results', 'matches_shortcode_adapter', 'show_latest_results' ],
        [ 'players', 'general_shortcode_adapter', 'show_players' ],
        [ 'player', 'general_shortcode_adapter', 'show_player' ],
        [ 'favourites', 'general_shortcode_adapter', 'show_favourites' ],
        [ 'invoice', 'general_shortcode_adapter', 'show_invoice' ],
        [ 'purchase-order', 'general_shortcode_adapter', 'show_purchase_order' ],
        [ 'memberships', 'general_shortcode_adapter', 'show_memberships' ],
        [ 'search-players', 'general_shortcode_adapter', 'show_player_search' ],
        [ 'team-order', 'general_shortcode_adapter', 'show_team_order' ],
        [ 'show-alert', 'general_shortcode_adapter', 'show_alert' ],

        [ 'clubs', 'club_shortcode_adapter', 'show_clubs' ],
        [ 'club', 'club_shortcode_adapter', 'show_club' ],
        [ 'club-players', 'club_shortcode_adapter', 'show_club_players' ],
        [ 'club-roles', 'club_shortcode_adapter', 'show_club_roles' ],
        [ 'club-role-modal', 'club_shortcode_adapter', 'show_club_role_modal' ],
        [ 'club-competitions', 'club_shortcode_adapter', 'show_club_competitions' ],
        [ 'club-event', 'club_shortcode_adapter', 'show_club_event' ],
        [ 'club-team', 'club_shortcode_adapter', 'show_club_team' ],
        [ 'club-invoices', 'club_shortcode_adapter', 'show_club_invoices' ],
        [ 'team-edit', 'club_shortcode_adapter', 'show_team_edit_modal' ],

        [ 'competitions', 'competition_shortcode_adapter', 'show_competitions' ],
        [ 'competition', 'competition_shortcode_adapter', 'show_competition' ],
        [ 'competition-overview', 'competition_shortcode_adapter', 'show_competition_overview' ],
        [ 'competition-events', 'competition_shortcode_adapter', 'show_competition_events' ],
        [ 'competition-teams', 'competition_shortcode_adapter', 'show_competition_teams' ],
        [ 'competition-clubs', 'competition_shortcode_adapter', 'show_competition_clubs' ],
        [ 'competition-players', 'competition_shortcode_adapter', 'show_competition_players' ],
        [ 'competition-winners', 'competition_shortcode_adapter', 'show_competition_winners' ],
        [ 'competition-entry', 'competition_shortcode_adapter', 'show_competition_entry' ],
        [ 'competition-entry-payment', 'competition_shortcode_adapter', 'show_competition_entry_payment' ],
        [ 'competition-entry-payment-complete', 'competition_shortcode_adapter', 'show_competition_entry_payment_complete' ],
        [ 'event-dropdown', 'competition_shortcode_adapter', 'show_dropdown' ],

        [ 'match-notification', 'email_shortcode_adapter', 'show_match_notification' ],
        [ 'result-notification', 'email_shortcode_adapter', 'show_result_notification' ],
        [ 'result-notification-captain', 'email_shortcode_adapter', 'show_captain_result_notification' ],
        [ 'result-outstanding-notification', 'email_shortcode_adapter', 'show_result_outstanding_notification' ],
        [ 'club-player-notification', 'email_shortcode_adapter', 'show_club_player_notification' ],
        [ 'match_date_change_notification', 'email_shortcode_adapter', 'show_match_date_change_notification' ],
        [ 'withdrawn-team', 'email_shortcode_adapter', 'show_team_withdrawn' ],
        [ 'withdrawn-team-match', 'email_shortcode_adapter', 'show_withdrawn_team_match' ],
        [ 'event-constitution', 'email_shortcode_adapter', 'show_event_constitution' ],

        [ 'event', 'event_shortcode_adapter', 'show_event' ],
        [ 'event-standings', 'event_shortcode_adapter', 'show_event_standings' ],
        [ 'event-draw', 'event_shortcode_adapter', 'show_event_draw' ],
        [ 'event-matches', 'event_shortcode_adapter', 'show_event_matches' ],
        [ 'event-clubs', 'event_shortcode_adapter', 'show_event_clubs' ],
        [ 'event-teams', 'event_shortcode_adapter', 'show_event_teams' ],
        [ 'event-players', 'event_shortcode_adapter', 'show_event_players' ],
        [ 'event-partner', 'event_shortcode_adapter', 'show_event_partner' ],
        [ 'event-team-matches', 'event_shortcode_adapter', 'show_event_team_matches' ],
        [ 'team-order-players', 'event_shortcode_adapter', 'show_team_order_players' ],
        [ 'league-dropdown', 'event_shortcode_adapter', 'show_dropdown' ],

        [ 'championship', 'league_shortcode_adapter', 'show_championship' ],
        [ 'leaguearchive', 'league_shortcode_adapter', 'show_archive' ],
        [ 'standings', 'league_shortcode_adapter', 'show_standings' ],
        [ 'crosstable', 'league_shortcode_adapter', 'show_crosstable' ],
        [ 'matches', 'league_shortcode_adapter', 'show_matches' ],

        [ 'fixture', 'fixture_shortcode_controller', 'handle' ],
        [ 'match', 'fixture_shortcode_controller', 'handle' ],

        [ 'teams', 'league_shortcode_adapter', 'show_teams' ],
        [ 'league-players', 'league_shortcode_adapter', 'show_league_players' ],
        [ 'season-dropdown', 'league_shortcode_adapter', 'show_season_dropdown' ],
        [ 'match-dropdown', 'league_shortcode_adapter', 'show_match_dropdown' ],
        [ 'last-5', 'league_shortcode_adapter', 'show_last_5' ],

        [ 'custom-login-form', 'login_shortcode_adapter', 'render_login_form' ],
        [ 'login-form', 'login_shortcode_adapter', 'login_form' ],
        [ 'custom-password-lost-form', 'login_shortcode_adapter', 'render_password_lost_form' ],
        [ 'custom-password-reset-form', 'login_shortcode_adapter', 'render_password_reset_form' ],
        [ 'account-info', 'login_shortcode_adapter', 'generate_member_account_form' ],

        [ 'score', 'matches_shortcode_adapter', 'show_score' ],
        [ 'match-header', 'matches_shortcode_adapter', 'show_match_header' ],
        [ 'match-detail', 'matches_shortcode_adapter', 'show_match_detail' ],

        [ 'messages', 'message_shortcode_adapter', 'show_messages' ],
        [ 'show-message', 'message_shortcode_adapter', 'show_message' ],

        [ 'tournament', 'tournament_shortcode_adapter', 'show_tournament' ],
        [ 'tournament-overview', 'tournament_shortcode_adapter', 'show_tournament_overview' ],
        [ 'tournament-events', 'tournament_shortcode_adapter', 'show_tournament_events' ],
        [ 'tournament-draws', 'tournament_shortcode_adapter', 'show_draws' ],
        [ 'tournament-players', 'tournament_shortcode_adapter', 'show_tournament_players' ],
        [ 'tournament-winners', 'tournament_shortcode_adapter', 'show_tournament_winners' ],
        [ 'tournament-matches', 'tournament_shortcode_adapter', 'show_tournament_matches' ],
        [ 'tournament-match', 'tournament_shortcode_adapter', 'show_tournament_match' ],
        [ 'orderofplay', 'tournament_shortcode_adapter', 'show_order_of_play' ],
        [ 'latest-tournament', 'tournament_shortcode_adapter', 'show_latest_tournament' ],
        [ 'tournament-withdrawal', 'tournament_shortcode_adapter', 'show_tournament_withdrawal_modal' ],
    ];

    /**
     * Constructor.
     *
     * @param Simple_Container $container Service container.
     */
    public function __construct(
        private readonly Simple_Container $container
    ) {}

    /**
     * Register all shortcodes and associated hooks in WordPress.
     */
    public function register(): void {
        foreach ( self::SHORTCODE_DEFINITIONS as [ $tag, $service_id, $method ] ) {
            $service = $this->container->get( $service_id );
            add_shortcode( $tag, [ $service, $method ] );
        }

        if ( $this->container->has( 'login_shortcode_adapter' ) ) {
            $login = $this->container->get( 'login_shortcode_adapter' );
            add_action( 'init', [ $login, 'load_translations' ] );
        }
    }

}
