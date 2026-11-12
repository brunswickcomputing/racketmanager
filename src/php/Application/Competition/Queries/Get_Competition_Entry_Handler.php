<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

use BackedEnum;
use Racketmanager\Application\Club\Ports\Club_Service_Interface;
use Racketmanager\Application\Club\Ports\Team_Service_Interface;
use Racketmanager\Application\Competition\DTOs\Competition_Entry_Result;
use Racketmanager\Application\Ports\Finance_Service_Interface;
use Racketmanager\Application\Tournament\Ports\Tournament_Service_Interface;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Exceptions\Club_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Util\Util_Lookup;
use stdClass;
use function array_column;
use function in_array;
use function intval;
use function is_object;
use function method_exists;
use function str_starts_with;
use function substr;
use function __;

/**
 * Handles retrieval and preparation of competition entry data.
 *
 * Resolves the requested competition from either a tournament name or competition
 * identifier, validates the requested entry context, and returns a structured
 * competition entry result for tournament, league, or cup entry flows.
 *
 * Tournament entries resolve the relevant tournament and player before building
 * the tournament entry response. League and cup entries require a valid season
 * and club context, including authorisation checks for club-based entry. When no
 * club is supplied and multiple eligible clubs are available, the handler returns
 * a club-selection result instead of an entry form result.
 *
 * The handler converts missing or invalid entities, unavailable seasons,
 * unauthorised club access, and unsupported competition types into error results
 * rather than allowing domain lookup failures to escape to the caller.
 */
