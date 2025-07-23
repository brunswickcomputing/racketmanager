<?php
/**
 * RacketManager-Admin API: RacketManager-club class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Admin/Club
 */

namespace Racketmanager;

use stdClass;

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
        } else {
            $this->display_clubs_page();
        }
    }
    /**
     * Display clubs page
     */
    public function display_clubs_page(): void {
        global $racketmanager;

        if ( ! current_user_can( 'edit_leagues' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
        } else {
            if ( isset( $_POST['addClub'] ) ) {
                check_admin_referer( 'racketmanager_add-club' );
                if ( ! current_user_can( 'edit_teams' ) ) {
                    $this->set_message( $this->no_permission, true );
                } else {
                    $club             = new stdClass();
                    $club->name       = isset( $_POST['club'] ) ? sanitize_text_field( wp_unslash( $_POST['club'] ) ) : null;
                    $club->type       = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : null;
                    $club->shortcode  = isset( $_POST['shortcode'] ) ? sanitize_text_field( wp_unslash( $_POST['shortcode'] ) ) : null;
                    $club->contactno  = isset( $_POST['contactno'] ) ? sanitize_text_field( wp_unslash( $_POST['contactno'] ) ) : null;
                    $club->website    = isset( $_POST['website'] ) ? sanitize_text_field( wp_unslash( $_POST['website'] ) ) : null;
                    $club->founded    = isset( $_POST['founded'] ) ? intval( $_POST['founded'] ) : null;
                    $club->facilities = isset( $_POST['facilities'] ) ? sanitize_text_field( wp_unslash( $_POST['facilities'] ) ) : null;
                    $club->address    = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : null;
                    $club->latitude   = isset( $_POST['latitude'] ) ? sanitize_text_field( wp_unslash( $_POST['latitude'] ) ) : null;
                    $club->longitude  = isset( $_POST['longitude'] ) ? sanitize_text_field( wp_unslash( $_POST['longitude'] ) ) : null;
                    if ( empty( $club->name ) ) {
                        $this->set_message( __( 'Name required', 'racketmanager' ), true );
                    } elseif ( empty( $club->shortcode ) ) {
                        $this->set_message( __( 'Shortcode required', 'racketmanager' ), 'error' );
                    } elseif ( empty( $club->address ) ) {
                        $this->set_message( __( 'Address required', 'racketmanager' ), 'error' );
                    } else {
                        $club             = new Club( $club );
                        $this->set_message( __( 'Club added', 'racketmanager' ) );
                    }
                }
                $this->show_message();
            } elseif ( isset( $_POST['editClub'] ) ) {
                check_admin_referer( 'racketmanager_manage-club' );
                if ( ! current_user_can( 'edit_teams' ) ) {
                    $this->set_message( $this->no_permission, true );
                } elseif ( isset( $_POST['club_id'] ) ) {
                    $club          = get_club( intval( $_POST['club_id'] ) );
                    $club->name    = isset( $_POST['club'] ) ? sanitize_text_field( wp_unslash( $_POST['club'] ) ) : null;
                    $club->type    = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : null;
                    $old_shortcode = $club->shortcode;
                    if ( $club->shortcode !== $_POST['shortcode'] ) {
                        $club->shortcode = isset( $_POST['shortcode'] ) ? sanitize_text_field( wp_unslash( $_POST['shortcode'] ) ) : null;
                    }
                    $club->matchsecretary             = isset( $_POST['match_secretary'] ) ? intval( $_POST['match_secretary'] ) : null;
                    $club->match_secretary_contact_no = isset( $_POST['match_secretary_contact_no'] ) ? sanitize_text_field( wp_unslash( $_POST['match_secretary_contact_no'] ) ) : null;
                    $club->match_secretary_email      = isset( $_POST['match_secretary_email'] ) ? sanitize_text_field( wp_unslash( $_POST['match_secretary_email'] ) ) : null;
                    $club->contactno                  = isset( $_POST['contactno'] ) ? sanitize_text_field( wp_unslash( $_POST['contactno'] ) ) : null;
                    $club->website                    = isset( $_POST['website'] ) ? sanitize_text_field( wp_unslash( $_POST['website'] ) ) : null;
                    $club->founded                    = isset( $_POST['founded'] ) ? intval( $_POST['founded'] ) : null;
                    $club->facilities                 = isset( $_POST['facilities'] ) ? sanitize_text_field( wp_unslash( $_POST['facilities'] ) ) : null;
                    $club->address                    = isset( $_POST['address'] ) ? sanitize_text_field( wp_unslash( $_POST['address'] ) ) : null;
                    $club->latitude                   = isset( $_POST['latitude'] ) ? sanitize_text_field( wp_unslash( $_POST['latitude'] ) ) : null;
                    $club->longitude                  = isset( $_POST['longitude'] ) ? sanitize_text_field( wp_unslash( $_POST['longitude'] ) ) : null;
                    $club->update( $club, $old_shortcode );
                    $this->set_message( __( 'Club updated', 'racketmanager' ) );
                }
                $this->show_message();
            } elseif ( isset( $_POST['doClubDel'] ) && isset( $_POST['action'] ) && 'delete' === $_POST['action'] ) {
                check_admin_referer( 'clubs-bulk' );
                if ( ! current_user_can( 'del_teams' ) ) {
                    $this->set_message( $this->no_permission, true );
                } else {
                    $messages      = array();
                    $message_error = false;
                    if ( empty( $_POST['club'] ) ) {
                        $this->set_message( __( 'No clubs selected', 'racketmanager' ), true );
                    } else {
                        foreach ( $_POST['club'] as $club_id ) { //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
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
                        $club_id = 0;
                    }
                }

                $this->show_message();
            } elseif ( isset( $_POST['doSchedulePlayerRatings'] ) ) {
                check_admin_referer( 'clubs-bulk' );
                if ( ! current_user_can( 'del_teams' ) ) {
                    $this->set_message( $this->no_permission, true );
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
                $this->show_message();
            }
            $clubs = $racketmanager->get_clubs();
            require_once RACKETMANAGER_PATH . '/admin/show-clubs.php';
        }
    }

    /**
     * Display club page
     */
    public function display_club_page(): void {
        if ( ! current_user_can( 'edit_teams' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
        } else {
            $edit      = false;
            $league_id = '';
            $season    = '';
            if ( isset( $_GET['club_id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $club_id     = intval( $_GET['club_id'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $edit        = true;
                $club        = get_club( $club_id );
                $form_title  = __( 'Edit Club', 'racketmanager' );
                $form_action = __( 'Update', 'racketmanager' );
            } else {
                $club_id     = '';
                $form_title  = __( 'Add Club', 'racketmanager' );
                $form_action = __( 'Add', 'racketmanager' );
                $club        = (object) array(
                    'name'                       => '',
                    'type'                       => '',
                    'id'                         => '',
                    'website'                    => '',
                    'matchsecretary'             => '',
                    'match_secretary_name'       => '',
                    'contactno'                  => '',
                    'match_secretary_contact_no' => '',
                    'match_secretary_email'      => '',
                    'shortcode'                  => '',
                    'founded'                    => '',
                    'facilities'                 => '',
                    'address'                    => '',
                    'latitude'                   => '',
                    'longitude'                  => '',
                );
            }
            require_once RACKETMANAGER_PATH . '/admin/includes/club.php';
        }
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
            require_once RACKETMANAGER_PATH . '/admin/club/show-club-players.php';
        }
    }
    /**
     * Display teams page
     */
    public function display_teams_page(): void {
        $club_id = null;
        if ( ! current_user_can( 'edit_teams' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
        } else {
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
            if ( isset( $_GET['club_id'] ) ) {
                $club_id = intval( $_GET['club_id'] );
            }
            $club = get_club( $club_id );
            if ( $club ) {
                $teams = $club->get_teams();
                require_once RACKETMANAGER_PATH . '/admin/club/show-teams.php';
            } else {
                $this->set_message( __( 'Club not found', 'racketmanager' ), true );
            }
        }
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
                $match_days = Racketmanager_Util::get_match_days();
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
                require_once RACKETMANAGER_PATH . '/admin/includes/teams/' . $file;
            } else {
                $this->set_message( __( 'Team not specified', 'racketmanager' ), true );
                $this->show_message();
            }
        }
    }
}
