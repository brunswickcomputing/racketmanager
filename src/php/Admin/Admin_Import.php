<?php
/**
 * RacketManager-Admin API: RacketManager-import class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Import
 */

namespace Racketmanager\Admin;

use Racketmanager\Domain\Player;
use Racketmanager\Domain\Racketmanager_Match;
use Racketmanager\Util\Util;
use stdClass;
use function Racketmanager\get_club;
use function Racketmanager\get_league;
use function Racketmanager\get_league_team;
use function Racketmanager\get_player;

/**
 * RacketManager Import functions
 * Class to implement RacketManager Import
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Import
 */
class Admin_Import extends Admin_Display {
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
    /**
     * Display import Page
     */
    public function display_import_page(): void {
        if ( ! current_user_can( 'import_leagues' ) ) {
            $this->set_message( $this->invalid_permissions, true );
            $this->show_message();
        } else {
            if ( isset( $_POST['import'] ) ) {
                if ( ! isset( $_POST['racketmanager_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['racketmanager_nonce'] ) ), 'racketmanager_import-datasets' ) ) {
                    $this->set_message( $this->invalid_security_token, true );
                } else {
                    $league_id = isset( $_POST['league_id'] ) ? intval( $_POST['league_id'] ) : null;
                    $season    = isset( $_POST['season'] ) ? sanitize_text_field( wp_unslash( $_POST['season'] ) ) : null;
                    $club      = isset( $_POST['club'] ) ? intval( $_POST['club'] ) : null;
                    $files     = $_FILES['racketmanager_import'] ?? null; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $delimiter = $_POST['delimiter'] ?? null; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    $mode      = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : null;
                    $this->import( $files, $delimiter, $mode, $league_id, $season, $club );
                }
                $this->show_message();
            }
            require_once RACKETMANAGER_PATH . 'templates/admin/tools/import.php';
        }
    }
    /**
     * Import data from CSV file
     *
     * @param array $file CSV file.
     * @param string $delimiter delimiter.
     * @param string $mode 'teams' | 'matches' | 'fixtures' | 'players' | 'clubplayers'.
     * @param int|null $league_id league.
     * @param string|null $season season.
     * @param false|int $club - optional.
     */
    private function import( array $file, string $delimiter, string $mode, ?int $league_id, ?string $season, false|int $club = false ): void {
        if ( empty( $file['name'] ) ) {
            $this->set_message( __( 'No file specified for upload', 'racketmanager' ), true );
        } elseif ( 0 === $file['size'] ) {
            $this->set_message( __( 'Upload file is empty', 'racketmanager' ), true );
        } else {
            $access_type = get_filesystem_method();
            if ( 'direct' === $access_type ) {
                /* you can safely run request_filesystem_credentials() without any issues and don't need to worry about passing in a URL */
                $credentials = request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, array() );
                /* initialize the API */
                if ( ! WP_Filesystem( $credentials ) ) {
                    /* any problems and we exit */
                    $this->set_message( __( 'Unable to access file system', 'racketmanager' ), true );
                } else {
                    global $wp_filesystem;
                    $new_file = Util::get_file_path( $file['name'] );
                    if ( $wp_filesystem->copy( $file['tmp_name'], $new_file, true ) ) {
                        $contents = $wp_filesystem->get_contents_array( $new_file );
                        if ( $contents ) {
                            $club      = isset( $club ) ? intval( $club ) : 0;
                            if ( 'TAB' === $delimiter ) {
                                $delimiter = "\t"; // correct tabular delimiter.
                            }
                            if ( 'table' === $mode ) {
                                $this->import_table( $contents, $delimiter, $league_id, $season );
                            } elseif ( 'fixtures' === $mode ) {
                                $this->import_fixtures( $contents, $delimiter, $league_id, $season );
                            } elseif ( 'clubplayers' === $mode ) {
                                $this->import_club_players( $contents, $delimiter, $club );
                            } elseif ( 'players' === $mode ) {
                                $this->import_players( $contents, $delimiter );
                            } else {
                                $this->set_message( __( 'Type of data to upload not selected', 'racketmanager' ), true );
                            }
                        } else {
                            $this->set_message( __( 'Unable to read file contents', 'racketmanager' ), true );
                        }
                        if ( ! $wp_filesystem->delete( $new_file ) ) {
                            $this->set_message( __( 'Unable to delete file', 'racketmanager' ), true );
                        }
                    } else {
                        /* translators: %s: location of file */
                        $this->set_message( sprintf( __( 'The uploaded file could not be moved to %s.', 'racketmanager' ), ABSPATH . 'wp-content/uploads' ), true );
                    }
                }
            } else {
                $this->set_message( __( 'Unable to access file', 'racketmanager' ), true );
            }
        }
    }

    /**
     * Import table from CSV file
     *
     * @param array $contents array of file contents.
     * @param string $delimiter delimiter.
     * @param int $league_id league.
     * @param string $season season.
     */
    private function import_table( array $contents, string $delimiter, int $league_id, string $season ): void {
        $league       = get_league( $league_id );
        $i            = 0;
        $x            = 0;
        foreach ( $contents as $record ) {
            $line = explode( $delimiter, $record );
            // ignore header and empty lines.
            if ( $i > 0 && count( $line ) > 1 ) {
                $team    = $line[0];
                $team_id = $this->get_team_id( $team );
                if ( ! empty( $team_id ) ) {
                    $table_id = $league->add_team( $team_id, $season );
                    if ( $table_id ) {
                        $league_team = get_league_team( $table_id );
                        if ( $league_team ) {
                            $league_team->done_matches = $line[1] ?? 0;
                            $league_team->won_matches  = $line[2] ?? 0;
                            $league_team->draw_matches = $line[3] ?? 0;
                            $league_team->lost_matches = $line[4] ?? 0;
                            if ( isset( $line[5] ) ) {
                                if (str_contains($line[5], ':')) {
                                    $points_2 = explode( ':', $line[5] );
                                } else {
                                    $points_2 = array( $line[5], 0 );
                                }
                            } else {
                                $points_2 = array( 0, 0 );
                            }
                            $league_team->points_2_plus  = intval( $points_2[0] );
                            $league_team->points_2_minus = intval( $points_2[1] );
                            if ( isset( $line[6] ) ) {
                                if (str_contains($line[6], ':')) {
                                    $points = explode( ':', $line[6] );
                                } else {
                                    $points = array( $line[6], 0 );
                                }
                            } else {
                                $points = array( 0, 0 );
                            }
                            $league_team->points_plus  = floatval( $points[0] );
                            $league_team->points_minus = floatval( $points[1] );
                            $league_team->add_points   = intval( $line[7] ?? 0 );
                            $custom['sets_won']        = intval( $line[8] ?? 0 );
                            $custom['sets_allowed']    = intval( $line[9] ?? 0 );
                            $custom['games_won']       = intval( $line[10] ?? 0 );
                            $custom['games_allowed']   = intval( $line[11] ?? 0 );
                            $league_team->custom       = $custom;
                            $league_team->update();
                            ++$x;
                        }
                    }
                }
            }
            ++$i;
        }
        if ( ! empty( $i ) ) {
            $league->set_teams_rank( $season );
        }
        /* translators: %d: number of table entries imported */
        $this->set_message( sprintf( __( '%d Table Entries imported', 'racketmanager' ), $x ) );
    }

    /**
     * Import fixtures from file
     *
     * @param array $contents array of file contents.
     * @param string $delimiter delimiter.
     * @param int $league_id league.
     * @param string $season season.
     */
    private function import_fixtures( array $contents, string $delimiter, int $league_id, string $season ): void {
        $league = get_league( $league_id );
        $i      = 0;
        $x      = 0;
        foreach ( $contents as $record ) {
            $line = explode( $delimiter, $record );
            // ignore header and empty lines.
            if ( $i > 0 && count( $line ) > 1 ) {
                $match            = new stdClass();
                $match->league_id = $league->id;
                $date             = ( ! empty( $line[6] ) ) ? $line[0] . ' ' . $line[6] : $line[0] . ' 00:00';
                $match->match_day = $line[1] ?? '';
                $match->date      = trim( $date );
                $match->season    = $season;
                $match->home_team = $this->get_team_id( $line[2] );
                $match->away_team = $this->get_team_id( $line[3] );
                if ( ! empty( $match->home_team )  && ! empty( $match->away_team ) ) {
                    $match->location = $line[4] ?? '';
                    $match->group    = $line[5] ?? '';
                    $match = new Racketmanager_Match( $match );
                    if ( ! empty( $match->id ) ) {
                        ++$x;
                    }
                }
            }
            ++$i;
        }
        /* translators: %d: number of fixtures imported */
        $this->set_message( sprintf( __( '%d Fixtures imported', 'racketmanager' ), $x ) );
    }

    /**
     * Import players from file
     *
     * @param array $contents array of file contents.
     * @param string $delimiter delimiter.
     */
    private function import_players( array $contents, string $delimiter ): void {
        global $racketmanager;
        $error_messages = array();
        $i              = 0;
        $x              = 0;
        foreach ( $contents as $record ) {
            $line = explode( $delimiter, $record );
            // ignore header and empty lines.
            if ( $i > 0 && count( $line ) > 1 ) {
                $_POST['firstname']     = $line[0] ?? '';
                $_POST['surname']       = $line[1] ?? '';
                $_POST['gender']        = $line[2] ?? '';
                $_POST['btm']           = $line[3] ?? '';
                $_POST['email']         = $line[4] ?? '';
                $_POST['contactno']     = $line[5] ?? '';
                $_POST['year_of_birth'] = $line[6] ?? '';
                $player_valid           = $racketmanager->validate_player();
                if ( $player_valid[0] ) {
                    $new_player = $player_valid[1];
                    $player     = get_player( $new_player->user_login, 'login' );  // get player by login.
                    if ( ! $player ) {
                        $player = new Player( $new_player );
                        if ( ! empty( $player->id ) ) {
                            ++$x;
                        }
                    }
                } else {
                    $error_messages = $player_valid[2];
                    /* translators: %d: player line with error */
                    $message = sprintf( __( 'Error with player %d details', 'racketmanager' ), $i );
                    foreach ( $error_messages as $error_message ) {
                        $message .= '<br>' . $error_message;
                    }
                    $error_messages[] = $message;
                }
            }
            ++$i;
        }
        /* translators: %d: number of players imported */
        $message = sprintf( __( '%d Players imported', 'racketmanager' ), $x );
        foreach ( $error_messages as $error_message ) {
            $message .= '<br>' . $error_message;
        }
        $this->set_message( $message );
    }

    /**
     * Import club players from file
     *
     * @param array $contents array of file contents.
     * @param string $delimiter delimiter.
     * @param int $club club.
     */
    private function import_club_players( array $contents, string $delimiter, int $club ): void {
        global $racketmanager;
        $club           = get_club( $club );
        $i              = 0;
        $x              = 0;
        $error_messages = array();
        foreach ( $contents as $record ) {
            $line = explode( $delimiter, $record );
            // ignore header and empty lines.
            if ( $i > 0 && count( $line ) > 1 ) {
                $_POST['firstname']     = $line[0] ?? '';
                $_POST['surname']       = $line[1] ?? '';
                $_POST['gender']        = $line[2] ?? '';
                $_POST['btm']           = $line[3] ?? '';
                $_POST['email']         = $line[4] ?? '';
                $_POST['contactno']     = $line[5] ?? '';
                $_POST['year_of_birth'] = $line[6] ?? '';
                $player_valid           = $racketmanager->validate_player();
                if ( $player_valid[0] ) {
                    $new_player = $player_valid[1];
                    $club->register_player( $new_player );
                    ++$x;
                } else {
                    $error_messages = $player_valid[2];
                    /* translators: %d: player id */
                    $message = sprintf( __( 'Error with player %d details', 'racketmanager' ), $i );
                    foreach ( $error_messages as $error_message ) {
                        $message .= '<br>' . $error_message;
                    }
                    $error_messages[] = $message;
                }
            }
            ++$i;
        }
        /* translators: %d: number of players imported */
        $message = sprintf( __( '%d Club Players imported', 'racketmanager' ), $x );
        foreach ( $error_messages as $error_message ) {
            $message .= '<br>' . $error_message;
        }
        $this->set_message( $message );
    }
}
