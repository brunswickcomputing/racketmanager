<?php
/**
 * RacketManager-Admin API: RacketManager-club class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Club
 */

namespace Racketmanager\Admin;

use Racketmanager\Domain\Club;
use Racketmanager\Repositories\Club_Role_Repository;
use Racketmanager\Services\Club_Management_Service;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Services\Validator\Validator_Club;
use Racketmanager\Util\Util_Lookup;
use stdClass;
use function Racketmanager\get_club;
use function Racketmanager\get_club_player;
use function Racketmanager\get_club_role;
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
        $this->admin_players = new Admin_Player();
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
        global $racketmanager;
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
                        $club = get_club( $club_id );
                        if ( $club->get_teams( array( 'count' => true ) ) ) {
                            $messages[]    = $club->name . ' ' . __( 'not deleted - still has teams attached', 'racketmanager' );
                            $message_error = true;
                        } else {
                            $club->delete();
                            $messages[] = $club->name . ' ' . __( 'deleted', 'racketmanager' );
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
                $result = $racketmanager->schedule_player_ratings();
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
        $clubs = $racketmanager->get_clubs();
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
            $validator = $validator->club( $club_id );
            if ( ! empty( $validator->error ) ) {
                $this->set_message( $validator->err_msgs[0], true );
                $this->show_message();
                return;
            }
            $edit = true;
            $club = get_club( $club_id );
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
                    $club = new Club( $club );
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
                    $updates = $club->update( $club_updated );
                    if ( $updates ) {
                        $club = get_club( $club_id );
                        $this->set_message( __( 'Club updated', 'racketmanager' ) );
                    } else {
                        $this->set_message( __( 'No updates', 'racketmanager' ), 'warning' );
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
        $club_updated->latitude                      = isset( $_POST['latitude'] ) ? sanitize_text_field( wp_unslash( $_POST['latitude'] ) ) : null;
        $club_updated->longitude                     = isset( $_POST['longitude'] ) ? sanitize_text_field( wp_unslash( $_POST['longitude'] ) ) : null;
        return $club_updated;
    }
    /**
     * Display club players page
     */
    public function display_club_players_page(): void {
        global $racketmanager;
        $club_id = null;
        if ( ! current_user_can( 'edit_teams' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
        } else {
            if ( isset( $_POST['addPlayer'] ) ) {
                if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_manage-player' ) ) {
                    $this->set_message( $this->invalid_security_token, true );
                    $this->show_message();
                } else {
                    $player_valid = $racketmanager->validate_player();
                    if ( $player_valid[0] ) {
                        $new_player = $player_valid[1];
                        if ( isset( $_POST['club_Id'] ) ) {
                            $club = get_club( intval( $_POST['club_Id'] ) );
                            $answer = $club->register_player( $new_player );
                            $this->set_message( $answer->msg, $answer->error );
                            if ( $answer->error ) {
                                $player = $new_player;
                            }
                        }
                    } else {
                        $form_valid     = false;
                        $error_fields   = $player_valid[1];
                        $error_messages = $player_valid[2];
                        $player         = $player_valid[3];
                        $message        = __( 'Error with player details', 'racketmanager' );
                        $this->set_message( $message, true );
                    }
                }
            } elseif ( isset( $_POST['doClubPlayerDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
                check_admin_referer( 'club-players-bulk' );
                if ( isset( $_POST['clubPlayer'] ) ) {
                    foreach ( $_POST['clubPlayer'] as $club_player_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                        $club_player = get_club_player( $club_player_id );
                        $club_player?->remove();
                    }
                }
            } elseif ( isset( $_POST['doPlayerRatings'] ) ) {
                check_admin_referer( 'club-players-bulk' );
                if ( isset( $_POST['club_id'] ) ) {
                    $club_id = intval( $_POST['club_id'] );
                    $club    = get_club( $club_id );
                    if ( $club ) {
                        $schedule_name  = 'rm_calculate_player_ratings';
                        $schedule_args[]  = $club->id;
                        wp_schedule_single_event( time(), $schedule_name, $schedule_args );
                        $this->set_message( __( 'Player ratings set', 'racketmanager' ) );
                    }
                }
            }
            $this->show_message();
            if ( isset( $_GET['club_id'] ) ) {
                $club_id = intval( $_GET['club_id'] );
            }
            $club    = get_club( $club_id );
            $active  = isset( $_GET['active'] ) ? sanitize_text_field( wp_unslash( $_GET['active'] ) ) : false;
            $gender  = isset( $_GET['gender'] ) ? sanitize_text_field( wp_unslash( $_GET['gender'] ) ) : false;
            $players = $club->get_players(
                array(
                    'active' => $active,
                    'gender' => $gender,
                    'type'   => true,
                )
            );
            require_once RACKETMANAGER_PATH . 'templates/admin/club/show-club-players.php';
        }
    }
    /**
     * Display teams page
     */
    public function display_teams_page(): void {
        $club_id   = null;
        $validator = new Validator();
        $validator = $validator->capability( 'edit_teams' );
        if ( empty( $validator->error ) ) {
            $club_id = isset( $_GET['club_id'] ) ? intval( $_GET['club_id'] ) : null;
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
        $club = get_club( $club_id );
        if ( isset( $_POST['addTeam'] ) ) {
            if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_add-team' ) ) {
                $this->set_message( $this->invalid_security_token, true );
            } elseif ( isset( $_POST['club'] ) && isset( $_POST['team_type'] ) ) {
                $club = get_club( intval( $_POST['club'] ) );
                $club->add_team( sanitize_text_field( wp_unslash( $_POST['team_type'] ) ) );
                $this->set_message( __( 'Team added', 'racketmanager' ) );
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
        global $racketmanager;
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
                $clubs = $racketmanager->get_clubs();
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
        $club_role_repository = new Club_Role_Repository();
        $club_service         = new Club_Management_Service( $club_role_repository );
        $club                 = get_club( $club_id );
        if ( isset( $_POST['addRole'] ) ) {
            $validator = $validator->check_security_token( 'racketmanager_nonce', 'racketmanager_add-club-role' );
            if ( empty( $validator->error ) ) {
                $club_id_passed = isset( $_POST['club_id'] ) ? intval( $_POST['club_id'] ) : null;
                $role_id        = isset( $_POST['role_id'] ) ? intval( $_POST['role_id'] ) : null;
                $user_id        = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : null;
                $club_role      = $club_service->set_club_role( $club_id_passed, $role_id, $user_id );
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
                        $deleted = $club_service->remove_club_role( $role_id );
                        $messages[] = __( 'Role deleted', 'racketmanager' );
                    }
                    $message = implode( '<br>', $messages );
                    $this->set_message( $message );
                }
            }
        }
        $this->show_message();
        $roles = $club->get_club_roles( array( 'group' => true ) );
        require_once RACKETMANAGER_PATH . 'templates/admin/club/show-roles.php';
    }
}
