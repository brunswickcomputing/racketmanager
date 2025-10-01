<?php
/**
 * RacketManager-Admin API: RacketManager-admin-display class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin-Display
 */

namespace Racketmanager\admin;

use Racketmanager\Util;
use Racketmanager\validator\Validator;
use stdClass;
use function Racketmanager\get_club;
use function Racketmanager\get_competition;
use function Racketmanager\get_league;
use function Racketmanager\get_league_team;
use function Racketmanager\get_match;
use function Racketmanager\get_team;
use function Racketmanager\get_tournament;
use function Racketmanager\show_alert;

/**
 * RacketManager administration display functions
 * Class to implement RacketManager Administration display
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerAdmin
 */
class Admin_Display {
    /**
     * Message.
     *
     * @var string $message
     */
    public string $message;
    /**
     * Error.
     *
     * @var string|bool $error
     */
    public bool|string $error = false;
    public Admin_Cup $admin_cup;
    public Admin_Import $admin_import;
    public Admin_Finances $admin_finances;
    public Admin_Player $admin_players;
    protected ?string $invalid_permissions;
    protected ?string $invalid_security_token = 'invalid';
    protected ?string $no_permission;
    protected ?string $team_ranking_saved = 'Team ranking saved';
    public Admin_Club $admin_club;
    public Admin_Tournament $admin_tournament;
    public Admin_Event $admin_event;
    public Admin_Competition $admin_competition;
    public Admin_League $admin_league;
    public Admin_Season $admin_seasons;
    public Admin_Result $admin_results;
    public Admin_Options $admin_options;
    public Admin_Index $admin_index;
    protected ?string $no_updates = 'No updates';
    protected ?string $errors_found = 'Errors found';

