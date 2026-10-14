<?php
/**
 * Import action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Import
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Import;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Duplicate_BTM_Exception;
use Racketmanager\Exceptions\Duplicate_Email_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Player_Already_Registered_Exception;
use Racketmanager\Exceptions\Player_Exists_Exception;
use Racketmanager\Exceptions\Registration_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Fixture_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Util\Util;
use stdClass;
use function Racketmanager\get_league;
use function Racketmanager\get_league_team;

/**
 * Handles CSV datasets import operations for leagues, fixtures, players, and club player registrations.
 */
readonly final class Import_Action_Dispatcher implements Import_Action_Dispatcher_Interface {

    /**
     * @param Action_Guard_Interface $action_guard
     * @param Fixture_Service        $fixture_service
     * @param Player_Service         $player_service
     * @param Registration_Service   $registration_service
     * @param object|null            $racketmanager
     */
    public function __construct(
        private Action_Guard_Interface $action_guard,
        private Fixture_Service $fixture_service,
        private Player_Service $player_service,
        private Registration_Service $registration_service,
        private ?object $racketmanager = null,
    ) {
    }

    /**
     * @param array<string, mixed>      $query Typically $_GET
     * @param array<string, mixed>      $post  Typically $_POST
     * @param array<string, mixed>|null $files Typically $_FILES
     */
    public function handle( array $query, array $post, ?array $files = null ): Action_Result_DTO {
        if ( ! isset( $post['import'] ) ) {
            return new Action_Result_DTO();
        }

        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_import-datasets', 'import_leagues' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $league_id = isset( $post['league_id'] ) ? intval( $post['league_id'] ) : null;
        $season    = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null;
        $club      = isset( $post['club'] ) ? intval( $post['club'] ) : 0;
        $delimiter = isset( $post['delimiter'] ) ? strval( $post['delimiter'] ) : 'TAB';
        $mode      = isset( $post['mode'] ) ? sanitize_text_field( wp_unslash( strval( $post['mode'] ) ) ) : '';

        $resolved_files = $files['racketmanager_import'] ?? [];
        if ( ! is_array( $resolved_files ) ) {
            $resolved_files = array();
        }

        return $this->import( $resolved_files, $delimiter, $mode, $league_id, $season, $club );
    }

    /**
     * Import data from CSV file.
     *
     * @param array<string, mixed> $file CSV file.
     * @param string               $delimiter Delimiter.
     * @param string               $mode 'table' | 'fixtures' | 'players' | 'clubplayers'.
     * @param int|null             $league_id League ID.
     * @param string|null          $season Season string.
     * @param int                  $club Club ID.
     */
    private function import( array $file, string $delimiter, string $mode, ?int $league_id, ?string $season, int $club = 0 ): Action_Result_DTO {
        if ( empty( $file['name'] ) ) {
            return new Action_Result_DTO(
                message: __( 'No file specified for upload', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        if ( ! isset( $file['size'] ) || 0 === intval( $file['size'] ) ) {
            return new Action_Result_DTO(
                message: __( 'Upload file is empty', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $access_type = function_exists( 'get_filesystem_method' ) ? get_filesystem_method() : 'direct';
        if ( 'direct' !== $access_type ) {
            return new Action_Result_DTO(
                message: __( 'Unable to access file', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $credentials = function_exists( 'request_filesystem_credentials' )
            ? request_filesystem_credentials( site_url() . '/wp-admin/', '', false, false, array() )
            : true;

        if ( function_exists( 'WP_Filesystem' ) && ! WP_Filesystem( $credentials ) ) {
            return new Action_Result_DTO(
                message: __( 'Unable to access file system', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        global $wp_filesystem;
        if ( ! $wp_filesystem ) {
            return new Action_Result_DTO(
                message: __( 'Unable to access file system', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $new_file = Util::get_file_path( strval( $file['name'] ) );
        if ( ! $wp_filesystem->copy( strval( $file['tmp_name'] ?? '' ), $new_file, true ) ) {
            return new Action_Result_DTO(
                message: sprintf( __( 'The uploaded file could not be moved to %s.', 'racketmanager' ), ABSPATH . 'wp-content/uploads' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $contents = $wp_filesystem->get_contents_array( $new_file );
            if ( ! $contents ) {
                return new Action_Result_DTO(
                    message: __( 'Unable to read file contents', 'racketmanager' ),
                    message_type: Admin_Message_Type::ERROR,
                );
            }

            if ( empty( $delimiter ) ) {
                return new Action_Result_DTO(
                    message: __( 'Delimiter not specified', 'racketmanager' ),
                    message_type: Admin_Message_Type::ERROR,
                );
            }

            if ( 'TAB' === $delimiter ) {
                $delimiter = "\t"; // correct tabular delimiter.
            }

            $result = match ( $mode ) {
                'table'       => $this->import_table( $contents, $delimiter, (int) $league_id, strval( $season ) ),
                'fixtures'    => $this->import_fixtures( $contents, $delimiter, (int) $league_id, strval( $season ) ),
                'clubplayers' => $this->import_club_players( $contents, $delimiter, $club ),
                'players'     => $this->import_players( $contents, $delimiter ),
                default       => new Action_Result_DTO(
                    message: __( 'Type of data to upload not selected', 'racketmanager' ),
                    message_type: Admin_Message_Type::ERROR,
                ),
            };

            return $result;
        } finally {
            $wp_filesystem->delete( $new_file );
        }
    }

    /**
     * Import table from a CSV file.
     *
     * @param array<string> $contents Array of file contents.
     * @param string        $delimiter Delimiter.
     * @param int           $league_id League ID.
     * @param string        $season Season.
     */
    public function import_table( array $contents, string $delimiter, int $league_id, string $season ): Action_Result_DTO {
        $league = get_league( $league_id );
        if ( ! $league ) {
            return new Action_Result_DTO(
                message: __( 'League not found', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $i = 0;
        $x = 0;
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
                            $league_team->done_matches = (int) ( $line[1] ?? 0 );
                            $league_team->won_matches  = (int) ( $line[2] ?? 0 );
                            $league_team->draw_matches = (int) ( $line[3] ?? 0 );
                            $league_team->lost_matches = (int) ( $line[4] ?? 0 );
                            if ( isset( $line[5] ) ) {
                                if ( str_contains( strval( $line[5] ), ':' ) ) {
                                    $points_2 = explode( ':', strval( $line[5] ) );
                                } else {
                                    $points_2 = array( $line[5], 0 );
                                }
                            } else {
                                $points_2 = array( 0, 0 );
                            }
                            $league_team->points_2_plus  = intval( $points_2[0] );
                            $league_team->points_2_minus = intval( $points_2[1] );
                            if ( isset( $line[6] ) ) {
                                if ( str_contains( strval( $line[6] ), ':' ) ) {
                                    $points = explode( ':', strval( $line[6] ) );
                                } else {
                                    $points = array( $line[6], 0 );
                                }
                            } else {
                                $points = array( 0, 0 );
                            }
                            $league_team->points_plus  = floatval( $points[0] );
                            $league_team->points_minus = floatval( $points[1] );
                            $league_team->add_points   = intval( $line[7] ?? 0 );
                            $custom                    = array();
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

        if ( ! empty( $i ) && method_exists( $league, 'set_teams_rank' ) ) {
            $league->set_teams_rank( $season );
        }

        /* translators: %d: number of table entries imported */
        return new Action_Result_DTO(
            message: sprintf( __( '%d Table Entries imported', 'racketmanager' ), $x ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }

    /**
     * Import fixtures from a file.
     *
     * @param array<string> $contents Array of file contents.
     * @param string        $delimiter Delimiter.
     * @param int           $league_id League ID.
     * @param string        $season Season.
     */
    public function import_fixtures( array $contents, string $delimiter, int $league_id, string $season ): Action_Result_DTO {
        $league = get_league( $league_id );
        if ( ! $league ) {
            return new Action_Result_DTO(
                message: __( 'League not found', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $i = 0;
        $x = 0;
        foreach ( $contents as $record ) {
            $line = explode( $delimiter, $record );
            // ignore header and empty lines.
            if ( $i > 0 && count( $line ) > 1 ) {
                $match_data            = new stdClass();
                $match_data->league_id = $league->id;
                $date                  = ( ! empty( $line[6] ) ) ? $line[0] . ' ' . $line[6] : $line[0] . ' 00:00';
                $match_data->match_day = $line[1] ?? '';
                $match_data->date      = trim( $date );
                $match_data->season    = $season;
                $match_data->home_team = $this->get_team_id( strval( $line[2] ?? '' ) );
                $match_data->away_team = $this->get_team_id( strval( $line[3] ?? '' ) );
                if ( ! empty( $match_data->home_team ) && ! empty( $match_data->away_team ) ) {
                    $match_data->location = $line[4] ?? '';
                    $match_data->group    = $line[5] ?? '';
                    $fixture              = Fixture_Factory::from_object( $match_data );
                    $fixture              = $this->fixture_service->create_fixture( $fixture, $league );
                    if ( ! empty( $fixture->get_id() ) ) {
                        ++$x;
                    }
                }
            }
            ++$i;
        }

        /* translators: %d: number of fixtures imported */
        return new Action_Result_DTO(
            message: sprintf( __( '%d Fixtures imported', 'racketmanager' ), $x ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }

    /**
     * Import players from a file without modifying global $_POST.
     *
     * @param array<string> $contents Array of file contents.
     * @param string        $delimiter Delimiter.
     */
    public function import_players( array $contents, string $delimiter ): Action_Result_DTO {
        $error_messages = array();
        $i              = 0;
        $x              = 0;
        foreach ( $contents as $record ) {
            $line = explode( $delimiter, $record );
            // ignore header and empty lines.
            if ( $i > 0 && count( $line ) > 1 ) {
                $player_data = $this->parse_player_row( $line );
                try {
                    $player = $this->player_service->add_new_player( $player_data );
                    if ( is_wp_error( $player ) ) {
                        $player_errors = $player->get_error_messages();
                        /* translators: %d: player line with error */
                        $message = sprintf( __( 'Error with player %d details', 'racketmanager' ), $i );
                        foreach ( $player_errors as $error_message ) {
                            $message .= '<br>' . $error_message;
                        }
                        $error_messages[] = $message;
                    } else {
                        ++$x;
                    }
                } catch ( Player_Exists_Exception|Duplicate_Email_Exception|Duplicate_BTM_Exception $e ) {
                    $error_messages[] = $e->getMessage();
                }
            }
            ++$i;
        }

        /* translators: %d: number of players imported */
        $message = sprintf( __( '%d Players imported', 'racketmanager' ), $x );
        foreach ( $error_messages as $error_message ) {
            $message .= '<br>' . $error_message;
        }

        $type = ( 0 === $x && ! empty( $error_messages ) )
            ? Admin_Message_Type::ERROR
            : ( ! empty( $error_messages ) ? Admin_Message_Type::WARNING : Admin_Message_Type::SUCCESS );

        return new Action_Result_DTO(
            message: $message,
            message_type: $type,
        );
    }

    /**
     * Import club players from a file without modifying global $_POST.
     *
     * @param array<string> $contents Array of file contents.
     * @param string        $delimiter Delimiter.
     * @param int           $club_id Club ID.
     */
    public function import_club_players( array $contents, string $delimiter, int $club_id ): Action_Result_DTO {
        $i              = 0;
        $x              = 0;
        $error_messages = array();
        $user_id        = function_exists( 'wp_get_current_user' ) ? ( wp_get_current_user()->ID ?? 0 ) : 0;

        foreach ( $contents as $record ) {
            $line = explode( $delimiter, $record );
            // ignore header and empty lines.
            if ( $i > 0 && count( $line ) > 1 ) {
                $player_data = $this->parse_player_row( $line );
                try {
                    $response = $this->registration_service->register_player_to_club( $club_id, $user_id, $player_data );
                    if ( is_wp_error( $response ) ) {
                        $player_messages = $response->get_error_messages();
                        /* translators: %d: player line with error */
                        $message = sprintf( __( 'Error with player %d details', 'racketmanager' ), $i );
                        foreach ( $player_messages as $error_message ) {
                            $message .= '<br>' . $error_message;
                        }
                        $error_messages[] = $message;
                    } else {
                        ++$x;
                    }
                } catch ( Club_Not_Found_Exception|Player_Already_Registered_Exception|Registration_Not_Found_Exception $e ) {
                    $error_messages[] = $e->getMessage();
                }
            }
            ++$i;
        }

        /* translators: %d: number of players imported */
        $message = sprintf( __( '%d Club Players imported', 'racketmanager' ), $x );
        foreach ( $error_messages as $error_message ) {
            $message .= '<br>' . $error_message;
        }

        $type = ( 0 === $x && ! empty( $error_messages ) )
            ? Admin_Message_Type::ERROR
            : ( ! empty( $error_messages ) ? Admin_Message_Type::WARNING : Admin_Message_Type::SUCCESS );

        return new Action_Result_DTO(
            message: $message,
            message_type: $type,
        );
    }

    /**
     * Map a CSV row to a player data array without mutating global $_POST.
     *
     * @param array<int, string> $line
     * @return array<string, string>
     */
    private function parse_player_row( array $line ): array {
        return array(
            'firstname'     => $line[0] ?? '',
            'surname'       => $line[1] ?? '',
            'gender'        => $line[2] ?? '',
            'btm'           => $line[3] ?? '',
            'email'         => $line[4] ?? '',
            'contactno'     => $line[5] ?? '',
            'year_of_birth' => $line[6] ?? '',
        );
    }

    /**
     * Resolve team ID from team name.
     */
    private function get_team_id( string $team ): ?int {
        if ( $this->racketmanager && method_exists( $this->racketmanager, 'get_team_id' ) ) {
            return $this->racketmanager->get_team_id( $team );
        }
        global $racketmanager;
        if ( $racketmanager && method_exists( $racketmanager, 'get_team_id' ) ) {
            return $racketmanager->get_team_id( $team );
        }
        return null;
    }
}
