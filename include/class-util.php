<?php
/**
 * RacketManager_Util API: RacketManager-util class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Util
 */

namespace Racketmanager;

use NumberFormatter;
use stdClass;

defined( 'ABSPATH' ) || die( 'Access denied !' );
/**
 * Helper and Util functions
 *
 * @package racketmanager
 * @subpackage include
 * @since 1.0.0
 * @author PaulMoffat
 */
class Util {

    /**
     * Get upload directory
     *
     * @param false|string $file file name.
     * @return string upload path
     */
    public static function get_file_path( false|string $file = false ): string {
        $base = WP_CONTENT_DIR . '/uploads/leagues';

        if ( $file ) {
            return $base . '/' . basename( $file );
        } else {
            return $base;
        }
    }

    /**
     * Add pages to database
     *
     * @param array $page_definitions page definition array.
     */
    public static function add_racketmanager_page( array $page_definitions ): void {
        foreach ( $page_definitions as $slug => $page ) {

            // Check that the page doesn't exist already.
            if ( ! is_page( $slug ) ) {
                $page_template = $page['page_template'];
                // Add the page using the data from the array above.
                $page    = array(
                    'post_content'   => $page['content'],
                    'post_name'      => $slug,
                    'post_title'     => $page['title'],
                    'post_status'    => 'publish',
                    'post_type'      => 'page',
                    'ping_status'    => 'closed',
                    'comment_status' => 'closed',
                    'page_template'  => $page_template,
                );
                $page_id = wp_insert_post( $page );
                if ( $page_id ) {
                    $page_name = sanitize_title_with_dashes( $page['post_title'] );
                    $option    = 'racketmanager_page_' . $page_name . '_id';
                    // Only update this option if `wp_insert_post()` was successful.
                    update_option( $option, $page_id );
                }
            }
        }
    }
    /**
     * Get event types
     *
     * @return array event types.
     */
    public static function get_event_types(): array {
        $event_types       = array();
        $event_types['BS'] = __( 'Boys Singles', 'racketmanager' );
        $event_types['GS'] = __( 'Girls Singles', 'racketmanager' );
        $event_types['WS'] = __( 'Ladies Singles', 'racketmanager' );
        $event_types['MS'] = __( 'Mens Singles', 'racketmanager' );
        $event_types['OS'] = __( 'Open Singles', 'racketmanager' );
        $event_types['BD'] = __( 'Boys Doubles', 'racketmanager' );
        $event_types['GD'] = __( 'Girls Doubles', 'racketmanager' );
        $event_types['WD'] = __( 'Ladies Doubles', 'racketmanager' );
        $event_types['MD'] = __( 'Mens Doubles', 'racketmanager' );
        $event_types['XD'] = __( 'Mixed Doubles', 'racketmanager' );
        $event_types['LD'] = __( 'The League', 'racketmanager' );
        return $event_types;
    }

    /**
     * Get event type
     *
     * @param string $type event.
     * @return string event description.
     */
    public static function get_event_type( string $type ): string {
        $event_types = self::get_event_types();
        if ( empty( $event_types[ $type ] ) ) {
            return __( 'Unknown', 'racketmanager' );
        } else {
            return $event_types[ $type ];
        }
    }
    /**
     * Get available league standing status
     *
     * @return array
     */
    public static function get_standing_statuses(): array {
        $standing_status       = array();
        $standing_status['C']  = __( 'Champions', 'racketmanager' );
        $standing_status['P1'] = __( 'Promoted in first place', 'racketmanager' );
        $standing_status['P2'] = __( 'Promoted in second place', 'racketmanager' );
        $standing_status['P3'] = __( 'Promoted in third place', 'racketmanager' );
        $standing_status['P4'] = __( 'Promoted in fourth place', 'racketmanager' );
        $standing_status['W1'] = __( 'League winners but league locked', 'racketmanager' );
        $standing_status['W2'] = __( 'Second place but league locked', 'racketmanager' );
        $standing_status['W3'] = __( 'Third place but league locked', 'racketmanager' );
        $standing_status['RB'] = __( 'Relegated in bottom place', 'racketmanager' );
        $standing_status['R2'] = __( 'Relegated in second bottom place', 'racketmanager' );
        $standing_status['RQ'] = __( 'Relegated by request', 'racketmanager' );
        $standing_status['RT'] = __( 'Relegated as team in division above', 'racketmanager' );
        $standing_status['BT'] = __( 'Finished bottom but not relegated', 'racketmanager' );
        $standing_status['NT'] = __( 'New team', 'racketmanager' );
        $standing_status['W']  = __( 'Withdrawn', 'racketmanager' );
        $standing_status['+']  = __( 'Move up', 'racketmanager' );
        $standing_status['-']  = __( 'Move down', 'racketmanager' );
        $standing_status['=']  = __( 'No movement', 'racketmanager' );
        return $standing_status;
    }