    /**
     * Constructor
     */
    public function __construct() {
        add_action( 'init', array( &$this, 'load_translations' ) );
    }
    public function load_translations(): void {
        $this->invalid_permissions    = __( 'You do not have sufficient permissions to access this page', 'racketmanager' );
        $this->invalid_security_token = __( 'Security token invalid', 'racketmanager' );
        $this->no_permission          = __( 'You do not have permission to perform this task', 'racketmanager' );
        $this->team_ranking_saved     = __( 'Team ranking saved', 'racketmanager' );
        $this->no_updates             = __( 'No updates', 'racketmanager' );
        $this->errors_found           = __( 'Errors found', 'racketmanager' );
    }
    /**
     * ShowMenu() - show admin menu
     */
    public function display(): void {
        global $racketmanager;
        $options = $racketmanager->options;
        // Update Plugin Version.
        if ( RACKETMANAGER_VERSION !== $options['version'] ) {
            $options['version'] = RACKETMANAGER_VERSION;
            update_option( 'racketmanager', $options );
        }
        // Update database.
        if ( ! isset( $options['dbversion'] ) || RACKETMANAGER_DBVERSION !== $options['dbversion'] ) {
            require_once RACKETMANAGER_PATH . '/admin/upgrade.php';
            racketmanager_upgrade_page();
            return;
        }
        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        switch ( $page ) {
            case 'racketmanager-documentation':
            case 'racketmanager-doc':
                require_once RACKETMANAGER_PATH . '/admin/documentation.php';
                break;
            case 'racketmanager-leagues':
                $this->admin_league = new Admin_League();
                $this->admin_league->handle_display( $view );
                break;
            case 'racketmanager-cups':
                $this->admin_cup = new Admin_Cup();
                $this->admin_cup->handle_display( $view );
                break;
            case 'racketmanager-tournaments':
                $this->admin_tournament = new Admin_Tournament();
                $this->admin_tournament->handle_display( $view );
                break;
            case 'racketmanager-clubs':
                $this->admin_club = new Admin_Club();
                $this->admin_club->handle_display( $view );
                break;
            case 'racketmanager-results':
                $this->admin_results = new Admin_Result();
                $this->admin_results->handle_display( $view );
                break;
            case 'racketmanager-seasons':
                $this->admin_seasons = new Admin_Season();
                $this->admin_seasons->handle_display( $view );
                break;
            case 'racketmanager-players':
                $this->admin_players = new Admin_Player();
                $this->admin_players->handle_display( $view );
                break;
            case 'racketmanager-finances':
                $this->admin_finances = new Admin_Finances();
                $this->admin_finances->handle_display( $view );
                break;
            case 'racketmanager-settings':
                $this->admin_options = new Admin_Options();
                $this->admin_options->handle_display( $view );
                break;
            case 'racketmanager-import':
                $this->admin_import = new Admin_Import();
                $this->admin_import->display_import_page();
                break;
            case 'racketmanager':
            default:
                $this->admin_index = new Admin_Index();
                $this->admin_index->display_index_page();
                break;
        }
    }
    /**
     * Display contact page
     */
    protected function display_contact_page(): void {
        $title       = null;
        $season      = null;
        $object_type = null;
        $object      = null;
        $validator   = new Validator();
        $validator   = $validator->capability( 'edit_teams' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        if ( isset( $_POST['contactTeamPreview'] ) ) {
            $this->show_contact_preview();
        } else {
            if ( isset( $_GET['league_id'] ) ) {
                $league      = get_league( intval( $_GET['league_id'] ) );
                $object_type = 'league';
                $object_name = 'league_id';
                $object_id   = $league->id;
            } elseif ( isset( $_GET['competition_id'] ) ) {
                $competition = get_competition( intval( $_GET['competition_id'] ) );
                $object_type = 'competition';
                $object_name = 'competition_id';
                $object_id   = $competition->id;
            } elseif ( isset( $_GET['tournament_id'] ) ) {
                $tournament = get_tournament( intval( $_GET['tournament_id'] ) );
                $object_type = 'tournament';
                $object_name = 'tournament_id';
                $object_id   = $tournament->id;
            }
            if ( isset( $_GET['season'] ) ) {
                $season = sanitize_text_field( wp_unslash( $_GET['season'] ) );
            }
            $email_title   = '';
            $email_intro   = '';
            $email_close   = '';
            $email_body    = array();
            $email_message = '';
            $tab           = 'compose';
            require_once RACKETMANAGER_PATH . '/admin/includes/contact.php';
        }
    }

    /**
     * Function to show contact preview
     *
     * @return void
     */
    private function show_contact_preview(): void {
        global $racketmanager;
        $validator = new Validator();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_contact-teams' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        if ( isset( $_POST['league_id'] ) ) {
            $league      = get_league( intval( $_POST['league_id'] ) );
            $title       = $league->title;
            $object_type = 'league';
            $object      = $league;
            $object_name = 'league_id';
            $object_id   = $league->id;
        } elseif ( isset( $_POST['competition_id'] ) ) {
            $competition = get_competition( intval( $_POST['competition_id'] ) );
            $title       = $competition->name;
            $object_type = 'competition';
            $object      = $competition;
            $object_name = 'competition_id';
            $object_id   = $competition->id;
        } elseif ( isset( $_POST['tournament_id'] ) ) {
            $tournament = get_tournament( intval( $_POST['tournament_id'] ) );
            $title       = $tournament->name;
            $object_type = 'tournament';
            $object      = $tournament;
            $object_name = 'tournament_id';
            $object_id   = $tournament->id;
        }
        if ( isset( $_POST['season'] ) ) {
            $season = sanitize_text_field( wp_unslash( $_POST['season'] ) );
        }
        $tab           = 'preview';
        $email_title   = isset( $_POST['contactTitle'] ) ? sanitize_text_field( wp_unslash( $_POST['contactTitle'] ) ) : null;
        $email_intro   = isset( $_POST['contactIntro'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contactIntro'] ) ) : null;
        $email_body    = isset( $_POST['contactBody'] ) ? wp_unslash( $_POST['contactBody'] ) : null; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $email_close   = isset( $_POST['contactClose'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contactClose'] ) ) : null;
        $email_subject = $racketmanager->site_name . ' - ' . $title . ' ' . $season . ' - Important Message';
        $email_message = $racketmanager->shortcodes->load_template(
            'contact-teams',
            array(
                $object_type    => $object,
                'organisation'  => $racketmanager->site_name,
                'season'        => $season,
                'title_text'    => $email_title,
                'intro'         => $email_intro,
                'body'          => $email_body,
                'closing_text'  => $email_close,
                'email_subject' => $email_subject,
            ),
            'email'
        );
        require_once RACKETMANAGER_PATH . '/admin/includes/contact.php';
    }
    /**
     * Handle league teams action function
     *
     * @param object $league league object.
     *
     * @return bool
     */
    protected function handle_league_teams_action( object $league ): bool {
        $updates = false;
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        if ( isset( $_POST['action'] ) ) {
            if ( 'delete' === $_POST['action'] ) {
                $this->delete_teams_from_league( $league );
                $updates = true;
            } elseif ( 'withdraw' === $_POST['action'] ) {
                $this->withdraw_teams_from_league( $league );
                $updates = true;
            }
        }
        return $updates;
    }
    /**
     * Delete teams from league in admin screen
     *
     * @param object $league league object.
     */
    private function delete_teams_from_league( object $league ): void {
        if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_teams-bulk' ) ) {
            $this->set_message( $this->invalid_security_token, true );
            return;
        }
        if ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
            if ( ! current_user_can( 'del_teams' ) ) {
                $this->set_message( $this->no_permission, true );
                return;
            }
            $league        = get_league( $league );
            $season        = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
            $messages      = array();
            if ( isset( $_POST['team'] ) ) {
                foreach ( $_POST['team'] as $team_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                    $league->delete_team( intval( $team_id ), $season );
                    $messages[] = $team_id . ' ' . __( 'deleted', 'racketmanager' );
                }
                $message = implode( '<br>', $messages );
                $this->set_message( $message );
            }
        }
    }
    /**
     * Withdraw teams from league in admin screen
     *
     * @param object $league league object.
     */
    private function withdraw_teams_from_league( object $league ): void {
        if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_teams-bulk' ) ) {
            $this->set_message( $this->invalid_security_token, true );
            return;
        }
        if ( isset( $_POST['action'] ) && 'withdraw' === $_POST['action'] ) {
            if ( current_user_can( 'del_teams' ) ) {
                $this->set_message( $this->no_permission, true );
                return;
            }
            $league        = get_league( $league );
            $season        = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
            $messages      = array();
            if ( isset( $_POST['team'] ) ) {
                foreach ( $_POST['team'] as $team_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                    $team = get_team( $team_id );
                    $league->withdraw_team( intval( $team_id ), $season );
                    $messages[] = $team->title . ' ' . __( 'withdrawn', 'racketmanager' );
                }
                $message = implode( '<br>', $messages );
                $this->set_message( $message );
            }
        }
    }
    /**
     * Display teams list page
     */
    protected function display_teams_list(): void {
        $validator = new Validator();
        $league_id = isset( $_GET['league_id'] ) ? intval( $_GET['league_id'] ) : null;
        $validator = $validator->capability( 'edit_teams' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->league( $league_id );
        }
        if ( ! empty(  $validator->error ) ) {
            if ( empty( $validator->msg ) ) {
                $this->set_message( $validator->err_msgs[0], true );
            } else {
                $this->set_message( $validator->msg, true );
            }
            $this->show_message();
            return;
        }
        $league      = get_league( $league_id );
        $league_type = $league->type;
        if ( 'LD' === $league_type ) {
            $league_type = 'XD';
        }
        $entry_type = '';
        if ( $league->event->competition->is_player_entry ) {
            $entry_type = 'player';
        }
        if ( empty( $league->championship->is_consolation ) ) {
            $teams = $this->get_club_teams( $entry_type, $league_type );
        } else {
            $teams = $this->get_consolation_teams( $league );
        }
        $season        = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : '';
        $view          = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';
        $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
        $type          = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : null;
        if ( $tournament_id ) {
            $tournament = get_tournament( $tournament_id );
        }
        //phpcs:enable WordPress.Security.NonceVerification.Recommended
        require_once RACKETMANAGER_PATH . '/admin/includes/teams-list.php';
    }

    /**
     * Function to get club teams for list.
     *
     * @param string $entry_type entry type.
     * @param string $league_type league type.
     *
     * @return array
     */
    private function get_club_teams( string $entry_type, string $league_type ): array {
        global $racketmanager;
        $teams = array();
        $clubs = $racketmanager->get_clubs();
        if ( $clubs ) {
            foreach ( $clubs as $club ) {
                $club       = get_club( $club );
                $club_teams = $club->get_teams( array( 'players' => $entry_type, 'type' => $league_type ) );
                if ( $club_teams ) {
                    foreach ( $club_teams as $team ) {
                        $teams[] = $team;
                    }
                }
            }
        }
        return $teams;
    }

    /**
     * Function to get consolation teams
     *
     * @param object $league league object.
     *
     * @return array
     */
    private function get_consolation_teams( object $league ): array {
        $primary_league = get_league( $league->event->primary_league );
        $teams          = $primary_league->get_league_teams();
        $t              = 0;
        foreach ( $teams as $team ) {
            $match_array                     = array();
            $match_array['loser_id']         = $team->id;
            $match_array['count']            = true;
            $match_array['final']            = 'all';
            $match_array['reset_query_args'] = true;
            $matches                         = $primary_league->get_matches( $match_array );
            if ( ! $matches ) { // team did not lose a match.
                unset( $teams[ $t ] );
            } else {
                $match_array['loser_id'] = null;
                $match_array['team_id']  = $team->id;
                $matches                 = $primary_league->get_matches( $match_array );
                $last_match              = null;
                if ( $matches > 2 ) { // team played more than 2 matches.
                    unset( $teams[ $t ] );
                } elseif ( 2 === $matches ) { // team played 2 matches in main league.
                    $match_array['count'] = false;
                    $matches              = $primary_league->get_matches( $match_array );
                    if ( $matches ) {
                        $first_match = $matches[0];
                        if ( '-1' !== $first_match->home_team && '-1' !== $first_match->away_team ) { // first match not a bye.
                            unset( $teams[ $t ] );
                        } else {
                            $last_match = $matches[1];
                        }
                    }
                } elseif ( 1 === $matches ) {
                    $match_array['count'] = false;
                    $matches              = $primary_league->get_matches( $match_array );
                    $last_match           = $matches[0];
                }
                if ( $last_match && $last_match->is_walkover ) {
                    unset( $teams[ $t ] );
                }
            }
            ++$t;
        }
        $match_array                     = array();
        $match_array['reset_query_args'] = true;
        $final_name                      = $primary_league->championship->get_final_keys( 1 );
        $match_array['final']            = $final_name;
        $match_array['pending']          = true;
        $matches                         = $primary_league->get_matches( $match_array );
        if ( $matches ) {
            foreach ( $matches as $match ) {
                $team    = $this->build_loser_team( $final_name, $match );
                $teams[] = $team;
            }
        }
        $final_name           = $primary_league->championship->get_final_keys( 2 );
        $match_array['final'] = $final_name;
        $matches              = $primary_league->get_matches( $match_array );
        if ( $matches ) {
            foreach ( $matches as $match ) {
                $possible   = 0;
                $team_types = array( 'home', 'away' );
                foreach ( $team_types as $team_type ) {
                    $team_ref = $team_type . '_team';
                    if ( is_numeric( $match->$team_ref ) ) {
                        $match_array['pending']   = false;
                        $match_array['final']     = 'all';
                        $match_array['winner_id'] = $match->$team_ref;
                        $team_matches             = $primary_league->get_matches( $match_array );
                        foreach ( $team_matches as $team_match ) {
                            if ( '-1' === $team_match->home_team || '-1' === $team_match->away_team ) {
                                ++$possible;
                            }
                        }
                    }
                }
                if ( $possible ) {
                    $team    = $this->build_loser_team( $final_name, $match );
                    $teams[] = $team;
                }
            }
        }
        return $teams;
    }

    /**
     * Function to build loser team entry
     *
     * @param string $final_name name of final round.
     * @param object $match match object.
     *
     * @return stdClass
     */
    private function build_loser_team( string $final_name, object $match ): object {
        $team          = new stdClass();
        $team->id      = '2_' . $final_name . '_' . $match->id;
        $team->title   = __( 'Loser of ', 'racketmanager' ) . $match->teams['home']->title . ' ' . __( 'vs', 'racketmanager' ) . ' ' . $match->teams['away']->title;
        $team->stadium = '';
        return $team;
    }
    /**
     * Add teams to league in admin screen
     *
     * @param object $league league object.
     */
    protected function league_add_teams( object $league ): void {
        if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add-teams-bulk' ) ) {
            $this->set_message( $this->invalid_security_token, true );
        } elseif ( current_user_can( 'edit_teams' ) ) {
            if ( isset( $_POST['team'] ) && isset( $_POST['event_id'] ) && isset( $_POST['season'] ) ) {
                $league = get_league( $league );
                foreach ( $_POST['team'] as $team_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $league->add_team( $team_id, sanitize_text_field( wp_unslash( $_POST['season'] ) ) );
                    if ( is_numeric( $team_id ) ) {
                        $team = get_team( $team_id );
                        $team->set_event( intval( $_POST['event_id'] ) );
                    }
                }
            }
        } else {
            $this->set_message( $this->no_permission, true );
        }
    }
    public function rank_teams( object $league, $type ): void {
        $validator = new Validator();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_teams-bulk' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->capability( 'update_results' );
        }
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            return;
        }
        $team_ids = isset( $_POST['table_id'] ) ? array_values( $_POST['table_id'] ) : array();
        if ( $team_ids ) {
            $league = get_league( $league );
            switch ( $type ) {
                case 'random':
                    $team_ids = $this->league_random_rank_teams( $team_ids );
                    break;
                case 'ratings':
                    $team_ids = $this->league_rating_points_rank_teams( $team_ids, $league );
                    break;
                case 'manual':
                    $team_ids = $this->league_manual_rank_teams( $team_ids );
                    break;
                default:
                    break;
            }
            if ( $team_ids ) {
                $team_ranks = array();
                foreach ( $team_ids as $key => $team_id ) {
                    $rank                    = $key + 1;
                    $team                    = get_league_team( $team_id );
                    $team_ranks[ $rank - 1 ] = $team;
                }
                $team_ranks = $league->get_ranking( $team_ranks );
                $league->update_ranking( $team_ranks );
                $this->set_message( $this->team_ranking_saved );
            } else {
                $this->set_message( $this->no_updates, 'warning' );
            }
        }
    }
    /**
     * Randomly rank teams league in admin screen
     *
     * @param array $team_ids team ids.
     * @return array team ids sorted.
     */
    protected function league_random_rank_teams( array $team_ids ): array {
        shuffle( $team_ids );
        return $team_ids;
    }
    /**
     * Rating points rank teams league in admin screen
     *
     * @param array $team_ids team ids.
     * @return array team ids sorted.
     */
    protected function league_rating_points_rank_teams( array $team_ids, object $league ): array {
        if ( isset( $_POST['rating_points'] ) ) {
            $rating_points = array_values( $_POST['rating_points'] ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            array_multisort( $rating_points, SORT_ASC, $team_ids, SORT_ASC );
        }
        if ( $league->is_championship && $league->championship->num_seeds ) {
            $teams_seeded   = array_slice( $team_ids, 0, $league->championship->num_seeds );
            $teams_unseeded = array_slice( $team_ids, $league->championship->num_seeds );
            $teams_unseeded = $this->league_random_rank_teams( $teams_unseeded );
            $team_ids       = array_merge( $teams_seeded, $teams_unseeded );
        }
        return $team_ids;
    }
    /**
     * Manually rank teams league in admin screen
     *
     * @param array $team_ids team ids.
     * @return array team ids sorted.
     */
    protected function league_manual_rank_teams( array $team_ids ): array {
        if ( ! isset( $_POST['js-active'] ) && '1' !== $_POST['js-active'] ) {
            $ranks = isset( $_POST['rank'] ) ? array_values( $_POST['rank'] ) :  array();
            if ( $ranks ) {
                array_multisort( $ranks, SORT_ASC, $team_ids, SORT_ASC );
            }
        }
        return $team_ids;
    }
    /**
     * Manage matches in league in admin screen
     *
     * @param object $league league object.
     */
    protected function manage_matches_in_league( object $league ): void {
        if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-matches' ) ) {
            $this->set_message( $this->invalid_security_token, true );
        } elseif ( current_user_can( 'edit_matches' ) ) {
            if ( ! empty( $_POST['mode'] ) && 'add' === sanitize_text_field( wp_unslash( $_POST['mode'] ) ) ) {
                $this->add_matches_to_league( $league );
            } else {
                $this->edit_matches_in_league( $league );
            }
        } else {
            $this->set_message( $this->no_permission, true );
        }
    }

    /**
     * Add matches to league in admin screen
     *
     * @param object $league league object.
     * @param string|null $group group details.
     */
    protected function add_matches_to_league( object $league, string $group = null ): void {
        if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-matches' ) ) {
            $this->set_message( $this->invalid_security_token, true );
        } elseif ( isset( $_POST['match'] ) ) {
            $league = get_league( $league );
            $season = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
            $final  = isset( $_POST['final'] ) ? sanitize_text_field( wp_unslash( $_POST['final'] ) ) : null;
            if ( $final ) {
                $final_exists = $league->get_matches(
                    array(
                        'final'  => $final,
                        'season' => $season,
                    )
                );
                if ( $final_exists ) {
                    /* translators: %d: number of matches */
                    $this->set_message( sprintf( __( 'Matches already exist for %s', 'racketmanager' ), $final ), true );
                    return;
                }
            }
            $num_matches = count( $_POST['match'] );
            foreach ( $_POST['match'] as $i => $match_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                $match = new stdClass();
                if ( isset( $_POST['away_team'][ $i ] ) && isset( $_POST['home_team'][ $i ] ) && $_POST['away_team'][ $i ] !== $_POST['home_team'][ $i ] ) {
                    $index = ( isset( $_POST['myDatePicker'][ $i ] ) ) ? $i : 0;
                    if ( ! isset( $_POST['begin_hour'][ $i ] ) ) {
                        $_POST['begin_hour'][ $i ] = 0;
                    }
                    if ( ! isset( $_POST['begin_minutes'][ $i ] ) ) {
                        $_POST['begin_minutes'][ $i ] = 0;
                    }
                    if ( isset( $_POST['myDatePicker'][ $index ] ) && isset( $_POST['begin_hour'][ $i ] ) ) {
                        $match->date      = sanitize_text_field( wp_unslash( $_POST['myDatePicker'][ $index ] ) ) . ' ' . intval( $_POST['begin_hour'][ $i ] ) . ':' . intval( $_POST['begin_minutes'][ $i ] ) . ':00';
                        $match->match_day = '';
                        if ( isset( $_POST['match_day'][ $i ] ) ) {
                            $match->match_day = sanitize_text_field( wp_unslash( $_POST['match_day'][ $i ] ) );
                        } elseif ( ! empty( $_POST['match_day'] ) ) {
                            $match->match_day = intval( $_POST['match_day'] );
                        }
                        $match->host        = isset( $_POST['host'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['host'][ $i ] ) ) : null;
                        $match->home_team   = sanitize_text_field( wp_unslash( $_POST['home_team'][ $i ] ) );
                        $match->away_team   = sanitize_text_field( wp_unslash( $_POST['away_team'][ $i ] ) );
                        $match->location    = isset( $_POST['location'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['location'][ $i ] ) ) : null;
                        $match->league_id   = isset( $_POST['league_id'] ) ? sanitize_text_field( wp_unslash( $_POST['league_id'] ) ) : null;
                        $match->season      = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
                        $match->group       = $group;
                        $match->final_round = isset( $_POST['final'] ) ? sanitize_text_field( wp_unslash( $_POST['final'] ) ) : null;
                        $match->num_rubbers = isset( $_POST['num_rubbers'] ) ? intval( $_POST['num_rubbers'] ) : null;
                        $league->add_match( $match );
                    }
                } else {
                    --$num_matches;
                }
            }
            /* translators: %d: number of matches */
            $this->set_message( sprintf( _n( '%d Match added', '%d Matches added', $num_matches, 'racketmanager' ), $num_matches ) );
        }
    }

    /**
     * Edit matches in league in admin screen
     *
     * @param object $league league object.
     */
    protected function edit_matches_in_league( object $league ): void {
        if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-matches' ) ) {
            $this->set_message( $this->invalid_security_token, true );
        } elseif ( isset( $_POST['match'] ) ) {
            $num_matches = count( $_POST['match'] );
            $post_match  = wp_unslash( $_POST['match'] ); //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            foreach ( $post_match as $i => $match_id ) {
                $match         = get_match( $match_id );
                $begin_hour    = isset( $_POST['begin_hour'][ $i ] ) ? intval( $_POST['begin_hour'][ $i ] ) : '00';
                $begin_minutes = isset( $_POST['begin_minutes'][ $i ] ) ? intval( $_POST['begin_minutes'][ $i ] ) : '00';
                if ( isset( $_POST['myDatePicker'][ $i ] ) ) {
                    $date  = sanitize_text_field( wp_unslash( $_POST['myDatePicker'][ $i ] ) );
                    $date  = $date . ' ' . $begin_hour . ':' . $begin_minutes . ':00';
                } else {
                    $index = ( isset( $_POST['year'][ $i ] ) && isset( $_POST['month'][ $i ] ) && isset( $_POST['day'][ $i ] ) ) ? $i : 0;
                    $year  = isset( $_POST['year'][ $index ] ) ? intval( $_POST['year'][ $index ] ) : 0;
                    $month = isset( $_POST['month'][ $index ] ) ? intval( $_POST['month'][ $index ] ) : 0;
                    $day   = isset( $_POST['day'][ $index ] ) ? intval( $_POST['day'][ $index ] ) : 0;
                    $date  = $year . '-' . $month . '-' . $day . ' ' . $begin_hour . ':' . $begin_minutes . ':00';
                }
                $match->date      = $date;
                $match->league_id = $league->id;
                $match->match_day = null;
                if ( isset( $_POST['match_day'] ) ) {
                    if ( is_array( $_POST['match_day'] ) ) {
                        $match->match_day = isset( $_POST['match_day'][ $i ] ) ? intval( $_POST['match_day'][ $i ] ) : null;
                    } elseif ( ! empty( $_POST['match_day'] ) ) {
                        $match->match_day = intval( $_POST['match_day'] );
                    }
                }
                $match->host        = isset( $_POST['host'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['host'][ $i ] ) ) : null;
                $match->home_team   = isset( $_POST['home_team'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['home_team'][ $i ] ) ) : '';
                $match->away_team   = isset( $_POST['away_team'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['away_team'][ $i ] ) ) : '';
                $match->location    = isset( $_POST['location'][ $i ] ) ? sanitize_text_field( wp_unslash( $_POST['location'][ $i ] ) ) : null;
                $match->final_round = isset( $_POST['final'] ) ? sanitize_text_field( wp_unslash( $_POST['final'] ) ) : null;
                $league->update_match( $match );
            }
            /* translators: %d: number of matches updated */
            $this->set_message( sprintf( _n( '%d Match updated', '%d Matches updated', $num_matches, 'racketmanager' ), $num_matches ) );
        }
    }
    /**
     * Delete season(s) from competition via admin
     *
     * @param object $competition competition object.
     */
    protected function delete_seasons_from_competition( object $competition ): void {
        if ( ! current_user_can( 'del_seasons' ) ) {
            $this->set_message( $this->no_permission, true );
        } elseif ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'seasons-bulk' ) ) {
            $this->set_message( $this->invalid_security_token, true );
        } elseif ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] && isset( $_POST['del_season'] ) ) {
            $msg = array();
            foreach ( $_POST['del_season'] as $season ) {  //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                $update          = $competition->delete_season( $season );
                $schedule_args[] = intval( $competition->id );
                $schedule_args[] = intval( $season );
                $schedule_name   = 'rm_notify_team_entry_open';
                Util::clear_scheduled_event( $schedule_name, $schedule_args );
                $schedule_name = 'rm_notify_team_entry_reminder';
                Util::clear_scheduled_event( $schedule_name, $schedule_args );
                $schedule_name = 'rm_calculate_team_ratings';
                Util::clear_scheduled_event( $schedule_name, $schedule_args );
                if ( $update ) {
                    /* translators: %s: season name */
                    $msg[] = sprintf( __( 'Season %s deleted', 'racketmanager' ), $season );
                } else {
                    /* translators: %s: season name */
                    $msg[] = sprintf( __( 'Season %s not deleted', 'racketmanager' ), $season );
                }
            }
            $this->set_message( implode( '<br>', $msg ) );
        }
    }
    /**
     * Set message
     *
     * @param string $message message.
     * @param boolean|string|null $error triggers error message if true.
     */
    public function set_message( string $message, bool|string|null $error = false ): void {
        if ( true === $error ) {
            $this->error = 'error';
        } elseif ( 'warning' === $error ) {
            $this->error = 'warning';
        } elseif ( 'info' === $error ) {
            $this->error = 'info';
        } elseif ( 'error' === $error ) {
            $this->error = 'error';
        } elseif ( 'danger' === $error ) {
            $this->error = 'danger';
        } else {
            $this->error = false;
        }
        $this->message = $message;
    }
    /**
     * Print formatted message
     */
    public function show_message(): void {
        if ( ! empty( $this->message ) ) {
            $alert_class = match ( $this->error ) {
                'error'   => 'danger',
                'warning' => 'warning',
                'info'    => 'info',
                default   => 'success',
            };
            echo show_alert( $this->message, $alert_class ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        $this->message = '';
    }
}
