<?php
/**
 * League schedule service
 *
 * @package RacketManager
 * @subpackage Services/Admin/League
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\League;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use Racketmanager\Util\Util;
use stdClass;
use function Racketmanager\get_event;
use function Racketmanager\get_league;
use function Racketmanager\get_team;

/**
 * Service for league schedule generation and event matches deletion.
 */
class League_Schedule_Service {

    public function __construct(
        private readonly Fixture_Maintenance_Service $fixture_maintenance_service,
    ) {
    }

    /**
     * Delete matches for events
     *
     * @param array<int|string> $events
     */
    public function delete_event_matches( array $events ): Action_Result_DTO {
        global $racketmanager;

        $has_completed_matches = false;
        $deleted_any           = false;

        foreach ( $events as $event_id ) {
            $event       = get_event( (int) $event_id );
            if ( null === $event ) {
                continue;
            }
            $season      = $event->get_season();
            $match_count = $racketmanager ? $racketmanager->get_matches(
                array(
                    'count'    => true,
                    'event_id' => $event->id,
                    'season'   => $season,
                    'time'     => 'latest',
                )
            ) : 0;

            if ( $match_count ) {
                $has_completed_matches = true;
            } else {
                $leagues = $event->get_leagues();
                foreach ( $leagues as $league ) {
                    $league_obj = is_object( $league ) ? $league : get_league( (int) $league );
                    if ( null === $league_obj ) {
                        continue;
                    }
                    $matches = $league_obj->get_matches( array( 'season' => $season ) );
                    foreach ( $matches as $match ) {
                        $this->fixture_maintenance_service->delete_fixture( (int) $match->id );
                        $deleted_any = true;
                    }
                }
            }
        }

        if ( $has_completed_matches && ! $deleted_any ) {
            return new Action_Result_DTO(
                message: __( 'Event has completed matches', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        if ( $has_completed_matches && $deleted_any ) {
            return new Action_Result_DTO(
                message: __( 'Some matches deleted, but some events have completed matches', 'racketmanager' ),
                message_type: Admin_Message_Type::WARNING,
            );
        }

        return new Action_Result_DTO(
            message: __( 'Matches deleted', 'racketmanager' ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }

    /**
     * Schedule league matches
     *
     * @param array<int|string> $events
     */
    public function schedule_league_matches( array $events ): Action_Result_DTO {
        $validation = $this->validate_schedule( $events );
        if ( ! $validation->success ) {
            return new Action_Result_DTO(
                message: $validation->message ?? __( 'Schedule validation failed', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $max_teams    = (int) $validation->num_rounds + 1;
        $default_refs = array();
        for ( $i = 1; $i <= $max_teams; $i++ ) {
            $default_refs[] = $i;
        }

        $i      = 1;
        $result = false;
        do {
            $result = $this->setup_teams_in_schedule( $events, $max_teams, $default_refs );
            ++$i;
        } while ( ! $result && $i < 20 );

        if ( ! $result ) {
            return new Action_Result_DTO(
                message: __( 'Unable to generate schedule for selected events', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        foreach ( $events as $event_id ) {
            $event = get_event( (int) $event_id );
            if ( null === $event ) {
                continue;
            }
            foreach ( $event->get_leagues() as $league ) {
                $league_obj = is_object( $league ) ? $league : get_league( (int) $league );
                if ( null !== $league_obj && method_exists( $league_obj, 'schedule_matches' ) ) {
                    $league_obj->schedule_matches();
                }
            }
        }

        return new Action_Result_DTO(
            message: __( 'Matches scheduled', 'racketmanager' ),
            message_type: Admin_Message_Type::SUCCESS,
        );
    }

    /**
     * Validate schedule by team
     *
     * @param array<int|string> $events
     */
    private function validate_schedule( array $events ): object {
        global $racketmanager, $wpdb;

        $validation             = new stdClass();
        $validation->success    = true;
        $validation->num_rounds = 0;
        $messages               = array();
        $c                      = 0;
        $num_match_days         = 0;
        $home_away              = '';
        $match_dates            = array();

        foreach ( $events as $event_id ) {
            $event = get_event( (int) $event_id );
            if ( null === $event ) {
                continue;
            }
            $season      = $event->get_season();
            $match_count = $racketmanager ? $racketmanager->get_matches(
                array(
                    'count'    => true,
                    'event_id' => $event->id,
                    'season'   => $season,
                )
            ) : 0;

            if ( $match_count ) {
                $validation->success = false;
                /* translators: %1$s: event name %2$d season */
                $messages[] = sprintf( __( '%1$s already has matches scheduled for %2$d', 'racketmanager' ), $event->name, $season );
                break;
            } elseif ( empty( $c ) ) {
                $num_match_days = $event->current_season['num_match_days'] ?? 0;
                if ( ! isset( $event->current_season['match_dates'] ) ) {
                    $validation->success = false;
                    /* translators: %s: event name */
                    $messages[] = sprintf( __( 'Events match dates not set for %s', 'racketmanager' ), $event->name );
                } else {
                    $match_dates = $event->current_season['match_dates'];
                }
                $home_away = empty( $event->current_season['home_away'] ) ? false : $event->current_season['home_away'];
                if ( $home_away ) {
                    $validation->num_rounds = (int) ( $num_match_days / 2 );
                } else {
                    $validation->num_rounds = (int) $num_match_days;
                }
            } else {
                if ( ( $event->current_season['num_match_days'] ?? 0 ) !== $num_match_days ) {
                    $validation->success = false;
                    $messages[]          = __( 'Events have different number of match days', 'racketmanager' );
                }
                if ( ! isset( $event->current_season['match_dates'] ) ) {
                    $validation->success = false;
                    /* translators: %s: event name */
                    $messages[] = sprintf( __( 'Events match dates not set for %s', 'racketmanager' ), $event->name );
                } elseif ( $event->current_season['match_dates'] !== $match_dates ) {
                    $validation->success = false;
                    $messages[]          = __( 'Events have different match dates', 'racketmanager' );
                }
                $home_away_new = empty( $event->current_season['home_away'] ) ? false : $event->current_season['home_away'];
                if ( $home_away_new !== $home_away ) {
                    $validation->success = false;
                    $messages[]          = __( 'Events have different home / away setting', 'racketmanager' );
                }
            }
            ++$c;
        }

        if ( $validation->success && $wpdb ) {
            $season                = $this->get_latest_season();
            $event_ids             = implode( ',', array_map( 'intval', $events ) );
            $teams_missing_details = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    "SELECT `t`.`title` FROM $wpdb->racketmanager_teams t, $wpdb->racketmanager_league_teams t1, $wpdb->racketmanager l WHERE t.`id` = t1.`team_id` AND t1.`match_day` IS NULL AND l.`id` = t1.`league_id` AND l.`event_id` in (" . $event_ids . ') AND t1.`season` = %s',
                    $season
                )
            );
            if ( $teams_missing_details ) {
                $missing_teams = array();
                foreach ( $teams_missing_details as $team ) {
                    $missing_teams[] = $team->title;
                }
                $teams               = implode( ' and ', $missing_teams );
                $validation->success = false;
                /* translators: %s: teams with missing match days */
                $messages[] = sprintf( __( 'Missing match days for %s', 'racketmanager' ), $teams );
            }
        }

        $validation->message = implode( '<br>', $messages );
        return $validation;
    }

    /**
     * Get latest season
     */
    private function get_latest_season(): int {
        global $wpdb;
        if ( ! $wpdb ) {
            return 0;
        }

        return (int) $wpdb->get_var( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            "SELECT MAX(name) FROM $wpdb->racketmanager_seasons"
        );
    }

    /**
     * Setup teams in schedule where necessary
     *
     * @param array<int|string> $events
     * @param array<int>        $default_refs
     */
    private function setup_teams_in_schedule( array $events, int $max_teams, array $default_refs ): bool {
        global $wpdb;
        if ( ! $wpdb ) {
            return false;
        }

        $validation           = new stdClass();
        $validation->success  = true;
        $validation->messages = array();
        $season               = (string) $this->get_latest_season();
        $event_ids            = implode( ',', array_map( 'intval', $events ) );

        /* clear out schedule keys for this run */
        $wpdb->query( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "UPDATE $wpdb->racketmanager_league_teams SET `group` = '' WHERE `season` = %s AND `league_id` IN (SELECT `id` FROM $wpdb->racketmanager WHERE `event_id` IN ($event_ids))",
                $season
            )
        );
        $validation = $this->handle_teams_in_same_division( $events, $season, $validation, $default_refs );
        if ( $validation->success ) {
            $validation = $this->handle_teams_with_same_match_time( $events, $season, $max_teams, $validation, $default_refs );
        }

        return (bool) $validation->success;
    }

    /**
     * Setup teams from the same club in a division.
     *
     * @param array<int|string> $events
     * @param array<int>        $default_refs
     */
    private function handle_teams_in_same_division( array $events, string $season, object $validation, array $default_refs ): object {
        global $wpdb;
        if ( ! $wpdb ) {
            return $validation;
        }

        $alt_ref   = null;
        $event_ids = implode( ',', array_map( 'intval', $events ) );
        /* set refs for those teams in the same division so they play first */
        $sql = $wpdb->prepare(
            "SELECT `t`.`club_id`, tbl.`league_id` FROM $wpdb->racketmanager_teams t, $wpdb->racketmanager l, $wpdb->racketmanager_league_teams tbl WHERE l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND l.`event_id` in (" . $event_ids . ') AND tbl.`season` = %s GROUP BY t.`club_id`, tbl.`league_id` HAVING COUNT(*) > 1',
            $season
        );
        $club_leagues = wp_cache_get( md5( $sql ), 'club_leagues' );
        if ( ! $club_leagues ) {
            $club_leagues = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                $sql
            );
            wp_cache_set( md5( $sql ), $club_leagues, 'club_leagues' );
        }
        if ( ! is_array( $club_leagues ) ) {
            return $validation;
        }

        foreach ( $club_leagues as $club_league ) {
            $sql = $wpdb->prepare(
                "SELECT tbl.`id`, tbl.`team_id`, tbl.`league_id` FROM $wpdb->racketmanager_teams t, $wpdb->racketmanager l, $wpdb->racketmanager_league_teams tbl WHERE l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND l.`event_id` in (" . $event_ids . ') AND tbl.`season` = %s AND t.`club_id` = %d AND tbl.`league_id` = %d ORDER BY tbl.`team_id`',
                $season,
                $club_league->club_id,
                $club_league->league_id
            );
            $teams = wp_cache_get( md5( $sql ), 'club_leagues' );
            if ( ! $teams ) {
                $teams = $wpdb->get_results(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    $sql
                );
                wp_cache_set( md5( $sql ), $teams, 'club_leagues' );
            }
            if ( ! is_array( $teams ) ) {
                continue;
            }

            $counter  = 1;
            $alt_refs = array();
            $refs     = array();
            $table1   = 0;
            $league1  = 0;
            $team1    = 0;
            foreach ( $teams as $team ) {
                if ( $counter & 1 ) {
                    $team1    = (int) $team->team_id;
                    $table1   = (int) $team->id;
                    $league1  = (int) $team->league_id;
                    $refs     = $default_refs;
                    $alt_refs = $refs;
                    $groups   = $this->get_table_groups( $league1, (int) $season );
                    if ( $groups ) {
                        foreach ( $groups as $group ) {
                            $ref = array_search( intval( $group->value ), $refs, true );
                            if ( false !== $ref ) {
                                array_splice( $refs, $ref, 1 );
                            }
                        }
                    }
                } else {
                    $team2   = (int) $team->team_id;
                    $table2  = (int) $team->id;
                    $league2 = (int) $team->league_id;
                    $groups  = $this->get_table_groups( $league2, (int) $season );
                    if ( $groups ) {
                        foreach ( $groups as $group ) {
                            $ref = array_search( intval( $group->value ), $alt_refs, true );
                            if ( false !== $ref ) {
                                array_splice( $alt_refs, $ref, 1 );
                            }
                        }
                    }
                    if ( $refs ) {
                        $alt_found      = false;
                        $ref_option     = array( 2, 1, 3 );
                        $alt_ref_option = array( 5, 6, 4 );
                        $ref            = 0;
                        for ( $i = 0; $i < 3; $i++ ) {
                            $ref_free = array_search( $ref_option[ $i ], $refs, true );
                            $ref      = $ref_option[ $i ];
                            if ( false !== $ref_free ) {
                                $alt_ref   = $alt_ref_option[ $i ];
                                $alt_found = in_array( $alt_ref, $alt_refs, true );
                                if ( false !== $alt_found ) {
                                    break;
                                }
                            }
                        }
                        if ( false !== $alt_found ) {
                            Util::set_table_group( $ref, $table1 );
                            Util::set_table_group( $alt_ref, $table2 );
                        } else {
                            $validation->success = false;
                            /* translators: %1$d: league %2$d team 1 %3$d team 2 */
                            $validation->messages[] = sprintf( __( 'Unable to schedule first round for league %1$d for team %2$d and team %3$d', 'racketmanager' ), $league1, $team1, $team2 );
                        }
                    } else {
                        $validation->success = false;
                        /* translators: %1$d: league %2$d team 1 %3$d team 2 */
                        $validation->messages[] = sprintf( __( 'Error in scheduling first round for league %1$d for team %2$d and team %3$d', 'racketmanager' ), $league1, $team1, $team2 );
                    }
                }
                ++$counter;
            }
        }
        return $validation;
    }

    /**
     * Setup teams from same club with same match time.
     *
     * @param array<int|string> $events
     * @param array<int>        $default_refs
     */
    private function handle_teams_with_same_match_time( array $events, string $season, int $max_teams, object $validation, array $default_refs ): object {
        global $wpdb;
        if ( ! $wpdb ) {
            return $validation;
        }

        $event_ids = implode( ',', array_map( 'intval', $events ) );
        /* find all clubs with multiple matches at the same time */
        $event_teams = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                "SELECT `t`.`club_id`, tbl.`match_day`, tbl.`match_time`, count(*) FROM $wpdb->racketmanager_teams t, $wpdb->racketmanager l, $wpdb->racketmanager_league_teams tbl WHERE l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND l.`event_id` in (" . $event_ids . ') AND tbl.`season` = %s AND tbl.`profile` != 3 GROUP BY t.`club_id`, tbl.`match_day`, tbl.`match_time` HAVING COUNT(*) > 1 ORDER BY count(*) DESC, RAND()',
                $season
            )
        );
        if ( ! is_array( $event_teams ) ) {
            return $validation;
        }

        /* for each club / match time combination balance schedule so one team is home while the other is away */
        foreach ( $event_teams as $event_team ) {
            $teams = $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
                $wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
                    "SELECT tbl.`id`, tbl.`team_id`, tbl.`league_id`, tbl.`group` FROM $wpdb->racketmanager_teams t, $wpdb->racketmanager l, $wpdb->racketmanager_league_teams tbl WHERE l.`id` = tbl.`league_id` AND tbl.`team_id` = t.`id` AND l.`event_id` in (" . $event_ids . ') AND tbl.`season` = %s AND t.`club_id` = %d AND tbl.`match_day` = %s AND tbl.`match_time` = %s AND tbl.`profile` != 3 ORDER BY tbl.`group`, tbl.`team_id`',
                    $season,
                    $event_team->club_id,
                    $event_team->match_day,
                    $event_team->match_time
                )
            );
            if ( ! is_array( $teams ) ) {
                continue;
            }

            $counter  = 1;
            $refs     = array();
            $alt_refs = array();
            $table1   = 0;
            $team1    = 0;
            $league1  = 0;
            $group1   = '';

            foreach ( $teams as $team ) {
                /* for first of pair */
                if ( $counter & 1 ) {
                    $team1    = (int) $team->team_id;
                    $table1   = (int) $team->id;
                    $league1  = (int) $team->league_id;
                    $group1   = $team->group;
                    $refs     = $default_refs;
                    $alt_refs = $refs;
                    $groups   = $this->get_table_groups( $league1, (int) $season );
                    if ( $groups ) {
                        foreach ( $groups as $group ) {
                            $ref = array_search( intval( $group->value ), $refs, true );
                            if ( false !== $ref ) {
                                array_splice( $refs, $ref, 1 );
                            }
                        }
                    }
                } else {
                    /* for second of pair */
                    $table2  = (int) $team->id;
                    $league2 = (int) $team->league_id;
                    $group2  = $team->group;
                    $groups  = $this->get_table_groups( $league2, (int) $season );
                    if ( $groups ) {
                        foreach ( $groups as $group ) {
                            $ref = array_search( intval( $group->value ), $alt_refs, true );
                            if ( false !== $ref ) {
                                array_splice( $alt_refs, $ref, 1 );
                            }
                        }
                    }
                    if ( $refs ) {
                        if ( ! empty( $group1 ) ) {
                            $ref = (int) $group1;
                            if ( ! empty( $group2 ) ) {
                                $alt_ref   = (int) $group2;
                                $alt_found = true;
                            } else {
                                $alt_ref = (int) ( $ref + $max_teams / 2 );
                                if ( $alt_ref > $max_teams ) {
                                    $alt_ref = $alt_ref - $max_teams;
                                }
                                $alt_found = in_array( intval( $ref ), $refs, true );
                            }
                            if ( false !== $alt_found ) {
                                Util::set_table_group( $ref, $table1 );
                                Util::set_table_group( $alt_ref, $table2 );
                            } else {
                                $validation->success = false;
                                $league              = get_league( $league1 );
                                $team_obj            = get_team( $team1 );
                                /* translators: %1$s: team name %2$s league name */
                                $validation->messages[] = sprintf( __( '1 - Error in scheduling %1$s in %2$s', 'racketmanager' ), $team_obj?->title ?? '', $league?->title ?? '' );
                            }
                        } else {
                            $ref_set = false;
                            if ( ! empty( $group2 ) ) {
                                $alt_ref = (int) $group2;
                                $ref     = (int) ( $alt_ref - $max_teams / 2 );
                                if ( $ref < 1 ) {
                                    $ref = $ref + $max_teams;
                                }
                                $alt_found = in_array( intval( $ref ), $refs, true );
                                if ( false !== $alt_found ) {
                                    Util::set_table_group( $ref, $table1 );
                                    Util::set_table_group( $alt_ref, $table2 );
                                } else {
                                    $validation->success = false;
                                    $league              = get_league( $league1 );
                                    $team_obj            = get_team( $team1 );
                                    /* translators: %1$s: team name %2$s league name */
                                    $validation->messages[] = sprintf( __( '4 - Error in scheduling %1$s in %2$s', 'racketmanager' ), $team_obj?->title ?? '', $league?->title ?? '' );
                                }
                            } else {
                                $count_refs = count( $refs );
                                for ( $i = 0; $i < $count_refs; $i++ ) {
                                    $ref     = (int) $refs[ $i ];
                                    $alt_ref = (int) ( $ref + $max_teams / 2 );
                                    if ( $alt_ref > $max_teams ) {
                                        $alt_ref = $alt_ref - $max_teams;
                                    }
                                    $alt_found = in_array( intval( $alt_ref ), $alt_refs, true );
                                    if ( false !== $alt_found ) {
                                        $ref_set = true;
                                        Util::set_table_group( $ref, $table1 );
                                        Util::set_table_group( $alt_ref, $table2 );
                                        break;
                                    }
                                }
                                if ( ! $ref_set ) {
                                    $validation->success = false;
                                    $league              = get_league( $league1 );
                                    $team_obj            = get_team( $team1 );
                                    /* translators: %1$s: team name %2$s league name */
                                    $validation->messages[] = sprintf( __( '2 - Error in scheduling %1$s in %2$s', 'racketmanager' ), $team_obj?->title ?? '', $league?->title ?? '' );
                                }
                            }
                        }
                    } else {
                        $validation->success = false;
                        $league              = get_league( $league1 );
                        $team_obj            = get_team( $team1 );
                        /* translators: %1$s: team name %2$s league name */
                        $validation->messages[] = sprintf( __( '3 - Error in scheduling %1$s in %2$s', 'racketmanager' ), $team_obj?->title ?? '', $league?->title ?? '' );
                    }
                }
                ++$counter;
            }
        }
        return $validation;
    }

    /**
     * Get table groups
     *
     * @return array<object>
     */
    private function get_table_groups( int $league, int $season ): array {
        global $wpdb;
        if ( ! $wpdb ) {
            return array();
        }

        return (array) $wpdb->get_results( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
        //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT `group` as `value` FROM $wpdb->racketmanager_league_teams WHERE `league_id` = $league AND `season` = $season AND `group` != ''"
        );
    }
}