    /**
     * Get available league standing status
     *
     * @param string|null $status status value.
     *
     * @return string
     */
    public static function get_standing_status( ?string $status = null ): string {
        $standing_statuses = self::get_standing_statuses();
        return empty( $standing_statuses[ $status ] ) ? __( 'Unknown', 'racketmanager' ) : $standing_statuses[ $status ];
    }
    /**
     * Get available competition types
     *
     * @return array
     */
    public static function get_competition_types(): array {
        $competition_types               = array();
        $competition_types['cup']        = __( 'cup', 'racketmanager' );
        $competition_types['league']     = __( 'league', 'racketmanager' );
        $competition_types['tournament'] = __( 'tournament', 'racketmanager' );
        return $competition_types;
    }
    /**
     * Get competition type
     *
     * @param string|null $type competition type.
     * @return string|bool competition type text
     */
    public static function get_competition_type( ?string $type ): string|bool {
        $competition_types = self::get_competition_types();
        return empty( $competition_types[ $type ] ) ? false : $competition_types[ $type ];
    }

    /**
     * Get available league modes
     *
     * @return array
     */
    public static function get_modes(): array {
        $modes                 = array();
        $modes['default']      = __( 'Default', 'racketmanager' );
        $modes['championship'] = __( 'Championship', 'racketmanager' );
        /**
         * Fired when league modes are built
         *
         * @param array $modes
         * @return array
         * @category wp-filter
         */
        return apply_filters( 'racketmanager_modes', $modes );
    }