final readonly class Get_Competition_Entry_Handler {
    /**
     * Initialises the service with required dependencies.
     *
     * @param Club_Entry_Selection_Service $club_entry_selection Service for club entry selection operations.
     * @param Competition_Service          $competition_service Service for competition operations.
     * @param Tournament_Service_Interface $tournament_service Service for tournament operations.
     * @param Player_Service               $player_service Service for player operations.
     * @param Club_Service_Interface       $club_service Service for club operations.
     * @param Team_Service_Interface       $team_service Service for team operations.
     * @param Registration_Service         $registration_service Service for registration operations.
     * @param Competition_Entry_Service    $competition_entry_service Service for competition entry operations.
     * @param Finance_Service_Interface    $finance_service Service for finance operations.
     * @param Event_Query_Service          $event_query_service Service for event query operations.
     *
     * @return void
     */
    public function __construct(
        private Club_Entry_Selection_Service $club_entry_selection,
        private Competition_Service $competition_service,
        private Tournament_Service_Interface $tournament_service,
        private Player_Service $player_service,
        private Club_Service_Interface $club_service,
        private Team_Service_Interface $team_service,
        private Registration_Service $registration_service,
        private Competition_Entry_Service $competition_entry_service,
        private Finance_Service_Interface $finance_service,
        private Event_Query_Service $event_query_service
    ) {
    }

    /**
     * Handles a competition entry query and returns the appropriate entry result.
     *
     * @param Get_Competition_Entry_Query $query Query containing competition, tournament, season, club, player, user, and template context.
     *
     * @return Competition_Entry_Result Result containing the resolved entry form data, club selection list, or an error message.
     */
    public function handle( Get_Competition_Entry_Query $query ): Competition_Entry_Result {
        $is_tournament    = false;
        $tournament_name  = $query->tournament_name;
        $competition_name = $query->competition_name;
        $tournament       = null;

        if ( $tournament_name ) {
            $is_tournament = true;
        } elseif ( ! $competition_name ) {
            return Competition_Entry_Result::error( __( 'Competition not found', 'racketmanager' ) );
        }

        if ( $is_tournament ) {
            try {
                $tournament = $this->tournament_service->get_tournament_by_name( (string) $tournament_name );
            } catch ( Tournament_Not_Found_Exception $e ) {
                return Competition_Entry_Result::error( $e->getMessage() );
            }
        }

        $competition_ref = ( $is_tournament && $tournament ) ? $tournament->get_competition_id() : $competition_name;

        try {
            $competition = $this->competition_service->get_by_id( $competition_ref );
        } catch ( Competition_Not_Found_Exception $e ) {
            return Competition_Entry_Result::error( $e->getMessage() );
        }

        if ( $competition->is_tournament ) {
            if ( $query->player_id ) {
                try {
                    $player = $this->player_service->get_player_by_name( $query->player_id );
                } catch ( Player_Not_Found_Exception $e ) {
                    return Competition_Entry_Result::error( $e->getMessage() );
                }
            } else {
                try {
                    $player = $this->player_service->get_player( $query->user_id );
                } catch ( Player_Not_Found_Exception $e ) {
                    return Competition_Entry_Result::error( $e->getMessage() );
                }
            }

            if ( ! $tournament ) {
                return Competition_Entry_Result::error( __( 'Tournament not found', 'racketmanager' ) );
            }

            return $this->build_tournament_entry( $tournament, $player, $query->template, $query->user_id, $query->is_admin );
        }

        // League / Cup flow.
        if ( ! $query->season ) {
            return Competition_Entry_Result::error( __( 'No season specified', 'racketmanager' ) );
        }

        $competition_season = $competition->get_season_by_name( $query->season );
        if ( ! $competition_season ) {
            return Competition_Entry_Result::error( __( 'Season not found', 'racketmanager' ) );
        }

        if ( ! empty( $competition_season['venue'] ) ) {
            try {
                $venue_club                       = $this->club_service->get_club( (int) $competition_season['venue'] );
                $competition_season['venue_name'] = $venue_club->shortcode;
            } catch ( Club_Not_Found_Exception ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
                // venue club not found.
            }
        }

        if ( $query->club_name ) {
            try {
                $club = $this->club_service->get_club_by_shortcode( $query->club_name );
            } catch ( Club_Not_Found_Exception ) {
                $club = null;
            }
            if ( ! $club ) {
                return Competition_Entry_Result::error( __( 'Club not found', 'racketmanager' ) );
            }

            $club_id   = method_exists( $club, 'get_id' ) ? $club->get_id() : ( $club->id ?? 0 );
            $can_enter = $this->club_entry_selection->available_clubs(
                $query->is_admin,
                $query->user_id,
                (int) $club_id,
                $query->entry_level
            );

            if ( ! $can_enter ) {
                return Competition_Entry_Result::error( __( 'User not authorised for club entry for this competition', 'racketmanager' ) );
            }
        } else {
            $clubs = $this->club_entry_selection->available_clubs(
                $query->is_admin,
                $query->user_id,
                null,
                $query->entry_level
            );

            if ( $clubs ) {
                return new Competition_Entry_Result(
                    type: 'clubs_list',
                    competition: $competition,
                    clubs: is_array( $clubs ) ? $clubs : [ $clubs ],
                    season: $query->season,
                    competition_season: $competition_season,
                    template: 'entry-form-clubs-list'
                );
            }

            return Competition_Entry_Result::error( __( 'No club specified', 'racketmanager' ) );
        }

        $comp_type = method_exists( $competition, 'get_type' ) ? $competition->get_type() : ( $competition->type ?? '' );
        if ( $comp_type instanceof BackedEnum ) {
            $comp_type = $comp_type->value;
        }

        return match ( (string) $comp_type ) {
            'league' => $this->build_league_entry( $competition, $query->season, $competition_season, $club, $query->template ),
            'cup'    => $this->build_cup_entry( $competition, $query->season, $competition_season, $club, $query->template ),
            default  => Competition_Entry_Result::error( __( 'Invalid competition type specified', 'racketmanager' ) ),
        };
    }

    /**
     * Resolves the closing date from the competition season data.
     *
     * @param array $competition_season Competition season data that may contain a non-empty date_closing value.
     *
     * @return string|null The closing date when available, or null when missing, empty, or not a string.
     */
    private function resolve_closing_date( array $competition_season ): ?string {
        $date = $competition_season['date_closing'] ?? null;
        return is_string( $date ) && '' !== $date ? $date : null;
    }

    /**
     * Builds a cup competition entry result for the given competition, season, and club.
     *
     * @param object $competition Competition object used to resolve the competition ID and load related events.
     * @param string $season Season identifier used to filter club teams registered for each event.
     * @param array  $competition_season Competition season data used to resolve season-specific entry details.
     * @param object $club Club object used to resolve the club ID and attach matching event entry data.
     * @param string $template Optional template suffix used to determine the entry result template filename.
     *
     * @return Competition_Entry_Result Cup competition entry result containing competition, club, event, team, match day, closing date, and template data.
     */
    private function build_cup_entry( object $competition, string $season, array $competition_season, object $club, string $template ): Competition_Entry_Result {
        $comp_id = method_exists( $competition, 'get_id' ) ? $competition->get_id() : ( $competition->id ?? null );
        $events  = $comp_id ? $this->competition_service->get_events_for_competition( (int) $comp_id ) : [];
        $club_id = method_exists( $club, 'get_id' ) ? $club->get_id() : ( $club->id ?? 0 );

        foreach ( $events as $i => $event ) {
            if ( ! ( $event instanceof Event ) ) {
                $event_id = is_object( $event ) && isset( $event->id ) ? (int) $event->id : (int) $event;
                try {
                    $event = $this->competition_service->get_event_by_id( $event_id );
                } catch ( Competition_Not_Found_Exception ) {
                    $event = null;
                }
            }
            if ( ! ( $event instanceof Event ) ) {
                unset( $events[ $i ] );
                continue;
            }
            $event->status = '';
            $event_teams   = $this->event_query_service->get_teams(
                $event,
                [
                    'season' => $season,
                    'club'   => $club_id,
                ]
            );
            foreach ( $event_teams as $event_team ) {
                $event_team->team_info     = $this->event_query_service->get_team_info( $event, (int) $event_team->team_id );
                $event->team               = $event_team;
                $event->status             = 'checked';
                $club->entry[ $event->id ] = $event;
            }
        }

        $ladies_teams = $this->team_service->get_teams_for_club( (int) $club_id, 'WD' );
        $mens_teams   = $this->team_service->get_teams_for_club( (int) $club_id, 'MD' );
        $mixed_teams  = $this->team_service->get_teams_for_club( (int) $club_id, 'XD' );
        $match_days   = Util_Lookup::get_match_days();

        $filename = ( ! empty( $template ) ) ? 'entry-cup-' . $template : 'entry-cup';

        return new Competition_Entry_Result(
            type: 'cup',
            competition: $competition,
            club: $club,
            season: $season,
            competition_season: $competition_season,
            events: $events,
            ladies_teams: $ladies_teams,
            mens_teams: $mens_teams,
            mixed_teams: $mixed_teams,
            match_days: $match_days,
            date_closing: $this->resolve_closing_date( $competition_season ),
            template: $filename
        );
    }

    /**
     * Builds a league competition entry result for the specified club and season.
     *
     * @param object $competition Competition object or data object used to resolve the competition ID and related events.
     * @param string $season Season identifier used to filter event team entries.
     * @param array  $competition_season Competition season data used to resolve season-specific entry details.
     * @param object $club Club object or data object used to resolve club teams and mark entered events.
     * @param string $template Optional template suffix used to determine the league entry template name.
     *
     * @return Competition_Entry_Result League entry result containing competition, club, event, match day, closing date, and template data.
     */
    private function build_league_entry( object $competition, string $season, array $competition_season, object $club, string $template ): Competition_Entry_Result {
        $comp_id = method_exists( $competition, 'get_id' ) ? $competition->get_id() : ( $competition->id ?? null );
        $events  = $comp_id ? $this->competition_service->get_events_for_competition( (int) $comp_id ) : [];
        $club_id = method_exists( $club, 'get_id' ) ? $club->get_id() : ( $club->id ?? 0 );

        foreach ( $events as $i => $event ) {
            if ( ! ( $event instanceof Event ) ) {
                $event_id = is_object( $event ) && isset( $event->id ) ? (int) $event->id : (int) $event;
                try {
                    $event = $this->competition_service->get_event_by_id( $event_id );
                } catch ( Competition_Not_Found_Exception ) {
                    $event = null;
                }
            }
            if ( ! ( $event instanceof Event ) ) {
                unset( $events[ $i ] );
                continue;
            }
            $event->status = '';
            $event_teams   = $this->event_query_service->get_teams(
                $event,
                [
                    'season' => $season,
                    'club'   => $club_id,
                ]
            );
            foreach ( $event_teams as $c => $event_team ) {
                $event_team->team_info = $this->event_query_service->get_team_info( $event, (int) $event_team->team_id );
                if ( '0' === $event_team->profile || '1' === $event_team->profile || '2' === $event_team->profile ) {
                    $event_team->status = 'checked';
                    $event->status      = 'checked';
                } else {
                    $event_team->status = '';
                }
                $event_teams[ $c ] = $event_team;
            }
            $event->event_teams = $event_teams;
            $event_type         = $event->type;
            if ( 'LD' === $event->type ) {
                $event_type = 'XD';
            }
            $event->teams = $this->team_service->get_teams_for_club( (int) $club_id, $event_type );
            $key          = 0;
            foreach ( $event->teams as $team ) {
                $found = in_array( $team->id, array_column( $event->event_teams, 'team_id' ), true );
                if ( false !== $found ) {
                    unset( $event->teams[ $key ] );
                }
                ++$key;
            }
            $key = 0;
            foreach ( $event->teams as $team ) {
                if ( ! empty( $team->league_id ) ) {
                    $event_team            = new stdClass();
                    $event_team->team_id   = $team->id;
                    $event_team->name      = $team->title ?? $team->name ?? '';
                    $event_team->league_id = 0;
                    $event_team->status    = null;
                    $event->event_teams[]  = $event_team;
                }
                ++$key;
            }
            $event_team            = new stdClass();
            $event_team->team_id   = 0;
            $event_team->name      = __( 'New team', 'racketmanager' );
            $event_team->league_id = 0;
            $event_team->status    = null;
            $event->event_teams[]  = $event_team;
            $events[ $i ]          = $event;
            if ( ! empty( $event->status ) ) {
                $club->entry[ $event->id ] = true;
            }
        }

        $filename = ( ! empty( $template ) ) ? 'entry-league-' . $template : 'entry-league';

        return new Competition_Entry_Result(
            type: 'league',
            competition: $competition,
            club: $club,
            season: $season,
            competition_season: $competition_season,
            events: $events,
            match_days: Util_Lookup::get_match_days(),
            date_closing: $this->resolve_closing_date( $competition_season ),
            template: $filename
        );
    }

    /**
     * Builds a tournament competition entry result for the specified tournament and player.
     *
     * Determines eligible events based on player gender and age, loads existing player event entries,
     * tournament fees, player payments, club memberships, and tournament entry details, then packages
     * the data for rendering with the selected entry template.
     *
     * @param Tournament  $tournament Tournament for which the entry result is being built.
     * @param Player|null $player Player entering the tournament, or null when no player is selected.
     * @param string      $template Optional template suffix used to select the tournament entry template.
     * @param int         $user_id User ID associated with the entry context.
     * @param bool        $can_manage Whether the current context allows management of the entry.
     *
     * @return Competition_Entry_Result Tournament entry result containing tournament, player, eligible events, entry details, memberships, permissions, and template information.
     */
    private function build_tournament_entry( Tournament $tournament, ?Player $player, string $template, int $user_id = 0, bool $can_manage = false ): Competition_Entry_Result {
        $player_id     = $player ? $player->get_id() : 0;
        $tournament_id = $tournament->get_id();
        $season        = $tournament->get_season();

        if ( empty( $player?->year_of_birth ) ) {
            $player_age = 0;
        } else {
            $player_age = (int) substr( $tournament->date_end, 0, 4 ) - intval( $player->year_of_birth );
        }

        $tournament->fees     = $this->tournament_service->get_fees( $tournament_id );
        $tournament->payments = $this->finance_service->get_tournament_paid_total_for_player( $player_id, $tournament_id );

        $player_entries = [];
        $events         = $this->tournament_service->get_events_for_tournament( $tournament_id );
        $c              = 0;

        foreach ( $events as $event ) {
            $entry_valid = false;
            $gender      = $player?->gender ?? '';

            if ( 'M' === $gender ) {
                if ( ! str_starts_with( $event->type, 'W' ) && ! str_starts_with( $event->type, 'G' ) ) {
                    $entry_valid = true;
                }
            } elseif ( 'F' === $gender ) {
                if ( ! str_starts_with( $event->type, 'M' ) && ! str_starts_with( $event->type, 'B' ) ) {
                    $entry_valid = true;
                }
            }

            if ( $entry_valid ) {
                if ( empty( $event->age_limit ) || 'open' === $event->age_limit ) {
                    $entry_valid = true;
                } elseif ( empty( $player_age ) ) {
                    $entry_valid = false;
                } elseif ( $event->age_limit >= 30 ) {
                    $age_limit = $event->age_limit;
                    if ( 'F' === $gender && ! empty( $event->age_offset ) ) {
                        $age_limit = $event->age_limit - $event->age_offset;
                    }
                    if ( $player_age < $age_limit ) {
                        $entry_valid = false;
                    } else {
                        $entry_valid = true;
                    }
                } elseif ( $player_age > $event->age_limit ) {
                    $entry_valid = false;
                } else {
                    $entry_valid = true;
                }
            }

            if ( $entry_valid ) {
                $player_entry  = new stdClass();
                $entry_details = $this->tournament_service->get_player_entry_for_event( $event->get_id(), $player_id, $season );
                if ( $entry_details ) {
                    $player_entry->team_id = $entry_details->team_id;
                    if ( ! empty( $entry_details->partner_id ) ) {
                        $player_entry->partner_id   = $entry_details->partner_id;
                        $player_entry->partner_name = $entry_details->partner_name;
                    }
                    $player_entry->event          = $event->name;
                    $player_entries[ $event->id ] = $player_entry;
                }
            } else {
                unset( $events[ $c ] );
            }
            ++$c;
        }

        $club_memberships = $player_id ? $this->registration_service->get_clubs_for_player( $player_id ) : [];
        $tournament_entry = $player_id ? $this->competition_entry_service->get_tournament_entry_by_tournament_and_player( $tournament_id, $player_id ) : null;

        $filename = ( ! empty( $template ) ) ? 'entry-tournament-' . $template : 'entry-tournament';

        return new Competition_Entry_Result(
            type: 'tournament',
            tournament: $tournament,
            player: $player,
            season: $tournament->season,
            events: $events,
            player_entries: $player_entries,
            club_memberships: $club_memberships,
            tournament_entry: $tournament_entry,
            user_id: $user_id,
            can_manage: $can_manage,
            template: $filename
        );
    }
}
