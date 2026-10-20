<?php
/**
 * RacketManager-Admin API: RacketManager-club class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Club
 */

namespace Racketmanager\Admin;

use Exception;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Argument_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Player_Already_Registered_Exception;
use Racketmanager\Exceptions\Registration_Not_Found_Exception;
use Racketmanager\Exceptions\Team_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Services\Validator\Validator_Club;
use Racketmanager\Util\Util_Lookup;
use stdClass;
use function Racketmanager\get_league;
use function Racketmanager\get_team;

/**
 * RacketManager Club Admin Club functions
 * Class to implement RacketManager Admin Club
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Club
 */
class Admin_Club extends Admin_Display {
    /**
     * Function to handle administration club displays
     *
     * @param string|null $view
     *
     * @return void
     */
    public function handle_display( ?string $view ): void {
        $view_map = [
                'teams'   => [ $this, 'display_teams_page' ],
                'team'    => [ $this, 'display_team_page' ],
                'club'    => [ $this, 'display_club_page' ],
                'players' => [ $this, 'display_club_players_page' ],
                'roles'   => [ $this, 'display_roles_page' ],
                'player'  => [ $this->get_admin_player(), 'display_player_page' ],
        ];
        try {
            // Resolve the callback or fall back to default
            $callback = $view_map[ $view ] ?? [ $this, 'display_clubs_page' ];

            call_user_func( $callback );

        } catch ( Club_Not_Found_Exception|Invalid_Status_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
        }
    }

    private function get_admin_player(): Admin_Player {
        return $this->admin_players ??= new Admin_Player( $this->racketmanager );
    }

    /**
     * Display clubs page
     */
    public function display_clubs_page(): void {
        $this->apply_flash_message();

        if ( isset( $_POST['doClubDel'] ) || isset( $_POST['doSchedulePlayerRatings'] ) ) {
            $result = $this->club_action_dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_club_action_result( $result, $_GET ) ) {
                return;
            }
        }

        $validator = new Validator_Club();
        $validator = $validator->capability( 'edit_teams' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }

        $this->show_message();
        $clubs = $this->club_service->get_clubs();
        require RACKETMANAGER_PATH . 'templates/admin/show-clubs.php';
    }
    /**
     * Display club page
     */
    public function display_club_page(): void {
        $this->apply_flash_message();

        if ( isset( $_POST['addClub'] ) || isset( $_POST['editClub'] ) ) {
            $result = $this->club_action_dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_club_action_result( $result, $_GET ) ) {
                return;
            }
        }

        $club_id   = null;
        $validator = new Validator_Club();
        $validator = $validator->capability( 'edit_teams' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        $club_id = isset( $_GET[ 'club_id' ] ) ? (int) $_GET[ 'club_id' ] : null;
        if ( $club_id ) {
            try {
                $club = $this->club_service->get_club( $club_id );
            } catch ( Club_Not_Found_Exception $e ) {
                $this->set_message( $e->getMessage(), true );
                $this->show_message();
                return;
            }
            $edit = true;
        } else {
            $edit = false;
            $club = null;
        }

        $this->show_message();
        if ( $edit ) {
            $form_title  = __( 'Edit Club', 'racketmanager' );
            $form_action = __( 'Update', 'racketmanager' );
        } else {
            $form_title  = __( 'Add Club', 'racketmanager' );
            $form_action = __( 'Add', 'racketmanager' );
        }
        require_once RACKETMANAGER_PATH . 'templates/admin/includes/club.php';
    }

    /**
     * Display club players page
     */
    public function display_club_players_page(): void {
        $this->apply_flash_message();

        if ( isset( $_POST['addPlayer'] ) || isset( $_POST['doClubPlayerDel'] ) || isset( $_POST['doPlayerRatings'] ) ) {
            $result = $this->club_player_action_dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_club_action_result( $result, $_GET ) ) {
                return;
            }
        }

        $validator = new Validator();
        $validator = $validator->capability( 'edit_teams' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }

        $this->show_message();
        $club_id = isset( $_GET['club_id'] ) ? intval( $_GET['club_id'] ) : null;
        try {
            $club = $this->club_service->get_club( $club_id );
        } catch ( Club_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
        }
        $active  = isset( $_GET['active'] ) ? sanitize_text_field( wp_unslash( $_GET['active'] ) ) : false;
        $gender  = isset( $_GET['gender'] ) ? sanitize_text_field( wp_unslash( $_GET['gender'] ) ) : false;
        $players = $this->registration_service->get_registered_players_list( $active, null, $club_id, $gender );
        require_once RACKETMANAGER_PATH . 'templates/admin/club/show-club-players.php';
    }
    /**
     * Display teams page
     */
    public function display_teams_page(): void {
        $this->apply_flash_message();

        if ( isset( $_POST['addTeam'] ) || isset( $_POST['editTeam'] ) || isset( $_POST['doTeamDel'] ) ) {
            $result = $this->club_team_action_dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_club_action_result( $result, $_GET ) ) {
                return;
            }
        }

        $club_id   = null;
        $validator = new Validator();
        $validator = $validator->capability( 'edit_teams' );
        if ( ! empty( $validator->error ) ) {
            throw new Invalid_Argument_Exception( $validator->msg );
        }
        $club_id = isset( $_GET['club_id'] ) ? intval( $_GET['club_id'] ) : null;
        try {
            $club = $this->club_service->get_club( $club_id );
        } catch ( Club_Not_Found_Exception $e ) {
            throw new Club_Not_Found_Exception( $e->getMessage() );
        }

        $this->show_message();
        $teams = $this->team_service->get_teams_for_club( $club_id );
        require_once RACKETMANAGER_PATH . 'templates/admin/club/show-teams.php';
    }

    /**
     * Display team page
     */
    public function display_team_page(): void {
        $this->apply_flash_message();

        if ( ! current_user_can( 'edit_teams' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
        } else {
            $file   = 'team.php';
            $edit   = false;
            $league = false;
            //phpcs:disable WordPress.Security.NonceVerification.Recommended
            if ( isset( $_GET['league_id'] ) ) {
                $league_id  = intval( $_GET['league_id'] );
                $league     = get_league( $league_id );
                $season     = isset( $_GET['season'] ) ? sanitize_text_field( wp_unslash( $_GET['season'] ) ) : '';
                $match_days = Util_Lookup::get_match_days();
                if ( $league->event->competition->is_player_entry ) {
                    $file = 'player-team.php';
                }
            } else {
                $league_id = '';
                $season    = '';
                if ( isset( $_GET['club_id'] ) ) {
                    $club_id = intval( $_GET['club_id'] );
                } else {
                    $club_id = '';
                }
            }
            $team_id       = isset( $_GET['edit'] ) ? intval( $_GET['edit'] ) : null;
            $tournament_id = isset( $_GET['tournament'] ) ? intval( $_GET['tournament'] ) : null;
            if ( $team_id ) {
                if ( $tournament_id ) {
                    try {
                        $tournament = $this->tournament_service->get_tournament( $tournament_id );
                    } catch ( Tournament_Not_Found_Exception $e ) {
                        throw new Tournament_Not_Found_Exception( $e->getMessage() );
                    }
                }
                $edit = true;
                if ( $league ) {
                    $team = $league->get_team_dtls( $team_id );
                } else {
                    $team = get_team( $team_id );
                }
                if ( ! isset( $team->roster ) ) {
                    $team->roster = array();
                }
                $form_title  = __( 'Edit Team', 'racketmanager' );
                $form_action = __( 'Update', 'racketmanager' );
                $clubs = $this->club_service->get_clubs();
                //phpcs:enable WordPress.Security.NonceVerification.Recommended
                require_once RACKETMANAGER_PATH . 'templates/admin/includes/teams/' . $file;
            } else {
                $this->set_message( __( 'Team not specified', 'racketmanager' ), true );
                $this->show_message();
            }
        }
    }
    /**
     * Display roles page
     */
    public function display_roles_page(): void {
        $this->apply_flash_message();

        if ( isset( $_POST['addRole'] ) || isset( $_POST['delRole'] ) ) {
            $result = $this->club_role_action_dispatcher->handle( $_GET, $_POST );
            if ( $this->deliver_club_action_result( $result, $_GET ) ) {
                return;
            }
        }

        $club_id   = null;
        $validator = new Validator();
        $validator = $validator->capability( 'edit_teams' );
        if ( empty( $validator->error ) ) {
            $club_id   = isset( $_GET['club_id'] ) ? intval( $_GET['club_id'] ) : null;
            $validator = $validator->club( $club_id );
        }
        if ( ! empty( $validator->error ) ) {
            if ( empty( $validator->msg ) ) {
                $msg = $validator->err_msgs[0];
            } else {
                $msg = $validator->msg;
            }
            $this->set_message( $msg, true );
            $this->show_message();
            return;
        }
        try {
            $club = $this->club_service->get_club( $club_id );
        } catch ( Club_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
            return;
        }

        $this->show_message();
        $roles        = $this->club_service->get_roles_for_club( $club_id );
        $club_players = $this->registration_service->get_registered_players_list( 'active', null, $club->id );
        require_once RACKETMANAGER_PATH . 'templates/admin/club/show-roles.php';
    }
}
