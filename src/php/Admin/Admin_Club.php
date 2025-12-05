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
use Racketmanager\Exceptions\Player_Already_Registered_Exception;
use Racketmanager\Exceptions\Registration_Not_Found_Exception;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Services\Validator\Validator_Club;
use Racketmanager\Util\Util_Lookup;
use stdClass;
use function Racketmanager\get_club;
use function Racketmanager\get_league;
use function Racketmanager\get_team;
use function Racketmanager\get_tournament;

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
        $this->admin_players = new Admin_Player( $this->racketmanager );
        if ( 'teams' === $view ) {
            $this->display_teams_page();
        } elseif ( 'team' === $view ) {
            $this->display_team_page();
        } elseif ( 'club' === $view ) {
            $this->display_club_page();
        } elseif ( 'players' === $view ) {
            $this->display_club_players_page();
        } elseif ( 'player' === $view ) {
            $this->admin_players->display_player_page();
        } elseif ( 'roles' === $view ) {
            $this->display_roles_page();
        } else {
            $this->display_clubs_page();
        }
    }
    /**
     * Display clubs page
     */
    public function display_clubs_page(): void {
        $validator = new Validator_Club();
        $validator = $validator->capability( 'edit_teams' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        if  ( isset( $_POST['doClubDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
            $validator = $validator->capability( 'edit_teams' );
            if ( empty( $validator->error ) ) {
                $validator = $validator->check_security_token( 'racketmanager_nonce', 'clubs-bulk' );
            }
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
            } else {
                $messages      = array();
                $message_error = false;
                if ( empty( $_POST['club'] ) ) {
                    $this->set_message( __( 'No clubs selected', 'racketmanager' ), true );
                } else {
                    foreach ( $_POST['club'] as $club_id ) {
                        try {
                            $this->club_service->remove_club( $club_id );
                            $messages[] = $club_id . ' ' . __( 'deleted', 'racketmanager' );
                        }
                        catch ( Exception $e ) {
                            $messages[] = $e->getMessage();
                            $message_error = true;
                        }
                    }
                    $message = implode( '<br>', $messages );
                    $this->set_message( $message, $message_error );
                }
            }
        } elseif ( isset( $_POST['doSchedulePlayerRatings'] ) ) {
            $validator = $validator->capability( 'edit_teams' );
            if ( empty( $validator->error ) ) {
                $validator = $validator->check_security_token( 'racketmanager_nonce', 'clubs-bulk' );
            }
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
            } else {
                $result = $this->racketmanager->schedule_player_ratings();
                if ( is_wp_error( $result ) ) {
                    $this->set_message( __( 'Error scheduling player ratings calculation', 'racketmanager' ), 'error' );
                } elseif ( $result ) {
                    $this->set_message( __( 'Player ratings calculation scheduled', 'racketmanager' ) );
                } else {
                    $this->set_message( __( 'Player ratings calculation already scheduled', 'racketmanager' ), 'info' );
                }
            }
        }
        $this->show_message();
        $clubs = $this->club_service->get_clubs();
        require_once RACKETMANAGER_PATH . 'templates/admin/show-clubs.php';
    }
    /**
     * Display club page
     */
    public function display_club_page(): void {
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
        if ( isset( $_POST['addClub'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_add-club');
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
            } else {
                $club      = $this->get_club_input();
                $validator = $validator->name( $club->name );
                $validator = $validator->short_code( $club->shortcode );
                $validator = $validator->type( $club->type );
                $validator = $validator->address( $club->address );
                if ( empty( $validator->error ) ) {
                    $club = $this->club_service->add_club( $club );
                    $edit = true;
                    ?>
                    <script>
                        let url = new URL(window.location.href);
                        url.searchParams.append('club_id', <?php echo esc_attr( $club->id ); ?>);
                        history.pushState('', '', url.toString());
                    </script>
                    <?php
                    $this->set_message( __( 'Club added', 'racketmanager' ) );
                } else {
                    $this->set_message( __( 'Unable to add club', 'racketmanager' ), true );
                }
            }
        } elseif ( isset( $_POST['editClub'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_manage-club');
            if ( empty( $validator->error ) ) {
                $club_id_passed = isset( $_POST[ 'club_id' ] ) ? (int) $_POST[ 'club_id' ] : null;
                $validator      = $validator->compare( $club_id_passed, $club_id );
            }
            if ( ! empty( $validator->error ) ) {
                if ( empty( $validator->msg ) ) {
                    $this->set_message( $validator->err_msgs[0], true );
                } else {
                    $this->set_message( $validator->msg, true );
                }
            } else {
                $club_updated = $this->get_club_input( $club );
                $validator    = $validator->name( $club_updated->name );
                $validator    = $validator->short_code( $club_updated->shortcode );
                $validator    = $validator->type( $club_updated->type );
                $validator    = $validator->address( $club_updated->address );
                $validator    = $validator->match_secretary( $club_updated->match_secretary->id );
                $validator    = $validator->telephone( $club_updated->match_secretary->contactno );
                $validator    = $validator->email( $club_updated->match_secretary->email, $club_updated->match_secretary->id );
                if ( empty( $validator->error ) ) {
                    $club_updated         = $this->club_service->update_club( $club->id, $club_updated );
                    if ( $club_updated ) {
                        $this->set_message( __( 'Club updated', 'racketmanager' ) );
                        $club = $club_updated;
                    } else {
                        $this->set_message( __( 'Club details unchanged', 'racketmanager' ), 'warning' );
                    }
                } else {
                    $club = $club_updated;
                    $this->set_message( __( 'Unable to update club', 'racketmanager' ), true );
                }
            }
        }
        $this->show_message();
        if ( $edit) {
            $form_title  = __( 'Edit Club', 'racketmanager' );
            $form_action = __( 'Update', 'racketmanager' );
        } else {
            $form_title  = __( 'Add Club', 'racketmanager' );
            $form_action = __( 'Add', 'racketmanager' );
        }
        require_once RACKETMANAGER_PATH . 'templates/admin/includes/club.php';
    }

    /**
     * Function to get club input data.
     *
     * @param object|null $club club object.
     *
     * @return object
     */
    private function get_club_input( ?object $club = null ):object {
        if ( empty( $club ) ) {
            $club_updated = new stdClass();
        } else {
            $club_updated = clone $club;
        }
        $club_updated->name                          = isset( $_POST['club'] ) ? sanitize_text_field( wp_unslash( $_POST['club'] ) ) : null;
        $club_updated->type                          = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : null;
        $club_updated->shortcode                     = isset( $_POST['shortcode'] ) ? sanitize_text_field( wp_unslash( $_POST['shortcode'] ) ) : null;
        $club_updated->match_secretary               = new stdClass();
        $club_updated->match_secretary->id           = isset( $_POST['match_secretary'] ) ? intval( $_POST['match_secretary'] ) : null;
        $club_updated->match_secretary->display_name = isset( $_POST['match_secretary_name'] ) ? sanitize_text_field( wp_unslash( $_POST['match_secretary_name'] ) ) : null;
        $club_updated->match_secretary->contactno    = isset( $_POST['match_secretary_contact_no'] ) ? sanitize_text_field( wp_unslash( $_POST['match_secretary_contact_no'] ) ) : null;
        $club_updated->match_secretary->email        = isset( $_POST['match_secretary_email'] ) ? sanitize_text_field( wp_unslash( $_POST['match_secretary_email'] ) ) : null;
        $club_updated->contactno                     = isset( $_POST['contactno'] ) ? sanitize_text_field( wp_unslash( $_POST['contactno'] ) ) : null;
        $club_updated->website                       = isset( $_POST['website'] ) ? sanitize_text_field( wp_unslash( $_POST['website'] ) ) : null;
        $club_updated->founded                       = empty( $_POST['founded'] ) ? null : intval($_POST['founded'] );
        $club_updated->facilities                    = isset( $_POST['facilities'] ) ? sanitize_text_field( wp_unslash( $_POST['facilities'] ) ) : null;
        $club_updated->address                       = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : null;
        return $club_updated;
    }
    /**
     * Display club players page
     */
    public function display_club_players_page(): void {
        $validator = new Validator();
        $validator = $validator->capability( 'edit_teams' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        if ( isset( $_POST['addPlayer'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_manage-player');
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
            } else {
                $club_id = isset( $_POST['club_Id'] ) ? intval( $_POST['club_Id'] ) : null;
                try {
                    $response = $this->club_player_service->register_player_to_club( $club_id, wp_get_current_user()->ID );
                    if ( is_wp_error( $response ) ) {
                        $form_valid     = false;
                        $error_fields   = $response->get_error_codes();
                        $error_messages = $response->get_error_messages();
                        $player         = $response->get_error_data( 'player' );
                        $this->set_message( __( 'Error with player details', 'racketmanager' ), true );
                    } else {
                        $this->set_message( $response );
                        $player = null;
                    }
                } catch ( Club_Not_Found_Exception|Player_Already_Registered_Exception|Registration_Not_Found_Exception $e ) {
                    $this->set_message( $e->getMessage(), true );
                }
            }
        } elseif ( isset( $_POST['doClubPlayerDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_club-players-bulk');
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
            } elseif ( isset( $_POST['clubPlayer'] ) ) {
                $msg = array();
                foreach ( $_POST['clubPlayer'] as $club_player_id ) {
                    try {
                        $this->club_player_service->remove_registration( $club_player_id, wp_get_current_user()->ID );
                        $msg[] = sprintf( __( '%s - Player has been removed from club', 'racketmanager' ), $club_player_id );
                    } catch ( Club_Not_Found_Exception | Registration_Not_Found_Exception $e ) {
                        $this->set_message( $e->getMessage(), true );
                    }
                }
                $message = implode( '<br>', $msg );
                $this->set_message( $message );
            }
        } elseif ( isset( $_POST['doPlayerRatings'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_club-players-bulk');
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
            } else {
                $club_id = isset( $_POST['club_id'] ) ? intval( $_POST['club_id'] ) : null;
                try {
                    $this->player_service->schedule_player_ratings( $club_id );
                    $this->set_message( __( 'Player ratings scheduled', 'racketmanager' ) );
                } catch ( Club_Not_Found_Exception $e ) {
                    $this->set_message( $e->getMessage(), true );
                }
            }
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
        $players = $this->club_player_service->get_registered_players_list( $active, null, $club_id, $gender );
        require_once RACKETMANAGER_PATH . 'templates/admin/club/show-club-players.php';
    }
    /**
     * Display teams page
     */
    public function display_teams_page(): void {
        $club_id   = null;
        $validator = new Validator();
        $validator = $validator->capability( 'edit_teams' );
        if ( ! empty( $validator->error ) ) {
            $this->set_message( $validator->msg, true );
            $this->show_message();
            return;
        }
        $club_id = isset( $_GET['club_id'] ) ? intval( $_GET['club_id'] ) : null;
        try {
            $club = $this->club_service->get_club( $club_id );
        } catch ( Club_Not_Found_Exception $e ) {
            $this->set_message( $e->getMessage(), true );
            $this->show_message();
            return;
        }
        if ( isset( $_POST['addTeam'] ) ) {
            if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add-team' ) ) {
                $this->set_message( $this->invalid_security_token, true );
            } else {
                $club_id_passed = isset( $_POST['club'] ) ? intval( $_POST['club'] ) : null;
                $team_type = isset( $_POST['team_type'] ) ? sanitize_text_field( wp_unslash( $_POST['team_type'] ) ) : null;
                try {
                    $team = $this->club_service->create_team( $club_id_passed, $team_type );
                    $this->set_message( __( 'Team added', 'racketmanager' ) );
                } catch ( Club_Not_Found_Exception|Invalid_Argument_Exception $e ) {
                    $this->set_message( $e->getMessage(), true );
                }
            }
        } elseif ( isset( $_POST['editTeam'] ) ) {
            if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-teams' ) ) {
                $this->set_message( $this->invalid_security_token, true );
            } elseif ( isset( $_POST['team_id'] ) ) {
                $team = get_team( intval( $_POST['team_id'] ) );
                if ( isset( $_POST['team'] ) && isset( $_POST['clubId'] ) && isset( $_POST['team_type'] ) ) {
                    $team->update( sanitize_text_field( wp_unslash( $_POST['team'] ) ), intval( $_POST['clubId'] ), sanitize_text_field( wp_unslash( $_POST['team_type'] ) ) );
                    $this->set_message( __( 'Team updated', 'racketmanager' ) );
                }
            }
        } elseif ( isset( $_POST['doTeamDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
            if ( ! current_user_can( 'del_teams' ) ) {
                $this->set_message( $this->no_permission, true );
            } elseif ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_teams-bulk' ) ) {
                $this->set_message( $this->invalid_security_token, true );
            } elseif ( isset( $_POST['team'] ) ) {
                $messages = array();
                foreach ( $_POST['team'] as $team_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $team = get_team( $team_id );
                    $team->delete();
                    $messages[] = $team->title . ' ' . __( 'deleted', 'racketmanager' );
                }
                $message = implode( '<br>', $messages );
                $this->set_message( $message );
            }
        }
        $this->show_message();
        $teams = $club->get_teams();
        require_once RACKETMANAGER_PATH . 'templates/admin/club/show-teams.php';
    }

    /**
     * Display team page
     */
    public function display_team_page(): void {
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
                    $tournament = get_tournament( $tournament_id );
                    if ( ! $tournament ) {
                        $this->set_message( __( 'Tournament not found', 'racketmanager' ), true );
                        $this->show_message();
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
        if ( isset( $_POST['addRole'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_add-club-role' );
            if ( empty( $validator->error ) ) {
                $club_id_passed = isset( $_POST['club_id'] ) ? intval( $_POST['club_id'] ) : null;
                $role_id        = isset( $_POST['role_id'] ) ? intval( $_POST['role_id'] ) : null;
                $user_id        = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : null;
                $club_role      = $this->club_service->set_club_role( $club_id_passed, $role_id, $user_id );
                if ( $club_role ) {
                    $this->set_message( __( 'Role added', 'racketmanager' ) );
                } else {
                    $this->set_message( __( 'Unable to add role', 'racketmanager' ), true );
                }
            } else {
                $this->set_message( $validator->msg, true );
            }
        } elseif ( isset( $_POST['delRole'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
            $validator = $validator->capability( 'del_teams' );
            if ( empty( $validator->error ) ) {
                $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_roles-bulk' );
            }
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->msg, true );
            } else {
                if ( isset( $_POST['role'] ) ) {
                    $messages = array();
                    foreach ( $_POST['role'] as $role_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                        $this->club_service->remove_club_role( $role_id );
                        $messages[] = __( 'Role deleted', 'racketmanager' );
                    }
                    $message = implode( '<br>', $messages );
                    $this->set_message( $message );
                }
            }
        }
        $this->show_message();
        $roles        = $this->club_service->get_roles_for_club( $club_id );
        $club_players = $this->club_player_service->get_registered_players_list( 'active', null, $club->id );
        require_once RACKETMANAGER_PATH . 'templates/admin/club/show-roles.php';
    }
}