    /**
     * Get available entry types
     *
     * @return array
     */
    public static function get_entry_types(): array {
        $entry_types           = array();
        $entry_types['team']   = __( 'Team', 'racketmanager' );
        $entry_types['player'] = __( 'Player', 'racketmanager' );
        return $entry_types;
    }
    /**
     * Get array of supported scoring rules
     *
     * @return array
     */
    public static function get_scoring_types(): array {
        $scoring_types        = array();
        $scoring_types['F4']  = __( 'Fast 4', 'racketmanager' );
        $scoring_types['FM']  = __( 'Fast 4 with match tie break', 'racketmanager' );
        $scoring_types['PR']  = __( 'Pro', 'racketmanager' );
        $scoring_types['TB']  = __( 'Tie break', 'racketmanager' );
        $scoring_types['TBM'] = __( 'Tie break with match tie break in final', 'racketmanager' );
        $scoring_types['TM']  = __( 'Tie break with match tie break', 'racketmanager' );
        $scoring_types['TP']  = __( 'Tie break with tie break playoff', 'racketmanager' );
        $scoring_types['MP']  = __( 'Tie break with match tie break playoff', 'racketmanager' );
        $scoring_types['MPL'] = __( 'Tie break with match tie break playoff in 2nd Leg', 'racketmanager' );
        return $scoring_types;
    }
    /**
     * Get array of supported point rules
     *
     * @return array
     */
    public static function get_point_rules(): array {
        $rules           = array();
        $rules['manual'] = __( 'Update Standings Manually', 'racketmanager' );
        $rules['one']    = __( 'One-Point-Rule', 'racketmanager' );
        $rules['two']    = __( 'Two-Point-Rule', 'racketmanager' );
        $rules['three']  = __( 'Three-Point-Rule', 'racketmanager' );
        $rules['score']  = __( 'Score', 'racketmanager' );
        $rules['user']   = __( 'User defined', 'racketmanager' );
        /**
         * Fired when league point rules are built
         *
         * @param array $rules
         * @return array
         * @category wp-filter
         */
        $rules = apply_filters( 'racketmanager_point_rules_list', $rules );
        asort( $rules );
        return $rules;
    }
    /**
     * Get available point formats
     *
     * @return array
     */
    public static function get_point_formats(): array {
        $point_formats                = array();
        $point_formats['%s:%s']       = '%s:%s';
        $point_formats['%s']          = '%s';
        $point_formats['%d:%d']       = '%d:%d';
        $point_formats['%d - %d']     = '%d - %d';
        $point_formats['%d']          = '%d';
        $point_formats['%.1f:%.1f']   = '%f:%f';
        $point_formats['%.1f - %.1f'] = '%f - %f';
        $point_formats['%.1f']        = '%f';
        /**
         * Fired when league point formats are built
         *
         * @param array $point_formats
         * @return array
         * @category wp-filter
         */
        return apply_filters( 'racketmanager_point_formats', $point_formats );
    }
    /**
     * Get list of players by initial function
     *
     * @param array $players list of players.
     * @return array list of players by initial
     */
    public static function get_players_list( array $players ): array {
        $player_list = array();
        $firstname   = array_column( $players, 'firstname' );
        $surname     = array_column( $players, 'surname' );
        array_multisort( $surname, SORT_ASC, $firstname, SORT_ASC, $players );
        foreach ( $players as $player ) {
            $key = strtoupper( substr( $player->surname, 0, 1 ) );
            if ( false === array_key_exists( $key, $player_list ) ) {
                $player_list[ $key ] = array();
            }
            $player->index         = $player->surname . ', ' . $player->firstname;
            $player_list[ $key ][] = $player;
        }
        return $player_list;
    }
    /**
     * Get set type function
     *
     * @param string $scoring scoring format.
     * @param string|null $round round.
     * @param int $num_sets number of sets.
     * @param int $set set number.
     * @param int|null $rubber_number rubber number.
     * @param int|null $num_rubbers number of rubbers.
     * @param int|null $leg leg number.
     * @return string set type.
     */
    public static function get_set_type( string $scoring, ?string $round = null, int $num_sets = 99, int $set = 1, ?int $rubber_number = null, ?int $num_rubbers = null, ?int $leg = null ): string {
        if ( 'TB' === $scoring ) {
            $set_type = 'TB';
        } elseif ( 'TBM' === $scoring ) {
            if ( 'final' === $round ) {
                if ( $num_sets === $set ) {
                    $set_type = 'MTB';
                } else {
                    $set_type = 'TB';
                }
            } else {
                $set_type = 'TB';
            }
        } elseif ( 'TM' === $scoring ) {
            if ( $num_sets === $set ) {
                $set_type = 'MTB';
            } else {
                $set_type = 'TB';
            }
        } elseif ( 'F4' === $scoring ) {
            $set_type = 'fast4';
        } elseif ( 'FM' === $scoring ) {
            if ( $num_sets === $set ) {
                $set_type = 'MTB';
            } else {
                $set_type = 'fast4';
            }
        } elseif ( 'PR' === $scoring ) {
            $set_type = 'pro';
        } elseif ( 'TP' === $scoring ) {
            $set_type = 'TB';
            if ( $rubber_number && $rubber_number === $num_rubbers && 1 !== $set ) {
                $set_type = 'null';
            }
        } elseif ( 'MP' === $scoring ) {
            if ( $num_sets === $set ) {
                $set_type = 'MTB';
            } else {
                $set_type = 'TB';
            }
            if ( $rubber_number && intval( $num_rubbers ) === $rubber_number ) {
                $set_type = 'MTB';
                if ( 1 !== $set ) {
                    $set_type = 'null';
                }
            }
        } elseif ( 'MPL' === $scoring ) {
            if ( $num_sets === $set ) {
                $set_type = 'MTB';
            } else {
                $set_type = 'TB';
            }
            if ( ( 2 === $leg || 'final' === $round ) && $rubber_number && intval( $num_rubbers ) === $rubber_number ) {
                $set_type = 'MTB';
                if ( 1 !== $set ) {
                    $set_type = 'null';
                }
            }
        } else {
            $set_type = 'null';
        }
        return $set_type;
    }
    /**
     * Get set info function
     *
     * @param string $set_type set type.
     * @return object set information.
     */
    public static function get_set_info( string $set_type ): object {
        $tiebreak_allowed  = false;
        $tiebreak_set      = 6;
        if ( 'TB' === $set_type ) {
            $max_win          = 7;
            $min_win          = 6;
            $max_loss         = $max_win - 2;
            $min_loss         = $min_win - 2;
            $tiebreak_allowed = true;
        } elseif ( 'MTB' === $set_type ) {
            $max_win  = 99;
            $min_win  = 10;
            $max_loss = $max_win - 2;
            $min_loss = $min_win - 2;
        } elseif ( 'fast4' === $set_type ) {
            $max_win          = 4;
            $min_win          = 4;
            $tiebreak_set     = 3;
            $max_loss         = $max_win - 2;
            $min_loss         = $min_win - 2;
            $tiebreak_allowed = true;
        } elseif ( 'standard' === $set_type ) {
            $max_win  = 99;
            $min_win  = 6;
            $max_loss = $max_win - 2;
            $min_loss = $min_win - 2;
        } elseif ( 'pro' === $set_type ) {
            $max_win      = 9;
            $min_win      = 8;
            $max_loss     = $max_win - 2;
            $min_loss     = $min_win - 2;
            $tiebreak_set = 7;
        } else {
            $max_win  = 0;
            $min_win  = 0;
            $max_loss = 0;
            $min_loss = 0;
        }
        $set_info                    = new stdClass();
        $set_info->set_type          = $set_type;
        $set_info->max_win           = $max_win;
        $set_info->min_win           = $min_win;
        $set_info->max_loss          = $max_loss;
        $set_info->min_loss          = $min_loss;
        $set_info->tiebreak_set      = $tiebreak_set;
        $set_info->tiebreak_allowed  = $tiebreak_allowed;
        $set_info->tiebreak_required = false;
        return $set_info;
    }
    /**
     * Get match status values function
     *
     * @return array
     */
    private static function get_match_statuses(): array {
        $match_status      = array();
        $match_status['0'] = __( 'Complete', 'racketmanager' );
        $match_status['1'] = __( 'Not played', 'racketmanager' );
        $match_status['2'] = __( 'Retired', 'racketmanager' );
        $match_status['3'] = __( 'Not played', 'racketmanager' );
        $match_status['5'] = __( 'Rescheduled', 'racketmanager' );
        $match_status['6'] = __( 'Abandoned', 'racketmanager' );
        $match_status['7'] = __( 'Withdrawn', 'racketmanager' );
        $match_status['8'] = __( 'Cancelled', 'racketmanager' );
        $match_status['9'] = __( 'Invalid', 'racketmanager' );
        return $match_status;
    }
    /**
     * Get match status value function
     *
     * @param int $status status.
     * @return string status text
     */
    public static function get_match_status( int $status ): string {
        $match_statuses = self::get_match_statuses();
        return empty( $match_statuses[ $status ] ) ? __( 'Unknown', 'racketmanager' ) : $match_statuses[ $status ];
    }
    /**
     * Get match status code function
     *
     * @param mixed $status_value status description.
     * @return int
     */
    public static function get_match_status_code( mixed $status_value ): int {
        $match_statuses = self::get_match_statuses();
        $status         = array_search( ucwords( $status_value ), $match_statuses, true );
        if ( false === $status ) {
            $status = 0;
        }
        return intval( $status );
    }
    /**
     * Get match days function
     *
     * @return array of match days
     */
    public static function get_match_days(): array {
        $match_days      = array();
        $match_days['0'] = __( 'Monday', 'racketmanager' );
        $match_days['1'] = __( 'Tuesday', 'racketmanager' );
        $match_days['2'] = __( 'Wednesday', 'racketmanager' );
        $match_days['3'] = __( 'Thursday', 'racketmanager' );
        $match_days['4'] = __( 'Friday', 'racketmanager' );
        $match_days['5'] = __( 'Saturday', 'racketmanager' );
        $match_days['6'] = __( 'Sunday', 'racketmanager' );
        return $match_days;
    }
    /**
     * Get match day number from day name function
     *
     * @param string $match_day match day name.
     * @return int match day number
     */
    public static function get_match_day_number( string $match_day ): int {
        $match_days = self::get_match_days();
        $day        = array_search( $match_day, $match_days, true );
        if ( false === $day ) {
            $day = 0;
        }
        return intval( $day );
    }
    /**
     * Get match day name from day number function
     *
     * @param string $match_day_num match day number.
     * @return string match day name
     */
    public static function get_match_day( string $match_day_num ): string {
        $match_days = self::get_match_days();
        return empty( $match_days[ intval( $match_day_num ) ] ) ? __( 'Unknown', 'racketmanager' ) : $match_days[ $match_day_num ];
    }
    /**
     * Get users for favourite
     *
     * @param string $type type of favourite.
     * @param string $key key of favourite.
     * @return array list of users
     */
    public static function get_users_for_favourite( string $type, string $key ): array {
        return get_users(
            array(
                'meta_key'   => 'favourite-' . $type, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                'meta_value' => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                'fields'     => 'ids',
            )
        );
    }
    /**
     * Get message type function
     *
     * @param int $status status.
     * @return string status text
     */
    public static function get_message_type( int $status ): string {
        return match ($status) {
            0 => __('read', 'racketmanager'),
            1 => __('unread', 'racketmanager'),
            default => __('Unknown', 'racketmanager'),
        };
    }
    /**
     * Get key of final depending on number of teams
     *
     * @param int $num_teams number of teams in round.
     * @return string key
     */
    public static function get_final_key( int $num_teams ): string {
        if ( 2 === $num_teams ) {
            $key = 'final';
        } elseif ( 4 === $num_teams ) {
            $key = 'semi';
        } elseif ( 8 === $num_teams ) {
            $key = 'quarter';
        } else {
            $key = 'last-' . $num_teams;
        }
        return $key;
    }
    /**
     * Get name of final depending on number of teams
     *
     * @param false|string $key final key.
     * @return string|boolean name of the round
     */
    public static function get_final_name( false|string $key = false ): bool|string {
        if ( ! empty( $key ) ) {
            if ( 'final' === $key ) {
                $round = __( 'Final', 'racketmanager' );
            } elseif ( 'third' === $key ) {
                $round = __( 'Third Place', 'racketmanager' );
            } elseif ( 'semi' === $key ) {
                $round = __( 'Semi Final', 'racketmanager' );
            } elseif ( 'quarter' === $key ) {
                $round = __( 'Quarter Final', 'racketmanager' );
            } else {
                $tmp = explode( '-', $key );
                /* translators: %d: round number of teams in round */
                $round = sprintf( __( 'Round of %d', 'racketmanager' ), $tmp[1] );
            }
            return $round;
        } else {
            return false;
        }
    }
    /**
     * Get match types function
     *
     * @return array of match types
     */
    public static function get_match_types(): array {
        $match_types      = array();
        $match_types['S'] = __( 'Singles', 'racketmanager' );
        $match_types['D'] = __( 'Doubles', 'racketmanager' );
        return $match_types;
    }
    /**
     * Get match type key from name function
     *
     * @param string $match_type match type name.
     * @return string $key match type key.
     */
    public static function get_match_type_key( string $match_type ): string {
        $match_types = self::get_match_types();
        return array_search( $match_type, $match_types, true );
    }

