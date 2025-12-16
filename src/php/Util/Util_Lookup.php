<?php
/**
 * RacketManager_Util API: RacketManager_Util_Lookup class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Util
 */

namespace Racketmanager\Util;

class Util_Lookup {
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
     * Get an array of supported point rules
     *
     * @return array
     */
    public static function get_point_rules(): array {
        $rules                          = array();
        $rules['manual']                = __( 'Update Standings Manually', 'racketmanager' );
        $rules['one']                   = __( 'One-Point-Rule', 'racketmanager' );
        $rules['two']                   = __( 'Two-Point-Rule', 'racketmanager' );
        $rules['three']                 = __( 'Three-Point-Rule', 'racketmanager' );
        $rules['score']                 = __( 'Score', 'racketmanager' );
        $rules['user']                  = __( 'User defined', 'racketmanager' );
        $rules['tennis']                = __( 'Tennis', 'racketmanager' );
        $rules['tennisNoPenalty']       = __( 'Tennis No Penalty', 'racketmanager' );
        $rules['tennisSummer']          = __( 'Tennis Summer', 'racketmanager' );
        $rules['tennisSummerNoPenalty'] = __( 'Tennis Summer No Penalty', 'racketmanager' );
        $rules['tennisRubber']          = __( 'Tennis Rubber', 'racketmanager' );
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
        $club_roles['1'] = (object) [ 'desc' => __( 'Match secretary', 'racketmanager' ), 'limit' => 1 ];
        $club_roles['2'] = (object) [ 'desc' => __( 'Coach', 'racketmanager' ), 'limit' => null ];
        $club_roles['3'] = (object) [ 'desc' => __( 'Treasurer', 'racketmanager' ), 'limit' => 1 ];
        return $club_roles;
    }
    /**
     * Get club role description function
     *
     * @param string|null $club_role role.
     * @return object|bool club_role info.
     */
    public static function get_club_role( ?string $club_role ): object|bool {
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
        $role       = 0;
        foreach ( $club_roles as $role => $details ) {
            if ( $club_role === $details->desc ) {
                break;
            }
        }
        return intval( $role );
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
}
