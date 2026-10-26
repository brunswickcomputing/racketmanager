<?php
/**
 * League schedule service
 *
 * @package RacketManager
 * @subpackage Services/Admin/League
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\League;

use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Schedule_Validation_Exception;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Services\Competition\Competition_Schedule_Service;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Fixture\Fixture_Maintenance_Service;
use stdClass;

/**
 * Service for league schedule generation and event matches deletion.
 *
 * Scheduling validates the selected events and delegates to the
 * {@see Competition_Schedule_Service} (9.7.0 competition scheduler).
 */
class League_Schedule_Service {

    public function __construct(
        private readonly Fixture_Maintenance_Service $fixture_maintenance_service,
        private readonly Competition_Schedule_Service $competition_schedule_service,
        private readonly Event_Repository_Interface $event_repository,
        private readonly League_Repository_Interface $league_repository,
        private readonly League_Team_Repository_Interface $league_team_repository,
        private readonly Event_Query_Service $event_query_service,
        private readonly League_Query_Service $league_query_service,
    ) {
    }

    /**
     * Delete matches for events
     *
     * @param array<int|string> $events
     * @param string|null       $season season posted from the schedule page; falls back to each
     *                                  event's current season when empty. A posted season the event
     *                                  does not have is rejected rather than silently replaced.
     */
    public function delete_event_matches( array $events, ?string $season = null ): Action_Result_DTO {
        $has_completed_matches = false;
        $deleted_any           = false;
        $requested_season      = ( null === $season || '' === trim( $season ) ) ? null : trim( $season );

        $selected_events = array();
        foreach ( $events as $event_id ) {
            $event = $this->event_repository->find_by_id( (int) $event_id );
            if ( null === $event ) {
                continue;
            }
            if ( null !== $requested_season && null === $this->get_event_season( $event, $requested_season ) ) {
                return new Action_Result_DTO(
                    /* translators: %1$s: season %2$s: event name */
                    message: sprintf( __( 'Season %1$s is not available for %2$s', 'racketmanager' ), $requested_season, $event->name ),
                    message_type: Admin_Message_Type::ERROR,
                );
            }
            $selected_events[] = $event;
        }

        foreach ( $selected_events as $event ) {
            $season      = $requested_season ?? (string) $event->get_season();
            $match_count = $this->event_repository->count_by_event_season( (int) $event->id, $season, true );

            if ( $match_count ) {
                $has_completed_matches = true;
            } else {
                $leagues = ! empty( $event->leagues ) ? $event->leagues : $this->event_query_service->get_leagues( $event );
                foreach ( is_array( $leagues ) ? $leagues : array() as $league ) {
                    $league_obj = $this->resolve_league( $league );
                    if ( null === $league_obj ) {
                        continue;
                    }
                    $matches = $this->league_query_service->get_matches( $league_obj, array( 'season' => $season ) );
                    foreach ( is_array( $matches ) ? $matches : array() as $match ) {
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
     * Schedule league matches for the selected events in the given season.
     *
     * @param array<int|string> $events
     * @param string|null       $season season posted from the schedule page; falls back to the
     *                                  current season of the first event when empty.
     */
    public function schedule_league_matches( array $events, ?string $season = null ): Action_Result_DTO {
        $validation = $this->validate_events( $events, $season );
        if ( ! $validation->success ) {
            return new Action_Result_DTO(
                message: $validation->message ?? __( 'Schedule validation failed', 'racketmanager' ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $teams_per_division = (int) $validation->num_rounds + 1;
        try {
            $result = $this->competition_schedule_service->schedule_events(
                $events,
                (string) $validation->season,
                $teams_per_division,
                (bool) $validation->home_away,
                (array) $validation->match_dates
            );
        } catch ( Schedule_Validation_Exception $e ) {
            return new Action_Result_DTO(
                message: implode( '<br>', $e->get_error_msgs() ),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        $scheduled = $result->is_valid ? count( $result->fixtures() ) : 0;
        $report    = array(
            'retry'             => $result->retry,
            'conflicts'         => $result->conflicts,
            'alerts'            => $result->alerts,
            'resource_pairings' => $result->resource_pairings,
            'scheduled'         => $scheduled,
        );

        if ( ! $result->is_valid ) {
            return new Action_Result_DTO(
                /* translators: %d: number of scheduling attempts */
                message: sprintf( __( 'Scheduling failed after %d attempts', 'racketmanager' ), $result->retry ),
                message_type: Admin_Message_Type::ERROR,
                flash_data: $report,
            );
        }

        return new Action_Result_DTO(
            /* translators: %d: number of scheduling attempts */
            message: sprintf( __( 'Matches scheduled after %d attempts', 'racketmanager' ), $result->retry ),
            message_type: Admin_Message_Type::SUCCESS,
            flash_data: $report,
        );
    }

    /**
     * Validate schedule by team / events
     *
     * Returns an object with `success`, `season`, `num_rounds`, `home_away`, `match_dates`,
     * `messages` and `message`.
     *
     * @param array<int|string> $events
     * @param string|null       $season requested season; defaults to the current season of the first event.
     */
    public function validate_events( array $events, ?string $season = null ): object {
        $validation              = new stdClass();
        $validation->success     = true;
        $validation->season      = ( null === $season || '' === trim( $season ) ) ? null : trim( $season );
        $validation->num_rounds  = 0;
        $validation->home_away   = false;
        $validation->match_dates = array();
        $messages                = array();
        $c                       = 0;
        $num_match_days          = 0;
        $match_dates             = array();
        $event_ids               = array();

        foreach ( $events as $event_id ) {
            $event = $this->event_repository->find_by_id( (int) $event_id );
            if ( null === $event ) {
                continue;
            }
            $event_ids[] = (int) $event->id;
            if ( null === $validation->season ) {
                $validation->season = (string) $event->get_season();
            }
            $season_data = $this->get_event_season( $event, $validation->season );
            if ( null === $season_data ) {
                $validation->success = false;
                /* translators: %1$s: season %2$s: event name */
                $messages[] = sprintf( __( 'Season %1$s is not available for %2$s', 'racketmanager' ), $validation->season, $event->name );
                break;
            }
            $match_count = $this->event_repository->count_by_event_season( (int) $event->id, $validation->season );

            if ( $match_count ) {
                $validation->success = false;
                /* translators: %1$s: event name %2$s season */
                $messages[] = sprintf( __( '%1$s already has matches scheduled for %2$s', 'racketmanager' ), $event->name, $validation->season );
                break;
            } elseif ( empty( $c ) ) {
                $num_match_days = $season_data['num_match_days'] ?? 0;
                if ( ! isset( $season_data['match_dates'] ) ) {
                    $validation->success = false;
                    /* translators: %s: event name */
                    $messages[] = sprintf( __( 'Events match dates not set for %s', 'racketmanager' ), $event->name );
                } else {
                    $match_dates = $season_data['match_dates'];
                }
                $validation->match_dates = $match_dates;
                $validation->home_away   = ! empty( $season_data['home_away'] );
                if ( $validation->home_away ) {
                    $validation->num_rounds = (int) ( $num_match_days / 2 );
                } else {
                    $validation->num_rounds = (int) $num_match_days;
                }
            } else {
                if ( ( $season_data['num_match_days'] ?? 0 ) !== $num_match_days ) {
                    $validation->success = false;
                    $messages[]          = __( 'Events have different number of match days', 'racketmanager' );
                }
                if ( ! isset( $season_data['match_dates'] ) ) {
                    $validation->success = false;
                    /* translators: %s: event name */
                    $messages[] = sprintf( __( 'Events match dates not set for %s', 'racketmanager' ), $event->name );
                } elseif ( $season_data['match_dates'] !== $match_dates ) {
                    $validation->success = false;
                    $messages[]          = __( 'Events have different match dates', 'racketmanager' );
                }
                $home_away_new = ! empty( $season_data['home_away'] );
                if ( $home_away_new !== $validation->home_away ) {
                    $validation->success = false;
                    $messages[]          = __( 'Events have different home / away setting', 'racketmanager' );
                }
            }
            ++$c;
        }

        if ( $validation->success && $event_ids && null !== $validation->season ) {
            $missing_teams = $this->league_team_repository->find_teams_missing_match_day( $event_ids, $validation->season );
            if ( $missing_teams ) {
                $teams               = implode( ' and ', $missing_teams );
                $validation->success = false;
                /* translators: %s: teams with missing match days */
                $messages[] = sprintf( __( 'Missing match days for %s', 'racketmanager' ), $teams );
            }
        }

        $validation->messages = $messages;
        $validation->message  = implode( '<br>', $messages );
        return $validation;
    }

    /**
     * Season settings of an event for the requested season.
     *
     * @return array<string, mixed>|null null when the season is not configured for the event.
     */
    private function get_event_season( Event $event, string $season ): ?array {
        $seasons = $event->get_seasons();
        if ( isset( $seasons[ $season ] ) && is_array( $seasons[ $season ] ) ) {
            return $seasons[ $season ];
        }
        if ( is_array( $event->current_season ) && (string) ( $event->current_season['name'] ?? '' ) === $season ) {
            return $event->current_season;
        }
        return null;
    }

    private function resolve_league( mixed $league ): ?League {
        if ( $league instanceof League ) {
            return $league;
        }
        $id = is_object( $league ) ? ( $league->id ?? null ) : $league;
        if ( ! is_numeric( $id ) || (int) $id <= 0 ) {
            return null;
        }
        return $this->league_repository->find_by_id( (int) $id );
    }
}