    /**
     * Get match type function
     *
     * @param string $match_type match type name.
     * @return false|string
     */
    public static function get_match_type( string $match_type ): false|string {
        $match_types = self::get_match_types();
        return $match_types[ $match_type ] ?? false;
    }
    /**
     * Clear scheduled event function
     *
     * @param string $name name of scheduled event.
     * @param array $args array of event arguments.
     * @return void
     */
    public static function clear_scheduled_event( string $name, array $args ): void {
        if ( wp_next_scheduled( $name, $args ) ) {
            wp_clear_scheduled_hook( $name, $args );
        }
    }
    /**
     * Calculate championship rating function
     *
     * @param object $match match object.
     * @param int $team_id team id.
     * @return int
     */
    public static function calculate_championship_rating( object $match, int $team_id ): int {
        $points = 0;
        if ( isset( $match->league->event->age_limit ) ) {
            if ( $match->league->event->age_limit >= 30 ) {
                $event_points = 0.25;
            } elseif ( 16 === $match->league->event->age_limit ) {
                $event_points = 0.4;
            } elseif ( 14 === $match->league->event->age_limit ) {
                $event_points = 0.25;
            } elseif ( 12 === $match->league->event->age_limit ) {
                $event_points = 0.15;
            } else {
                $event_points = 1;
            }
        } else {
            $event_points = 1;
        }
        if ( empty( $match->leg ) || 2 === $match->leg ) {
            if ( empty( $match->leg ) ) {
                $winner_id = $match->winner_id;
                $loser_id  = $match->loser_id;
            } else {
                $winner_id = $match->winner_id_tie;
                $loser_id  = $match->loser_id_tie;
            }
            $first_round = $match->league->championship->get_final_keys( 1 );
            switch ( $match->final_round ) {
                case 'final':
                    if ( $winner_id === $team_id ) {
                        if ( $match->league->championship->is_consolation ) {
                            $base_points = 88;
                        } else {
                            $base_points = 300;
                        }
                    } elseif ( $loser_id === $team_id ) {
                        if ( $match->league->championship->is_consolation ) {
                            $base_points = 80;
                        } else {
                            $base_points = 240;
                        }
                    }
                    break;
                case 'semi':
                    if ( $loser_id === $team_id ) {
                        if ( $match->league->championship->is_consolation ) {
                            $base_points = 72;
                        } else {
                            $base_points = 180;
                        }
                    }
                    break;
                case 'quarter':
                    if ( $loser_id === $team_id ) {
                        if ( $match->league->championship->is_consolation ) {
                            $base_points = 56;
                        } else {
                            $base_points = 120;
                        }
                    }
                    break;
                case 'last-16':
                    if ( $loser_id === $team_id ) {
                        if ( $match->league->championship->is_consolation ) {
                            $base_points = 24;
                        } else {
                            $base_points = 88;
                        }
                    }
                    break;
                case 'last-32':
                    if ( $loser_id === $team_id ) {
                        if ( $match->league->championship->is_consolation ) {
                            $base_points = 24;
                        } else {
                            $base_points = 72;
                        }
                    }
                    break;
                case 'last-64':
                    if ( $loser_id === $team_id ) {
                        if ( $match->league->championship->is_consolation ) {
                            $base_points = 0;
                        } else {
                            $base_points = 20;
                        }
                    }
                    break;
                default:
                    $base_points = 0;
                    break;
            }
            if ( $first_round === $match->final_round ) {
                $base_points = 0;
            }
            if ( ! empty( $base_points ) ) {
                $last_year    = gmdate( 'Y-m-d H:i:s', strtotime( '-1 year' ) );
                $point_adjust = 1;
                if ( $match->date < $last_year ) {
                    $point_adjust = 0.5;
                }
                $points = ceil( $base_points * $event_points * $point_adjust );
            }
        }
        return $points;
    }
    /**
     * Get event grades function
     *
     * @return array of event grades
     */
    public static function get_event_grades(): array {
        $event_grades      = array();
        $event_grades['1'] = __( 'National', 'racketmanager' );
        $event_grades['2'] = __( 'National', 'racketmanager' );
        $event_grades['3'] = __( 'Regional', 'racketmanager' );
        $event_grades['4'] = __( 'County', 'racketmanager' );
        $event_grades['5'] = __( 'Local', 'racketmanager' );
        $event_grades['6'] = __( 'Match plays', 'racketmanager' );
        $event_grades['7'] = __( 'Internal', 'racketmanager' );
        $event_grades['U'] = __( 'Ungraded', 'racketmanager' );
        return $event_grades;
    }

