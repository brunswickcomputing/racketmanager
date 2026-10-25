<?php
/**
 * RacketManager-Admin API: RacketManager-admin-display class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin-Display
 */

namespace Racketmanager\Admin;

use Racketmanager\Admin\Controllers\Admin_Redirect_Url_Builder;
use Racketmanager\Admin\Presenters\Admin_Championship_Finals_Presenter;
use Racketmanager\Admin\Presenters\Admin_Date_Presenter;
use Racketmanager\Admin\Presenters\Admin_League_Schedule_Presenter;
use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Updated_Exception;
use Racketmanager\Exceptions\Fixture_Not_Found_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\Club\Club_Action_Dispatcher;
use Racketmanager\Services\Admin\Club\Club_Player_Action_Dispatcher;
use Racketmanager\Services\Admin\Club\Club_Role_Action_Dispatcher;
use Racketmanager\Services\Admin\Club\Club_Team_Action_Dispatcher;
use Racketmanager\Services\Admin\Finance\Finance_Charge_Action_Dispatcher;
use Racketmanager\Services\Admin\Finance\Finance_Invoice_Action_Dispatcher;
use Racketmanager\Services\Admin\League\Event_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Constitution_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Contact_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Event_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Match_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Schedule_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\League\League_Team_Action_Dispatcher;
use Racketmanager\Services\Admin\League\Season_Delete_Action_Dispatcher;
use Racketmanager\Services\Admin\Season\Season_Action_Dispatcher;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Container\Container_Bootstrap;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Services\Validator\Validator;
use stdClass;
use function Racketmanager\get_club;
use function Racketmanager\get_league;
use function Racketmanager\get_league_team;
use function Racketmanager\get_team;
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
    protected ?string $invalid_permissions = 'You do not have sufficient permissions to access this page';
    protected ?string $invalid_security_token = 'Security token invalid';
    protected ?string $no_permission = 'You do not have permission to perform this task';
    protected ?string $team_ranking_saved = 'Team ranking saved';
    public Admin_League $admin_league;
    public Admin_Season $admin_seasons;
    public Admin_Result $admin_results;
    public Admin_Options $admin_options;
    public Admin_Index $admin_index;
    protected ?string $no_updates = 'No updates';
    protected ?string $errors_found = 'Errors found';
    public Admin_Upgrade $admin_upgrade;
    protected Club_Service $club_service;
    protected Player_Service $player_service;
    protected Registration_Service $registration_service;
    protected RacketManager $racketmanager;
    protected Team_Service $team_service;
    protected League_Service $league_service;
    protected Competition_Service $competition_service;
    protected Finance_Service $finance_service;
    protected Season_Service $season_service;
    protected Tournament_Service $tournament_service;
    protected Admin_Club $admin_club;
    protected Fixture_Repository_Interface $fixture_repository;
    protected Event_Repository_Interface $event_repository;
    protected \Racketmanager\Services\Competition\Event_Query_Service $event_query_service;
    protected \Racketmanager\Services\Competition\League_Query_Service $league_query_service;
    protected ?Admin_League_Schedule_Presenter $admin_league_schedule_presenter = null;
    protected Admin_Action_Adapter $admin_action_adapter;
    protected Admin_Message_Service $admin_message_service;
    protected League_Contact_Action_Dispatcher $league_contact_dispatcher;
    protected League_Setup_Action_Dispatcher $league_setup_dispatcher;
    protected Event_Setup_Action_Dispatcher $event_setup_dispatcher;
    protected Season_Delete_Action_Dispatcher $season_delete_dispatcher;
    protected League_Team_Action_Dispatcher $league_team_dispatcher;
    protected League_Match_Action_Dispatcher $league_match_dispatcher;
    protected League_Constitution_Action_Dispatcher $league_constitution_dispatcher;
    protected League_Event_Action_Dispatcher $league_event_dispatcher;
    protected League_Schedule_Action_Dispatcher $league_schedule_dispatcher;
    protected Club_Action_Dispatcher $club_action_dispatcher;
    protected Club_Player_Action_Dispatcher $club_player_action_dispatcher;
    protected Club_Team_Action_Dispatcher $club_team_action_dispatcher;
    protected Club_Role_Action_Dispatcher $club_role_action_dispatcher;
    protected Season_Action_Dispatcher $season_admin_action_dispatcher;

    /**
     * Constructor
     */
    public function __construct( ?RacketManager $racketmanager = null, ?Simple_Container $container = null ) {
        if ( $racketmanager instanceof RacketManager ) {
            $this->racketmanager = $racketmanager;
        }
        $this->load_translations();
        add_action( 'init', array( &$this, 'load_translations' ) );
        $c = $container ?? ( isset( $this->racketmanager->container ) ? $this->racketmanager->container : null );
        if ( null === $c ) {
            throw new \LogicException( 'Admin_Display requires a Simple_Container instance.' );
        }
        $this->club_service         = $c->get( 'club_service' );
        $this->player_service       = $c->get( 'player_service' );
        $this->registration_service = $c->get( 'registration_service' );
        $this->team_service         = $c->get( 'team_service' );
        $this->league_service       = $c->get( 'league_service' );
        $this->competition_service  = $c->get( 'competition_service' );
        $this->finance_service      = $c->get( 'finance_service' );
        $this->season_service       = $c->get( 'season_service' );
        $this->tournament_service   = $c->get( 'tournament_service' );
        $this->fixture_repository   = $c->get( 'fixture_repository' );
        $this->event_repository     = $c->has( 'event_repository' )
            ? $c->get( 'event_repository' )
            : new \Racketmanager\Repositories\Event_Repository();
        $this->event_query_service  = $c->has( 'event_query_service' )
            ? $c->get( 'event_query_service' )
            : \Racketmanager\get_event_query_service();
        $this->league_query_service = $c->has( 'league_query_service' )
            ? $c->get( 'league_query_service' )
            : \Racketmanager\get_league_query_service();
        if ( $c->has( 'admin_league_schedule_presenter' ) ) {
            $this->admin_league_schedule_presenter = $c->get( 'admin_league_schedule_presenter' );
        }
        $this->admin_action_adapter      = $c->get( 'admin_action_adapter' );
        $this->admin_message_service     = $c->get( 'admin_message_service' );
        $this->league_contact_dispatcher = $c->get( 'league_contact_action_dispatcher' );
        $this->league_setup_dispatcher   = $c->get( 'league_setup_action_dispatcher' );
        $this->event_setup_dispatcher    = $c->get( 'event_setup_action_dispatcher' );
        $this->season_delete_dispatcher  = $c->get( 'season_delete_action_dispatcher' );
        $this->league_team_dispatcher         = $c->get( 'league_team_action_dispatcher' );
        $this->league_match_dispatcher        = $c->get( 'league_match_action_dispatcher' );
        $this->league_constitution_dispatcher = $c->get( 'league_constitution_action_dispatcher' );
        $this->league_event_dispatcher        = $c->get( 'league_event_action_dispatcher' );
        $this->league_schedule_dispatcher     = $c->get( 'league_schedule_action_dispatcher' );
        $this->club_action_dispatcher         = $c->get( 'club_action_dispatcher' );
        $this->club_player_action_dispatcher  = $c->get( 'club_player_action_dispatcher' );
        $this->club_team_action_dispatcher    = $c->get( 'club_team_action_dispatcher' );
        $this->club_role_action_dispatcher    = $c->get( 'club_role_action_dispatcher' );
        $this->season_admin_action_dispatcher = $c->get( 'season_action_dispatcher' );
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
        $options = $this->racketmanager->options;
        // Update Plugin Version.
        if ( RACKETMANAGER_VERSION !== $options['version'] ) {
            flush_rewrite_rules();
            $options['version'] = RACKETMANAGER_VERSION;
            $this->racketmanager->update_plugin_options( $options );
        }
        // Update database.
        if ( ! isset( $options['dbversion'] ) || RACKETMANAGER_DBVERSION !== $options['dbversion'] ) {
            $this->admin_upgrade = new Admin_Upgrade( $this->racketmanager );
            $this->admin_upgrade->handle_display();
            return;
        }
        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
        switch ( $page ) {
            case 'racketmanager-documentation':
            case 'racketmanager-doc':
                require_once RACKETMANAGER_PATH . 'templates/admin/documentation.php';
                break;
            case 'racketmanager-leagues':
                $this->admin_league = new Admin_League( $this->racketmanager );
                $this->admin_league->handle_display( $view );
                break;
            case 'racketmanager-cups':
                $this->admin_cup = new Admin_Cup( $this->racketmanager );
                $this->admin_cup->handle_display( $view );
                break;
            case 'racketmanager-tournaments':
                $admin_tournament = new Admin_Tournament(
                    $this->racketmanager,
                    $this->racketmanager->container->get( 'view_renderer' ),
                    $this->racketmanager->container->get( 'admin_message_service' ),
                    $this->racketmanager->container->get( 'admin_action_adapter' ),
                    $this->racketmanager->container->get( 'admin_screen_presenter' )
                );
                $admin_tournament->handle_display( $view );
                break;
            case 'racketmanager-clubs':
                $this->admin_club = new Admin_Club( $this->racketmanager );
                $this->admin_club->handle_display( $view );
                break;
            case 'racketmanager-results':
                $this->admin_results = new Admin_Result( $this->racketmanager );
                $this->admin_results->handle_display( $view );
                break;
            case 'racketmanager-seasons':
                $this->admin_seasons = new Admin_Season( $this->racketmanager );
                $this->admin_seasons->handle_display( $view );
                break;
            case 'racketmanager-players':
                $this->admin_players = new Admin_Player( $this->racketmanager );
                $this->admin_players->handle_display( $view );
                break;
            case 'racketmanager-finances':
                $this->admin_finances = new Admin_Finances( $this->racketmanager );
                $this->admin_finances->handle_display( $view );
                break;
            case 'racketmanager-settings':
                $this->admin_options = new Admin_Options( $this->racketmanager );
                $this->admin_options->handle_display( $view );
                break;
            case 'racketmanager-import':
                $this->admin_import = new Admin_Import( $this->racketmanager );
                $this->admin_import->display_import_page();
                break;
            case 'racketmanager':
            default:
                $this->admin_index = new Admin_Index( $this->racketmanager );
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
                $competition = $this->competition_service->get_competition( intval( $_GET['competition_id'] ) );
                $object_type = 'competition';
                $object_name = 'competition_id';
                $object_id   = $competition->id;
            } elseif ( isset( $_GET['tournament_id'] ) ) {
                $tournament_id = intval( $_GET['tournament_id'] );
                try {
                    $tournament = $this->tournament_service->get_tournament( $tournament_id );
                } catch ( Tournament_Not_Found_Exception $e ) {
                    throw new Tournament_Not_Found_Exception( $e->getMessage() );
                }
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
            require_once RACKETMANAGER_PATH . 'templates/admin/includes/contact.php';
        }
    }

    /**
     * Function to show contact preview
     *
     * @return void
     */
    private function show_contact_preview(): void {
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
            $competition = $this->competition_service->get_competition( intval( $_POST['competition_id'] ) );
            $title       = $competition->name;
            $object_type = 'competition';
            $object      = $competition;
            $object_name = 'competition_id';
            $object_id   = $competition->id;
        } elseif ( isset( $_POST['tournament_id'] ) ) {
            $tournament_id = intval( $_POST['tournament_id'] );
            try {
                $tournament = $this->tournament_service->get_tournament( $tournament_id );
            } catch ( Tournament_Not_Found_Exception $e ) {
                throw new Tournament_Not_Found_Exception( $e->getMessage() );
            }
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
        $email_subject = $this->racketmanager->site_name . ' - ' . $title . ' ' . $season . ' - Important Message';
        $email_message = $this->racketmanager->shortcodes->load_template(
            'contact-teams',
            array(
                $object_type    => $object,
                'organisation'  => $this->racketmanager->site_name,
                'season'        => $season,
                'title_text'    => $email_title,
                'intro'         => $email_intro,
                'body'          => $email_body,
                'closing_text'  => $email_close,
                'email_subject' => $email_subject,
            ),
            'email'
        );
        require_once RACKETMANAGER_PATH . 'templates/admin/includes/contact.php';
    }
    /**
     * Contact teams in league in admin screen (guarded mutation, Post-Redirect-Get).
     *
     * @return bool True when the request was redirected and the screen must not render again.
     */
    protected function contact_teams(): bool {
        $result = $this->league_contact_dispatcher->handle( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by the action guard.

        return $this->admin_action_adapter->deliver_redirect(
            array(
                'redirect'     => Admin_Redirect_Url_Builder::league_screen( $_GET ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
            ),
            true
        );
    }
    /**
     * Show a notice stored by a previous Post-Redirect-Get redirect.
     *
     * @return array{message?:string, message_type?:bool|string, flash_data?:array<string,mixed>}
     */
    protected function apply_flash_message(): array {
        $flash = $this->admin_message_service->pop_flash_message();
        if ( ! empty( $flash['message'] ) ) {
            $this->set_message( strval( $flash['message'] ), $flash['message_type'] ?? false );
        }
        return $flash;
    }

    /**
     * Deliver a guarded mutation result via Post-Redirect-Get.
     *
     * @param array<string, mixed> $query Typically $_GET identifying the current screen.
     *
     * @return bool True when a redirect was issued and the screen must not render.
     */
    protected function deliver_league_action_result( Action_Result_DTO $result, array $query ): bool {
        $extra_args = array();
        if ( null !== $result->tab_override && '' !== $result->tab_override ) {
            $extra_args['tab'] = $result->tab_override;
        }

        return $this->admin_action_adapter->deliver_redirect(
            array(
                'redirect'     => Admin_Redirect_Url_Builder::league_screen( $query, $extra_args ),
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
            ),
            true
        );
    }

    /**
     * Deliver a guarded club mutation result via Post-Redirect-Get.
     *
     * @param Action_Result_DTO    $result
     * @param array<string, mixed> $query Typically $_GET identifying the current screen.
     * @param array<string, mixed> $extra_args
     *
     * @return bool True when a redirect was issued and the screen must not render.
     */
    protected function deliver_club_action_result( Action_Result_DTO $result, array $query, array $extra_args = array() ): bool {
        if ( null !== $result->tab_override && '' !== $result->tab_override ) {
            $extra_args['tab'] = $result->tab_override;
        }

        $extra_args = array_merge( $extra_args, $result->query_params );

        return $this->admin_action_adapter->deliver_redirect(
            array(
                'redirect'     => Admin_Redirect_Url_Builder::club_screen( $query, $extra_args ),
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
            ),
            true
        );
    }

    /**
     * Deliver a guarded season mutation result via Post-Redirect-Get.
     *
     * @param Action_Result_DTO    $result
     * @param array<string, mixed> $query Typically $_GET identifying the current screen.
     * @param array<string, mixed> $extra_args
     *
     * @return bool True when a redirect was issued and the screen must not render.
     */
    protected function deliver_season_action_result( Action_Result_DTO $result, array $query, array $extra_args = array() ): bool {
        if ( null !== $result->tab_override && '' !== $result->tab_override ) {
            $extra_args['tab'] = $result->tab_override;
        }

        $extra_args = array_merge( $extra_args, $result->query_params );

        return $this->admin_action_adapter->deliver_redirect(
            array(
                'redirect'     => Admin_Redirect_Url_Builder::season_screen( $query, $extra_args ),
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            ),
            true
        );
    }

    /**
     * Deliver a guarded competition mutation result via Post-Redirect-Get.
     *
     * @param Action_Result_DTO    $result
     * @param array<string, mixed> $query Typically $_GET identifying the current screen.
     * @param array<string, mixed> $extra_args
     *
     * @return bool True when a redirect was issued and the screen must not render.
     */
    protected function deliver_competition_action_result( Action_Result_DTO $result, array $query, array $extra_args = array() ): bool {
        if ( null !== $result->tab_override && '' !== $result->tab_override ) {
            $extra_args['tab'] = $result->tab_override;
        }

        $extra_args = array_merge( $extra_args, $result->query_params );

        return $this->admin_action_adapter->deliver_redirect(
            array(
                'redirect'     => Admin_Redirect_Url_Builder::competition_screen( $query, $extra_args ),
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            ),
            true
        );
    }

    /**
     * Deliver a guarded event mutation result via Post-Redirect-Get.
     *
     * @param Action_Result_DTO    $result
     * @param array<string, mixed> $query Typically $_GET identifying the current screen.
     * @param array<string, mixed> $extra_args
     *
     * @return bool True when a redirect was issued and the screen must not render.
     */
    protected function deliver_event_action_result( Action_Result_DTO $result, array $query, array $extra_args = array() ): bool {
        if ( null !== $result->tab_override && '' !== $result->tab_override ) {
            $extra_args['tab'] = $result->tab_override;
        }

        $extra_args = array_merge( $extra_args, $result->query_params );

        return $this->admin_action_adapter->deliver_redirect(
            array(
                'redirect'     => Admin_Redirect_Url_Builder::event_screen( $query, $extra_args ),
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            ),
            true
        );
    }

    /**
     * Deliver a guarded finances mutation result via Post-Redirect-Get.
     *
     * @param Action_Result_DTO    $result
     * @param array<string, mixed> $query Typically $_GET identifying the current screen.
     * @param array<string, mixed> $extra_args
     *
     * @return bool True when a redirect was issued and the screen must not render.
     */
    protected function deliver_finances_action_result( Action_Result_DTO $result, array $query, array $extra_args = array() ): bool {
        if ( null !== $result->tab_override && '' !== $result->tab_override ) {
            $extra_args['tab'] = $result->tab_override;
        }

        $extra_args = array_merge( $extra_args, $result->query_params );

        return $this->admin_action_adapter->deliver_redirect(
            array(
                'redirect'     => Admin_Redirect_Url_Builder::finances_screen( $query, $extra_args ),
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            ),
            true
        );
    }

    /**
     * Deliver a guarded cup mutation result via Post-Redirect-Get.
     *
     * @param Action_Result_DTO    $result
     * @param array<string, mixed> $query Typically $_GET identifying the current screen.
     * @param array<string, mixed> $extra_args
     *
     * @return bool True when a redirect was issued and the screen must not render.
     */
    protected function deliver_cup_action_result( Action_Result_DTO $result, array $query, array $extra_args = array() ): bool {
        if ( null === $result->message && null === $result->message_type && null === $result->tab_override && empty( $result->flash_data ) ) {
            return false;
        }

        if ( null !== $result->tab_override && '' !== $result->tab_override ) {
            $extra_args['tab'] = $result->tab_override;
        }

        $extra_args = array_merge( $extra_args, $result->query_params );

        return $this->admin_action_adapter->deliver_redirect(
            array(
                'redirect'     => Admin_Redirect_Url_Builder::cup_screen( $query, $extra_args ),
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            ),
            true
        );
    }

    /**
     * Deliver a guarded results mutation result via Post-Redirect-Get.
     *
     * @param Action_Result_DTO    $result
     * @param array<string, mixed> $query Typically $_GET identifying the current screen.
     * @param array<string, mixed> $extra_args
     *
     * @return bool True when a redirect was issued and the screen must not render.
     */
    protected function deliver_results_action_result( Action_Result_DTO $result, array $query, array $extra_args = array() ): bool {
        if ( null === $result->message && null === $result->message_type && null === $result->tab_override && empty( $result->flash_data ) ) {
            return false;
        }

        if ( null !== $result->tab_override && '' !== $result->tab_override ) {
            $extra_args['tab'] = $result->tab_override;
        }

        $extra_args = array_merge( $extra_args, $result->query_params );

        return $this->admin_action_adapter->deliver_redirect(
            array(
                'redirect'     => Admin_Redirect_Url_Builder::results_screen( $query, $extra_args ),
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            ),
            true
        );
    }

    /**
     * Deliver a guarded index (competitions) mutation result via Post-Redirect-Get.
     *
     * @param Action_Result_DTO    $result
     * @param array<string, mixed> $query Typically $_GET identifying the current screen.
     * @param array<string, mixed> $extra_args
     *
     * @return bool True when a redirect was issued and the screen must not render.
     */
    protected function deliver_index_action_result( Action_Result_DTO $result, array $query, array $extra_args = array() ): bool {
        if ( null === $result->message && null === $result->message_type && null === $result->tab_override && empty( $result->flash_data ) ) {
            return false;
        }

        if ( null !== $result->tab_override && '' !== $result->tab_override ) {
            $extra_args['tab'] = $result->tab_override;
        }

        $extra_args = array_merge( $extra_args, $result->query_params );

        return $this->admin_action_adapter->deliver_redirect(
            array(
                'redirect'     => Admin_Redirect_Url_Builder::index_screen( $query, $extra_args ),
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            ),
            true
        );
    }

    /**
     * Deliver a guarded options (settings) mutation result via Post-Redirect-Get.
     *
     * @param Action_Result_DTO    $result
     * @param array<string, mixed> $query Typically $_GET identifying the current screen.
     * @param array<string, mixed> $extra_args
     *
     * @return bool True when a redirect was issued and the screen must not render.
     */
    protected function deliver_options_action_result( Action_Result_DTO $result, array $query, array $extra_args = array() ): bool {
        if ( null === $result->message && null === $result->message_type && null === $result->tab_override && empty( $result->flash_data ) ) {
            return false;
        }

        if ( null !== $result->tab_override && '' !== $result->tab_override ) {
            $extra_args['tab'] = $result->tab_override;
        }

        $extra_args = array_merge( $extra_args, $result->query_params );

        return $this->admin_action_adapter->deliver_redirect(
            array(
                'redirect'     => Admin_Redirect_Url_Builder::options_screen( $query, $extra_args ),
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            ),
            true
        );
    }

    /**
     * Deliver a guarded import mutation result via Post-Redirect-Get.
     *
     * @param Action_Result_DTO    $result
     * @param array<string, mixed> $query Typically $_GET identifying the current screen.
     * @param array<string, mixed> $extra_args
     *
     * @return bool True when a redirect was issued and the screen must not render.
     */
    protected function deliver_import_action_result( Action_Result_DTO $result, array $query, array $extra_args = array() ): bool {
        if ( null === $result->message && null === $result->message_type && null === $result->tab_override && empty( $result->flash_data ) ) {
            return false;
        }

        if ( null !== $result->tab_override && '' !== $result->tab_override ) {
            $extra_args['tab'] = $result->tab_override;
        }

        $extra_args = array_merge( $extra_args, $result->query_params );

        return $this->admin_action_adapter->deliver_redirect(
            array(
                'redirect'     => Admin_Redirect_Url_Builder::import_screen( $query, $extra_args ),
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            ),
            true
        );
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
        $validator = new Validator();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_teams-bulk' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->capability( 'del_teams' );
        }
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        if ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
            $league   = get_league( $league );
            $season   = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
            $messages = array();
            if ( isset( $_POST['team'] ) && $league ) {
                try {
                    foreach ( $_POST['team'] as $team_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                        $this->league_service->delete_team( $league, intval( $team_id ), $season );
                        $messages[] = $team_id . ' ' . __( 'deleted', 'racketmanager' );
                    }
                    $message = implode( '<br>', $messages );
                    $this->set_message( $message );
                } catch ( \RuntimeException | \InvalidArgumentException $e ) {
                    $this->set_message( $e->getMessage(), true );
                    $this->show_message();
                    return;
                }
            }
        }
    }
    /**
     * Withdraw teams from league in admin screen
     *
     * @param object $league league object.
     */
    private function withdraw_teams_from_league( object $league ): void {
        $validator = new Validator();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_teams-bulk' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->capability( 'del_teams' );
        }
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        if ( isset( $_POST['action'] ) && 'withdraw' === $_POST['action'] ) {
            $league   = get_league( $league );
            $season   = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
            $messages = array();
            if ( isset( $_POST['team'] ) && $league ) {
                try {
                    foreach ( $_POST['team'] as $team_id ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
                        $team = get_team( $team_id );
                        $this->league_service->withdraw_team( $league, intval( $team_id ), (string) $season );
                        $messages[] = $team->title . ' ' . __( 'withdrawn', 'racketmanager' );
                    }
                    $message = implode( '<br>', $messages );
                    $this->set_message( $message );
                } catch ( \RuntimeException | \InvalidArgumentException $e ) {
                    $this->set_message( $e->getMessage(), true );
                    $this->show_message();
                    return;
                }
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
            if ( $league->event->competition->is_player_entry ) {
                $teams = $this->team_service->get_player_teams( $league_type );
            } else {
                $teams = $this->get_club_teams( $league_type );
            }
        } else {
            $teams = $this->get_consolation_teams( $league );
        }
        $season        = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : '';
        $view          = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';
        $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
        $type          = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : null;
        if ( $tournament_id ) {
            try {
                $tournament = $this->tournament_service->get_tournament( $tournament_id );
            } catch ( Tournament_Not_Found_Exception $e ) {
                throw new Tournament_Not_Found_Exception( $e->getMessage() );
            }
        }
        //phpcs:enable WordPress.Security.NonceVerification.Recommended
        require_once RACKETMANAGER_PATH . 'templates/admin/includes/teams-list.php';
    }

    /**
     * Function to get club teams for list.
     *
     * @param string $league_type league type.
     *
     * @return array
     */
    private function get_club_teams( string $league_type ): array {
        $teams = array();
        $clubs = $this->club_service->get_clubs(
            array(
                'type' => 'affiliated',
            )
        );
        if ( $clubs ) {
            foreach ( $clubs as $club ) {
                $club       = get_club( $club );
                $club_teams = $this->team_service->get_teams_for_club( $club->id, $league_type );
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
        $teams          = $this->get_league_query_service()->get_league_teams( $primary_league );
        $t              = 0;
        foreach ( $teams as $team ) {
            $match_array                     = array();
            $match_array['loser_id']         = $team->id;
            $match_array['count']            = true;
            $match_array['final']            = 'all';
            $match_array['reset_query_args'] = true;
            $matches                         = $this->get_league_query_service()->get_matches( $primary_league, $match_array );
            if ( ! $matches ) { // team did not lose a match.
                unset( $teams[ $t ] );
            } else {
                $match_array['loser_id'] = null;
                $match_array['team_id']  = $team->id;
                $matches                 = $this->get_league_query_service()->get_matches( $primary_league, $match_array );
                $last_match              = null;
                if ( $matches > 2 ) { // team played more than 2 matches.
                    unset( $teams[ $t ] );
                } elseif ( 2 === $matches ) { // team played 2 matches in main league.
                    $match_array['count'] = false;
                    $matches              = $this->get_league_query_service()->get_matches( $primary_league, $match_array );
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
                    $matches              = $this->get_league_query_service()->get_matches( $primary_league, $match_array );
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
        $matches                         = $this->get_league_query_service()->get_matches( $primary_league, $match_array );
        if ( $matches ) {
            foreach ( $matches as $match ) {
                $team    = $this->build_loser_team( $final_name, $match );
                $teams[] = $team;
            }
        }
        $final_name           = $primary_league->championship->get_final_keys( 2 );
        $match_array['final'] = $final_name;
        $matches              = $this->get_league_query_service()->get_matches( $primary_league, $match_array );
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
                        $team_matches             = $this->get_league_query_service()->get_matches( $primary_league, $match_array );
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
        $validator = new Validator();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_add-teams-bulk' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->capability( 'edit_teams' );
        }
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        $league_id = $league->get_id();
        $season    = isset( $_POST['season'] ) ? intval( $_POST['season'] ) : null;
        if ( isset( $_POST['team'] ) && isset( $_POST['event_id'] ) && isset( $_POST['season'] ) ) {
            foreach ( $_POST['team'] as $team_id ) {
                try {
                    $this->league_service->add_team_to_league( $team_id, $league_id, $season );
                    $this->set_message( __( 'Team added', 'racketmanager' ) );
                } catch ( Team_Not_Found_Exception|League_Not_Found_Exception $e ) {
                    $this->set_message( $e->getMessage(), true );
                }
            }
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
            $ranking_performed = $this->league_service->rank_teams( $league, $type, $_POST, $team_ids );
            if ( $ranking_performed ) {
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
        return $this->league_service->sort_team_ids_random( $team_ids );
    }
    /**
     * Rating points rank teams league in admin screen
     *
     * @param array $team_ids team ids.
     * @return array team ids sorted.
     */
    protected function league_rating_points_rank_teams( array $team_ids, object $league ): array {
        return $this->league_service->sort_team_ids_by_ratings( $league, $_POST, $team_ids );
    }
    /**
     * Manually rank teams league in admin screen
     *
     * @param array $team_ids team ids.
     * @return array team ids sorted.
     */
    protected function league_manual_rank_teams( array $team_ids ): array {
        return $this->league_service->sort_team_ids_manual( $_POST, $team_ids );
    }

    /**
     * Display season list
     */
    public function display_seasons_page(): void {
        $this->apply_flash_message();
        $competition_id = isset( $_GET['competition_id'] ) ? intval( $_GET['competition_id'] ) : null;
        if ( isset( $_POST['doActionSeason'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $result = $this->season_delete_dispatcher->handle( $_GET, $_POST ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by the action guard.
            if ( $this->deliver_league_action_result( $result, $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                return;
            }
        }
        $this->show_message();
        try {
            $competition = $this->competition_service->get_by_id( $competition_id );
            require_once RACKETMANAGER_PATH . 'templates/admin/includes/show-seasons.php';
        } catch ( Competition_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
        }
    }

    /**
     * Delete season(s) from a competition via admin
     *
     * @param ?int $competition_id competition id.
     */
    protected function delete_seasons_from_competition( ?int $competition_id ): void {
        $validator = new Validator();
        $validator = $validator->check_security_token( 'racketmanager_nonce', 'seasons-bulk' );
        if ( empty( $validator->error ) ) {
            $validator = $validator->capability( 'del_matches' );
        }
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        if ( isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
            $seasons = isset( $_POST['del_season'] ) ? array_map( 'intval', $_POST['del_season'] ) : array();
            try {
                $this->competition_service->delete_seasons( $competition_id, $seasons );
                $this->set_message( __( 'Season(s) deleted', 'racketmanager' ) );
            } catch ( Competition_Not_Updated_Exception $e ) {
                $this->set_message( $e->getMessage(), 'warning' );
            } catch ( Competition_Not_Found_Exception $e ) {
                $this->set_message( $e->getMessage(), true );
            } catch ( \RuntimeException | \InvalidArgumentException $e ) {
                $this->set_message( $e->getMessage(), true );
            }
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

    /**
     * Build presented finals (with preformatted fixture view models) for a cup or league championship.
     *
     * @param object $league League with a championship.
     * @param string $season Season name.
     *
     * @return array Presented finals, indexed as a list; rounds without fixtures are omitted.
     */
    public function present_league_finals( object $league, string $season ): array {
        if ( empty( $league->championship ) || ! method_exists( $league->championship, 'get_finals' ) ) {
            return array();
        }
        $container       = $this->racketmanager->container;
        $detail_service  = $container->get( 'fixture_detail_service' );
        $active_finals   = array();
        foreach ( array_reverse( (array) $league->championship->get_finals() ) as $final ) {
            $matches = $this->get_league_query_service()->get_matches( $league,
                array(
                    'season'           => $season,
                    'final'            => $final['key'],
                    'orderby'          => array( 'id' => 'ASC' ),
                    'reset_query_args' => true,
                )
            );
            if ( empty( $matches ) || ! is_array( $matches ) ) {
                continue;
            }
            $fixtures = array();
            foreach ( $matches as $match ) {
                try {
                    $details = $detail_service->get_fixture_with_details( $match );
                } catch ( Fixture_Not_Found_Exception $e ) {
                    continue;
                }
                if ( $details ) {
                    $fixtures[] = $details;
                }
            }
            $final['fixtures'] = $fixtures;
            $active_finals[]   = (object) $final;
        }
        $presenter = $container instanceof Simple_Container && $container->has( 'admin_championship_finals_presenter' )
            ? $container->get( 'admin_championship_finals_presenter' )
            : new Admin_Championship_Finals_Presenter( new Admin_Date_Presenter() );
        return $presenter->present_finals( $active_finals, null, $league, $season );
    }

    protected function get_event_query_service(): \Racketmanager\Services\Competition\Event_Query_Service {
        return $this->event_query_service;
    }

    public function get_event_repository(): Event_Repository_Interface {
        return $this->event_repository;
    }

    public function get_league_query_service(): \Racketmanager\Services\Competition\League_Query_Service {
        return $this->league_query_service;
    }
}
