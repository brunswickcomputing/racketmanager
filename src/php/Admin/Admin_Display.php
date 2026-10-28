<?php
declare( strict_types=1 );
/**
 * RacketManager-Admin API: RacketManager-admin-display class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin-Display
 */

namespace Racketmanager\Admin;

use Racketmanager\Admin\Controllers\Admin_Redirect_Url_Builder;
use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Infrastructure\Wordpress\Admin\Admin_Action_Adapter;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Admin_Message_Service;
use Racketmanager\Services\Admin\Season\Season_Action_Dispatcher;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Container\Simple_Container;
use Racketmanager\Services\Season_Service;
use Racketmanager\Services\Tournament_Service;
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
    public Admin_Tournament $admin_tournament;
    protected ?string $no_permission = 'You do not have permission to perform this task';
    public Admin_League $admin_league;
    public Admin_Season $admin_seasons;
    public Admin_Result $admin_results;
    public Admin_Options $admin_options;
    public Admin_Index $admin_index;
    public Admin_Upgrade $admin_upgrade;
    protected Club_Service $club_service;
    protected RacketManager $racketmanager;
    protected Competition_Service $competition_service;
    protected Season_Service $season_service;
    protected Tournament_Service $tournament_service;
    public Admin_Club $admin_club;
    protected \Racketmanager\Services\Competition\Event_Query_Service $event_query_service;
    protected Admin_Action_Adapter $admin_action_adapter;
    protected Admin_Message_Service $admin_message_service;
    protected Season_Action_Dispatcher $season_admin_action_dispatcher;

    /**
     * Constructor
     */
    public function __construct(
        ?RacketManager $racketmanager = null,
        ?Simple_Container $container = null,
    ) {
        if ( $racketmanager instanceof RacketManager ) {
            $this->racketmanager = $racketmanager;
        }
        $this->load_translations();
        add_action( 'init', array( &$this, 'load_translations' ) );
        $c = $container ?? ( isset( $this->racketmanager->container ) ? $this->racketmanager->container : null );
        if ( null === $c ) {
            throw new \LogicException( 'Admin_Display requires a Simple_Container instance.' );
        }
        $this->club_service                   = $c->get( 'club_service' );
        $this->competition_service            = $c->get( 'competition_service' );
        $this->season_service                 = $c->get( 'season_service' );
        $this->tournament_service             = $c->get( 'tournament_service' );
        $this->event_query_service            = $c->get( 'event_query_service' );
        $this->admin_action_adapter           = $c->get( 'admin_action_adapter' );
        $this->admin_message_service          = $c->get( 'admin_message_service' );
        $this->season_admin_action_dispatcher = $c->get( 'season_action_dispatcher' );
    }
    public function load_translations(): void {
        $this->no_permission = __( 'You do not have permission to perform this task', 'racketmanager' );
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
                $this->admin_league = $this->racketmanager->container->get( 'admin_league_screen' );
                $this->admin_league->handle_display( $view );
                break;
            case 'racketmanager-cups':
                $this->admin_cup = $this->racketmanager->container->get( 'admin_cup_screen' );
                $this->admin_cup->handle_display( $view );
                break;
            case 'racketmanager-tournaments':
                $this->admin_tournament = $this->racketmanager->container->get( 'admin_tournament_screen' );
                $this->admin_tournament->handle_display( $view );
                break;
            case 'racketmanager-clubs':
                $this->admin_club = $this->racketmanager->container->get( 'admin_club_screen' );
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
                $this->admin_players = $this->racketmanager->container->get( 'admin_player_screen' );
                $this->admin_players->handle_display( $view );
                break;
            case 'racketmanager-finances':
                $this->admin_finances = $this->racketmanager->container->get( 'admin_finances_screen' );
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

    protected function get_event_query_service(): \Racketmanager\Services\Competition\Event_Query_Service {
        return $this->event_query_service;
    }
}