    /**
     * Get match status values function
     *
     * @param string|null $age_range age range.
     *
     * @return array
     */
    public static function get_age_limits( ?string $age_range = null ): array {
        $age_limits         = array();
        if ( empty( $age_range ) || 'open' == $age_range ) {
            $age_limits['open'] = __( 'Open', 'racketmanager' );
        }
        if ( empty( $age_range ) || 'junior' == $age_range ) {
            $age_limits['8']    = __( 'Under 8', 'racketmanager' );
            $age_limits['9']    = __( 'Under 9', 'racketmanager' );
            $age_limits['10']   = __( 'Under 10', 'racketmanager' );
            $age_limits['11']   = __( 'Under 11', 'racketmanager' );
            $age_limits['12']   = __( 'Under 12', 'racketmanager' );
            $age_limits['14']   = __( 'Under 14', 'racketmanager' );
            $age_limits['16']   = __( 'Under 16', 'racketmanager' );
            $age_limits['18']   = __( 'Under 18', 'racketmanager' );
            $age_limits['21']   = __( 'Under 21', 'racketmanager' );

        }
        if ( empty( $age_range ) || 'senior' == $age_range ) {
            $age_limits['30']   = __( 'Over 30', 'racketmanager' );
            $age_limits['35']   = __( 'Over 35', 'racketmanager' );
            $age_limits['40']   = __( 'Over 40', 'racketmanager' );
            $age_limits['45']   = __( 'Over 45', 'racketmanager' );
            $age_limits['50']   = __( 'Over 50', 'racketmanager' );
            $age_limits['55']   = __( 'Over 55', 'racketmanager' );
            $age_limits['60']   = __( 'Over 60', 'racketmanager' );
            $age_limits['65']   = __( 'Over 65', 'racketmanager' );
            $age_limits['70']   = __( 'Over 70', 'racketmanager' );
            $age_limits['75']   = __( 'Over 75', 'racketmanager' );
            $age_limits['80']   = __( 'Over 80', 'racketmanager' );
            $age_limits['85']   = __( 'Over 85', 'racketmanager' );
        }
        return $age_limits;
    }
    /**
     * Get age limit value function
     *
     * @param string|null $age_limit age limit.
     * @return string age_limit text
     */
    public static function get_age_limit( ?string $age_limit ): string {
        $age_limits = self::get_age_limits();
        return empty( $age_limits[ $age_limit ] ) ? __( 'Open', 'racketmanager' ) : $age_limits[ $age_limit ];
    }
    /**
     * Get age groups function
     *
     * @return array
     */
    public static function get_age_groups(): array {
        $age_groups           = array();
        $age_groups['open']   = __( 'Open', 'racketmanager' );
        $age_groups['junior'] = __( 'Junior', 'racketmanager' );
        $age_groups['senior'] = __( 'Senior', 'racketmanager' );
        return $age_groups;
    }
    /**
     * Get age group value function
     *
     * @param string|null $age_group age limit.
     * @return string age_limit text
     */
    public static function get_age_group( ?string $age_group ): string {
        $age_groups = self::get_age_groups();
        return empty( $age_groups[ $age_group ] ) ? false : $age_groups[ $age_group ];
    }
    /**
     * Get club roles function
     *
     * @return array
     */
    public static function get_club_roles(): array {
        $club_roles      = array();
        $club_roles['1'] = __( 'Match secretary', 'racketmanager' );
        $club_roles['2'] = __( 'Coach', 'racketmanager' );
        $club_roles['3'] = __( 'Treasurer', 'racketmanager' );
        return $club_roles;
    }
    /**
     * Get club role description function
     *
     * @param string|null $club_role role.
     * @return string club_role text
     */
    public static function get_club_role( ?string $club_role ): string {
        $club_roles = self::get_club_roles();
        return empty( $club_roles[ $club_role ] ) ? false : $club_roles[ $club_role ];
    }
    /**
     * Get club role reference
     *
     * @param string $club_role club role description.
     * @return int club role id
     */
    public static function get_club_role_ref( string $club_role ): int {
        $club_roles = self::get_club_roles();
        $role_ref   = array_search( $club_role, $club_roles, true );
        if ( false === $role_ref ) {
            $role_ref = 0;
        }
        return intval( $role_ref );
    }
    /**
     * Amend date function
     *
     * @param string $date date.
     * @param int $adjustment_value adjustment value.
     * @param string $adjustment_type adjustment type default to '+'.
     * @param string $adjustment_period adjustment period default to 'day'.
     * @return string new date.
     */
    public static function amend_date( string $date, int $adjustment_value, string $adjustment_type = '+', string $adjustment_period = 'day' ): string {
        if ( $date && $adjustment_value ) {
            return gmdate( 'Y-m-d', strtotime( $date . ' ' . $adjustment_type . $adjustment_value . $adjustment_period ) );
        } else {
            return $date;
        }
    }
    /**
     * Get sports
     *
     * @return array
     */
    public static function get_sports(): array {
        $types = array();
        /**
        * Add custom league types
        *
        * @param array $types
        * @return array
        * @category wp-filter
        */
        $types = apply_filters( 'racketmanager_sports', $types );
        asort( $types );

        return $types;
    }
    /**
     * Get ranking types function
     *
     * @return array
     */
    public static function get_ranking_types(): array {
        $ranking_types           = array();
        $ranking_types['auto']   = __( 'Automatic', 'racketmanager' );
        $ranking_types['manual'] = __( 'Manual', 'racketmanager' );
        return $ranking_types;
    }
    /**
     * Get standings display options
     *
     * @return array
     */
    public static function get_standings_display_options(): array {
        return array(
            'status'     => __( 'Team Status', 'racketmanager' ),
            'pld'        => __( 'Played Games', 'racketmanager' ),
            'won'        => __( 'Won Games', 'racketmanager' ),
            'tie'        => __( 'Tie Games', 'racketmanager' ),
            'lost'       => __( 'Lost Games', 'racketmanager' ),
            'winPercent' => __( 'Win Percentage', 'racketmanager' ),
            'last5'      => __( 'Last 5 Matches', 'racketmanager' ),
            'sets'       => __( 'Sets', 'racketmanager' ),
            'games'      => __( 'Games', 'racketmanager' ),
        );
    }
    /**
     * Get currency format
     *
     * @return object || null
     */
    public static function get_currency_format(): object {
        return numfmt_create( get_locale(), NumberFormatter::CURRENCY );
    }
    /**
     * Get currency code
     *
     * @return string
     */
    public static function get_currency_code(): string {
        setlocale( LC_ALL, get_locale() );
        $locale_info = localeconv();
        return empty( $locale_info['int_curr_symbol'] ) ? 'GBP' : trim( $locale_info['int_curr_symbol'] ) ;
    }
    /**
     * Finds and returns a matching error message for the given error code.
     *
     * @param string $error_code    The error code to look up.
     *
     * @return string               An error message.
     */
    public static function get_error_message( string $error_code ): string {
        return match ($error_code) {
            'empty_password' => __('You need to enter a password to login.', 'racketmanager'),
            'incorrect_password' => __('Incorrect password.', 'racketmanager'),
            'email' => __('The email address you entered is not valid.', 'racketmanager'),
            'email_exists' => __('An account exists with this email address.', 'racketmanager'),
            'closed' => __('Registering new users is currently not allowed.', 'racketmanager'),
            'captcha' => __('Google reCAPTCHA verification failed', 'racketmanager'),
            'empty_username' => __('Email address must be entered.', 'racketmanager'),
            'invalid_email', 'invalidcombo', 'invalid_username' => __('There are no users registered with this email address.', 'racketmanager'),
            'expiredkey', 'invalidkey' => __('Password reset link has expired.', 'racketmanager'),
            'password_reset_mismatch' => __('The two passwords you entered do not match.', 'racketmanager'),
            'password_reset_empty' => __("Password must be entered.", 'racketmanager'),
            'firstname_field_empty', 'first_name' => __('First name must be entered', 'racketmanager'),
            'lastname_field_empty', 'last_name' => __('Last name must be entered', 'racketmanager'),
            'gender_field_empty' => __('Gender must be specified', 'racketmanager'),
            'no_updates' => __('No updates to be made', 'racketmanager'),
            'form_has_timedout' => __('The form has timed out.', 'racketmanager'),
            'btm_field_empty' => __('LTA tennis number missing', 'racketmanager'),
            'security' => __('Form has expired. Please refresh the page and resubmit.', 'racketmanager'),
            default => $error_code,
        };
    }
    /**
     * Finds and returns a matching field for the given error code.
     *
     * @param string $error_code    The error code to look up.
     *
     * @return string               A field.
     */
    public static function get_error_field( string $error_code ): string {
        return match ($error_code) {
            'empty_password', 'incorrect_password' => 'user_pass',
            'email', 'email_exists', 'closed', 'empty_username', 'invalid_email', 'invalidcombo', 'invalid_username' => 'user_login',
            'captcha' => 'captcha',
            'password_reset_mismatch', 'password_reset_empty' => 'password',
            'firstname_field_empty', 'first_name' => 'firstname',
            'lastname_field_empty', 'last_name' => 'lastname',
            'gender_field_empty' => 'gender',
            'btm_field_empty' => 'btm',
            default => 'top',
        };
    }
    /**
     * Get email opt in choices  function
     *
     * @return array
     */
    public static function get_email_opt_ins(): array {
        $email_opt_ins      = array();
        $email_opt_ins['1'] = __( 'Tournament notification', 'racketmanager' );
        return $email_opt_ins;
    }
    /**
     * Get email opt in value function
     *
     * @param string $opt_in opt in.
     * @return string $opt_in text
     */
    public static function get_email_opt_in( string $opt_in ): string {
        $email_opt_ins = self::get_email_opt_ins();
        return empty( $email_opt_ins[ $opt_in ] ) ? __( 'Unknown', 'racketmanager' ) : $email_opt_ins[ $opt_in ];
    }

    /**
     * Check age within limit
     *
     * @param int|null $player_age
     * @param int $age_limit
     * @param string $gender
     * @param int|null $age_offset
     *
     * @return stdClass
     */
    public static function check_age_within_limit( ?int $player_age, int $age_limit, string $gender, ?int $age_offset ): object {
        $age_check        = new stdClass();
        $age_check->valid = true;
        $age_check->msg   = null;
        if ( empty( $player_age ) ) {
            $age_check->valid = false;
        } elseif ( $age_limit >= 30 ) {
            if ( ! empty( $age_offset ) && 'F' === $gender ) {
                $age_limit -= $age_offset;
            }
            if ( $player_age < $age_limit ) {
                $age_check->valid = false;
                $age_check->msg   = sprintf( __( 'player age (%1$d) less than event age limit (%2$d)', 'racketmanager' ), $player_age, $age_limit );
            }
        } elseif ( $player_age > $age_limit ) {
            $age_check->valid = false;
            $age_check->msg   = sprintf( __( 'player age (%1$d) greater than event age limit (%2$d)', 'racketmanager' ), $player_age, $age_limit );
        }
        return $age_check;
    }

    /**
     * Generate team name for winner/loser of previous match
     *
     * @param string $title
     *
     * @return string|null
     */
    public static function generate_team_name( string $title ): ?string {
        $name_array = explode( '_', $title );
        if ( '1' === $name_array[0] ) {
            $team_name = __( 'Winner of', 'racketmanager' );
        } elseif ( '2' === $name_array[0] ) {
            $team_name = __( 'Loser of', 'racketmanager' );
        } else {
            $team_name = null;
        }
        if ( ! empty( $team_name ) && is_numeric( $name_array[2] ) ) {
            $match = get_match( $name_array[2] );
            if ( $match ) {
                $team_name .= ' ' . $match->teams['home']->title . ' ' . __( 'vs', 'racketmanager' ) . ' ' . $match->teams['away']->title;
            }
        }
        return $team_name;
    }
    /**
     * Schedule result chase
     *
     * @param string $competition_type type of competition.
     * @param array $options array of options to use for chasing result.
     */
    public static function schedule_result_chase( string $competition_type, array $options ): void {
        $day            = intval( gmdate( 'd' ) );
        $month          = intval( gmdate( 'm' ) );
        $year           = intval( gmdate( 'Y' ) );
        $schedule_start = mktime( 19, 0, 0, $month, $day, $year );
        $interval       = 'daily';
        $schedule_args  = array( $competition_type );
        if ( '' !== $options['resultPending'] ) {
            $schedule_name = 'rm_resultPending';
            Util::clear_scheduled_event( $schedule_name, $schedule_args );
            if ( ! wp_next_scheduled( $schedule_name, $schedule_args ) && ! wp_schedule_event( $schedule_start, $interval, $schedule_name, $schedule_args ) ) {
                error_log( __( 'Error scheduling pending results', 'racketmanager' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }
        if ( '' !== $options['confirmationPending'] ) {
            $schedule_name = 'rm_confirmationPending';
            Util::clear_scheduled_event( $schedule_name, $schedule_args );
            if ( ! wp_next_scheduled( $schedule_name, $schedule_args ) && ! wp_schedule_event( $schedule_start, $interval, $schedule_name, $schedule_args ) ) {
                error_log( __( 'Error scheduling result confirmations', 'racketmanager' ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            }
        }
    }
    /**
     * Function to initialise match statistics
     *
     * @return array
     */
    public static function initialise_match_stats(): array {
        $stats                    = array();
        $stats['rubbers']['home'] = 0;
        $stats['rubbers']['away'] = 0;
        $stats['sets']['home']    = 0;
        $stats['sets']['away']    = 0;
        $stats['games']['home']   = 0;
        $stats['games']['away']   = 0;
        return $stats;
    }
    /**
     * Get default number of match days
     *
     * @param string $type competition type.
     *
     * @return int default number of match days.
     */
    public static function get_default_match_days( string $type ): int {
        global $racketmanager;
        $options                = $racketmanager->get_options();
        $rm_options             = $options['championship'];
        $default_num_match_days = $rm_options['numRounds'] ?? 1;
        switch ( $type ) {
            case 'cup':
                $args['count'] = true;
                $args['type']  = 'affiliated';
                $num_clubs     = $racketmanager->get_clubs( $args );
                if ( $num_clubs ) {
                    $num_match_days = ceil( log( $num_clubs, 2 ) );
                } else {
                    $num_match_days = $default_num_match_days;
                }
                break;
            case 'tournament':
                $num_match_days = $default_num_match_days;
                break;
            default:
                $num_match_days = 0;
                break;
        }
        return $num_match_days;
    }

    /**
     * Function to return search string from array
     *
     * @param array $search_terms search terms.
     * @param bool $standalone standalone indicator.
     *
     * @return string
     */
    public static function search_string( array $search_terms, bool $standalone = false ) : string {
        $search = '';
        if ( ! empty( $search_terms ) ) {
            if ( $standalone ) {
                $search = ' WHERE ';
            } else {
                $search = ' AND ';
            }
            $search .= implode( ' AND ', $search_terms );
        }
        return $search;
    }

    /**
     * Function to return order by string from array
     *
     * @param array $order_by order by terms.
     *
     * @return string
     */
    public static function order_by_string( array $order_by ) : string {
        $orderby_string = '';
        $order          = '';
        $i              = 0;
        foreach ( $order_by as $order => $direction ) {
            if ( ! in_array( $direction, array( 'DESC', 'ASC', 'desc', 'asc' ), true ) ) {
                $direction = 'ASC';
            }
            $orderby_string .= '`' . $order . '` ' . $direction;
            if ( $i < ( count( $order_by ) - 1 ) ) {
                $orderby_string .= ',';
            }
            ++$i;
        }
        if ( $orderby_string ) {
            $order = ' ORDER BY ' . $orderby_string;
        }
        return $order;
    }
    /**
     * Set table group
     *
     * @param string $group group.
     * @param integer $id id.
     */
    public static function set_table_group( string $group, int $id ): void {
        global $wpdb;

        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
                "UPDATE $wpdb->racketmanager_table SET `group` = %s WHERE `id` = %d",
                $group,
                $id
            )
        );
    }
}
