<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Competition;

use Closure;
use LogicException;
use Racketmanager\Application\Competition\Ports\Event_Query_Service_Interface;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Fixture\Rubber_Factory;
use Racketmanager\Exceptions\Player_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Request\Competition_Request;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use stdClass;

/**
 * Application service that runs repository-backed Event reads and writes.
 *
 * Supplies explicit repository arguments to Event data methods, giving callers
 * outside the domain a single dependency without storing collaborators on Event.
 */
class Event_Query_Service implements Event_Query_Service_Interface {
    private Event_Repository_Interface $event_repository;
    private Fixture_Repository_Interface|Closure|null $fixture_repository;
    private Rubber_Repository_Interface|Closure|null $rubber_repository;
    private League_Repository_Interface|Closure|null $league_repository;
    private Team_Repository_Interface|Closure|null $team_repository;
    private Club_Repository_Interface|Closure|null $club_repository;
    private Player_Repository_Interface|Closure|null $player_repository;

    /**
     * Initializes the service with the required event repository and optional related repositories.
     *
     * @param Event_Repository_Interface                $event_repository Event repository instance.
     * @param Fixture_Repository_Interface|Closure|null $fixture_repository Fixture repository instance, resolver closure, or null.
     * @param Rubber_Repository_Interface|Closure|null  $rubber_repository Rubber repository instance, resolver closure, or null.
     * @param League_Repository_Interface|Closure|null  $league_repository League repository instance, resolver closure, or null.
     * @param Team_Repository_Interface|Closure|null    $team_repository Team repository instance, resolver closure, or null.
     * @param Club_Repository_Interface|Closure|null    $club_repository Club repository instance, resolver closure, or null.
     * @param Player_Repository_Interface|Closure|null  $player_repository Player repository instance, resolver closure, or null.
     *
     * @return void
     */
    public function __construct(
        Event_Repository_Interface $event_repository,
        Fixture_Repository_Interface|Closure|null $fixture_repository = null,
        Rubber_Repository_Interface|Closure|null $rubber_repository = null,
        League_Repository_Interface|Closure|null $league_repository = null,
        Team_Repository_Interface|Closure|null $team_repository = null,
        Club_Repository_Interface|Closure|null $club_repository = null,
        Player_Repository_Interface|Closure|null $player_repository = null
    ) {
        $this->event_repository   = $event_repository;
        $this->fixture_repository = $fixture_repository;
        $this->rubber_repository  = $rubber_repository;
        $this->league_repository  = $league_repository;
        $this->team_repository    = $team_repository;
        $this->club_repository    = $club_repository;
        $this->player_repository  = $player_repository;
    }

    /**
     * Retrieves the event repository instance.
     *
     * @return Event_Repository_Interface Event repository instance.
     */
    public function get_event_repository(): Event_Repository_Interface {
        return $this->event_repository;
    }

    /**
     * Retrieves the configured fixture repository, resolving it from a closure when necessary.
     *
     * @return Fixture_Repository_Interface Fixture repository instance.
     *
     * @throws LogicException If no valid fixture repository has been configured.
     */
    public function get_fixture_repository(): Fixture_Repository_Interface {
        if ( $this->fixture_repository instanceof Closure ) {
            $this->fixture_repository = ( $this->fixture_repository )();
        }
        if ( ! $this->fixture_repository instanceof Fixture_Repository_Interface ) {
            throw new LogicException( 'Fixture_Repository_Interface is required by Event_Query_Service but was not configured.' );
        }
        return $this->fixture_repository;
    }

    /**
     * Resolves and returns the optional fixture repository when available.
     *
     * @return Fixture_Repository_Interface|null Fixture repository instance, or null when none is configured.
     */
    private function optional_fixture_repository(): ?Fixture_Repository_Interface {
        if ( $this->fixture_repository instanceof Closure ) {
            $this->fixture_repository = ( $this->fixture_repository )();
        }
        return $this->fixture_repository instanceof Fixture_Repository_Interface ? $this->fixture_repository : null;
    }

    /**
     * Retrieves the configured rubber repository, resolving it from a closure when necessary.
     *
     * @return Rubber_Repository_Interface Rubber repository instance.
     *
     * @throws LogicException If no valid rubber repository has been configured.
     */
    public function get_rubber_repository(): Rubber_Repository_Interface {
        if ( $this->rubber_repository instanceof Closure ) {
            $this->rubber_repository = ( $this->rubber_repository )();
        }
        if ( ! $this->rubber_repository instanceof Rubber_Repository_Interface ) {
            throw new LogicException( 'Rubber_Repository_Interface is required by Event_Query_Service but was not configured.' );
        }
        return $this->rubber_repository;
    }

    /**
     * Retrieves the configured league repository, resolving it from a closure when necessary.
     *
     * @return League_Repository_Interface League repository instance.
     *
     * @throws LogicException If no valid league repository has been configured.
     */
    public function get_league_repository(): League_Repository_Interface {
        if ( $this->league_repository instanceof Closure ) {
            $this->league_repository = ( $this->league_repository )();
        }
        if ( ! $this->league_repository instanceof League_Repository_Interface ) {
            throw new LogicException( 'League_Repository_Interface is required by Event_Query_Service but was not configured.' );
        }
        return $this->league_repository;
    }

    /**
     * Retrieves the configured team repository, resolving it from a closure when necessary.
     *
     * @return Team_Repository_Interface Team repository instance.
     *
     * @throws LogicException If the team repository is not configured or does not implement the required interface.
     */
    public function get_team_repository(): Team_Repository_Interface {
        if ( $this->team_repository instanceof Closure ) {
            $this->team_repository = ( $this->team_repository )();
        }
        if ( ! $this->team_repository instanceof Team_Repository_Interface ) {
            throw new LogicException( 'Team_Repository_Interface is required by Event_Query_Service but was not configured.' );
        }
        return $this->team_repository;
    }

    /**
     * Retrieves the configured club repository, resolving a lazy repository closure when necessary.
     *
     * @return Club_Repository_Interface Club repository instance.
     *
     * @throws LogicException If no valid club repository is configured.
     */
    public function get_club_repository(): Club_Repository_Interface {
        if ( $this->club_repository instanceof Closure ) {
            $this->club_repository = ( $this->club_repository )();
        }
        if ( ! $this->club_repository instanceof Club_Repository_Interface ) {
            throw new LogicException( 'Club_Repository_Interface is required by Event_Query_Service but was not configured.' );
        }
        return $this->club_repository;
    }

    /**
     * Retrieves the configured player repository, resolving it from a closure when necessary.
     *
     * @return Player_Repository_Interface Player repository instance.
     *
     * @throws LogicException If the player repository is not configured or does not implement the required interface.
     */
    public function get_player_repository(): Player_Repository_Interface {
        if ( $this->player_repository instanceof Closure ) {
            $this->player_repository = ( $this->player_repository )();
        }
        if ( ! $this->player_repository instanceof Player_Repository_Interface ) {
            throw new LogicException( 'Player_Repository_Interface is required by Event_Query_Service but was not configured.' );
        }
        return $this->player_repository;
    }

    /**
     * Retrieves an event by the specified identifier or name.
     *
     * Numeric event identifiers are treated as IDs when searching by name.
     *
     * @param int|string $event_id Event ID or event name to search for.
     * @param string     $search_term Search field to use, either 'id' or 'name'.
     *
     * @return Event|null Matching event instance, or null if no event is found.
     */
    public function get_event( int|string $event_id, string $search_term = 'id' ): ?Event {
        if ( 'name' === $search_term && is_numeric( $event_id ) ) {
            $search_term = 'id';
            $event_id    = (int) $event_id;
        }
        $event = 'name' === $search_term
            ? $this->event_repository->find_by_name( (string) $event_id )
            : $this->event_repository->find_by_id( $event_id );

        return $event instanceof Event ? $event : null;
    }

    /**
     * Sets the season for the given event, optionally preserving an existing requested season unless forced.
     *
     * @param Event       $event Event instance to update.
     * @param string|null $season Season value to set, or null to clear or defer the season value.
     * @param bool        $force_overwrite Whether to overwrite any requested or existing season value.
     *
     * @return void
     */
    public function set_season( Event $event, ?string $season = null, bool $force_overwrite = false ): void {
        if ( ! $force_overwrite ) {
            $requested_season = $this->resolve_requested_season( $event );
            if ( null !== $requested_season ) {
                $event->set_season( $requested_season, force_overwrite: false, strict: true );
                return;
            }
        }

        $event->set_season( $season, $force_overwrite );
    }

    /**
     * Resolves the requested season for the specified event.
     *
     * @param Event $event Event for which to resolve the requested season.
     *
     * @return string|null Requested season value, or null when the event does not have a valid ID.
     */
    public function resolve_requested_season( Event $event ): ?string {
        if ( ! isset( $event->id ) || $event->id <= 0 ) {
            return null;
        }
        return Competition_Request::season( (int) $event->id );
    }

    /**
     * Retrieves leagues associated with the given event, optionally returning only a count.
     *
     * @param Event $event Event for which leagues should be retrieved.
     * @param array $args Query arguments, including offset, limit, orderby, consolation, count, and season options.
     *
     * @return array|int Array of league instances when count is false, or the number of matching leagues when count is true.
     */
    public function get_leagues( Event $event, array $args = array() ): array|int {
        if ( ! isset( $event->id ) ) {
            return ! empty( $args['count'] ) ? 0 : array();
        }
        $defaults    = array(
            'offset'      => 0,
            'limit'       => 99999999,
            'orderby'     => array( 'title' => 'ASC' ),
            'consolation' => false,
            'count'       => false,
            'season'      => false,
        );
        $args        = array_merge( $defaults, $args );
        $consolation = $args['consolation'];
        $count       = $args['count'];

        $result = $this->event_repository->query_leagues( (int) $event->id, $args );
        if ( $count ) {
            return (int) $result;
        }

        $league_repo  = $this->get_league_repository();
        $leagues      = is_array( $result ) ? $result : [];
        $league_index = array();
        foreach ( $leagues as $i => $league ) {
            $league_id                  = is_object( $league ) ? (int) ( $league->id ?? 0 ) : (int) $league;
            $league_index[ $league_id ] = $i;
            $loaded_league              = $league_repo ? $league_repo->find_by_id( $league_id ) : ( $league instanceof League ? $league : null );
            if ( $consolation && ( ! $loaded_league || ! $loaded_league->championship?->is_consolation ) ) {
                unset( $leagues[ $i ] );
            }
            if ( isset( $leagues[ $i ] ) && $loaded_league ) {
                $leagues[ $i ] = $loaded_league;
            }
        }
        if ( ! $consolation ) {
            $event->set_leagues( $leagues );
            $event->set_league_index( $league_index );
        }

        return $leagues;
    }

    /**
     * Retrieves matches for an event using the supplied query arguments.
     *
     * Returns a count when the count argument is enabled; otherwise returns an array
     * of hydrated fixture objects with related data, display class, optional player
     * rubbers, formatted date, league data, and generated match title where needed.
     *
     * @param Event $event Event for which matches should be retrieved.
     * @param array $match_args Optional match query arguments and filters.
     *
     * @return array|int Array of hydrated matches, an empty array when no matches are found, or the match count when requested.
     */
    public function get_matches( Event $event, array $match_args = array() ): array|int {
        if ( ! isset( $event->id ) ) {
            return ! empty( $match_args['count'] ) ? 0 : array();
        }
        $defaults   = array(
            'leagueId'            => false,
            'season'              => false,
            'final'               => false,
            'orderby'             => array(
                'league_id' => 'ASC',
                'id'        => 'ASC',
            ),
            'confirmed'           => false,
            'player'              => false,
            'match_date'          => false,
            'time'                => false,
            'timeOffset'          => false,
            'history'             => false,
            'club'                => false,
            'league_name'         => false,
            'team_id'             => false,
            'team_name'           => false,
            'home_team'           => false,
            'away_team'           => false,
            'match_day'           => false,
            'home_club'           => false,
            'count'               => false,
            'confirmationPending' => false,
            'resultPending'       => false,
            'status'              => false,
            'pending'             => false,
        );
        $match_args = array_merge( $defaults, $match_args );
        $count      = $match_args['count'] ?? false;
        $player     = $match_args['player'] ?? false;

        $result = $this->event_repository->query_matches( (int) $event->id, $match_args );
        if ( $count ) {
            return (int) $result;
        }

        $fixture_repo = $this->get_fixture_repository();
        $rubber_repo  = $player ? $this->get_rubber_repository() : null;

        $matches          = is_array( $result ) ? $result : [];
        $class            = '';
        $hydrated_matches = [];

        foreach ( $matches as $match ) {
            $match_id = is_object( $match ) ? (int) ( $match->id ?? 0 ) : (int) $match;
            $fixture  = $fixture_repo->find_by_id_with_relations( $match_id );
            if ( ! $fixture ) {
                continue;
            }
            $class = ( 'alternate' === $class ) ? '' : 'alternate';
            $fixture->set_class( $class );
            if ( $player && $rubber_repo ) {
                $fixture->set_rubbers( $rubber_repo->find_by_fixture_id( (int) $fixture->get_id(), (int) $player ) );
            }
            if ( ! empty( $fixture->date ) && 19 === strlen( (string) $fixture->date ) && preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string) $fixture->date ) ) {
                $fixture->set_date( substr( (string) $fixture->date, 0, 16 ) );
            }
            $fixture->get_league();
            if ( empty( $fixture->match_title ) ) {
                $fixture->set_match_title( $fixture->get_title( __( 'Bye', 'racketmanager' ), __( 'Unknown', 'racketmanager' ) ) );
            }
            $hydrated_matches[] = $fixture;
        }

        return $hydrated_matches;
    }

    /**
     * Retrieves teams associated with an event, optionally returning only the matching team count.
     *
     * Supports filtering, pagination, sorting, and lookup options through the arguments array. When
     * count is false, returned team records are enriched with roster data, club data, display title,
     * player information or player count, and event-specific team info.
     *
     * @param Event $event Event whose teams should be retrieved.
     * @param array $args Optional query arguments, including offset, limit, season, orderby, club, league, status, count, name, player, and partner.
     *
     * @return array|int Array of event team records when count is false, or the number of matching teams when count is true.
     */
    public function get_teams( Event $event, array $args = array() ): array|int {
        if ( ! isset( $event->id ) ) {
            return ! empty( $args['count'] ) ? 0 : array();
        }
        $defaults = array(
            'offset'  => 0,
            'limit'   => 99999999,
            'season'  => false,
            'orderby' => array(
                'league_name' => 'ASC',
                'name'        => 'ASC',
            ),
            'club'    => false,
            'league'  => false,
            'status'  => false,
            'count'   => false,
            'name'    => false,
            'player'  => false,
            'partner' => false,
        );
        $args     = array_merge( $defaults, $args );
        $season   = $args['season'];
        $count    = $args['count'];

        if ( ! $count ) {
            $club_repo = $this->get_club_repository();
            $team_repo = $this->get_team_repository();
        }

        $result = $this->event_repository->query_teams( (int) $event->id, $args );
        if ( $count ) {
            return (int) $result;
        }

        $event_teams = is_array( $result ) ? $result : [];
        foreach ( $event_teams as $i => $event_team ) {
            $event_team->roster = maybe_unserialize( $event_team->roster );
            $event_team->club   = $club_repo->find_by_id( (int) $event_team->club_id );
            if ( str_contains( (string) $event_team->name, '_' ) ) {
                $team_name = Util::generate_team_name( (string) $event_team->name, $this->get_fixture_repository() );
                if ( ! empty( $team_name ) ) {
                    $event_team->title = $team_name;
                }
            } else {
                $event_team->title = $event_team->name;
            }
            if ( 'P' === $event_team->team_type && ! empty( $event_team->roster ) ) {
                $team                = $team_repo->find_by_id( (int) $event_team->team_id );
                $event_team->players = $team?->players ?? array();
                $p                   = 1;
                if ( ! empty( $team?->players ) ) {
                    foreach ( $team->players as $team_player ) {
                        $event_team->player[ $p ]    = $team_player->get_fullname();
                        $event_team->player_id[ $p ] = $team_player->get_id();
                        ++$p;
                    }
                }
            } elseif ( $event_team->club ) {
                $event_team->player_count = $this->get_players(
                    $event,
                    array(
                        'season' => $season,
                        'count'  => true,
                        'team'   => $event_team->team_id,
                    )
                );
            } else {
                $event_team->player_count = 0;
            }
            $event_team->info  = $this->get_team_info( $event, (int) $event_team->team_id );
            $event_teams[ $i ] = $event_team;
        }

        $event->set_event_teams( $event_teams );

        return $event_teams;
    }

    /**
     * Retrieves team information for the given event season and enriches each team with display-ready details.
     *
     * @param Event $event Event for which team information should be retrieved.
     * @param array $args Optional query arguments used to filter or customize the team lookup.
     *
     * @return array List of team records enriched with captain, contact, club, roster, stadium, title, and row class information, or an empty array when the event is invalid or has no season.
     */
    public function get_teams_info( Event $event, array $args = array() ): array {
        if ( ! isset( $event->id ) ) {
            return array();
        }
        $season = $event->get_season();
        if ( empty( $season ) ) {
            return array();
        }
        $club_repo = $this->get_club_repository();
        $teams     = $this->event_repository->get_teams_info( (int) $event->id, $season, $args );

        $class = '';
        foreach ( $teams as $i => $team ) {
            $class        = ( 'alternate' === $class ) ? '' : 'alternate';
            $captain_id   = $team->captain ?? null;
            $captain      = $captain_id ? get_userdata( $captain_id ) : null;
            $team->roster = isset( $team->roster ) ? maybe_unserialize( $team->roster ) : null;
            $team->title  = htmlspecialchars( stripslashes( (string) ( $team->title ?? '' ) ), ENT_QUOTES );
            if ( ! empty( $captain ) ) {
                $team->captain      = $captain->display_name;
                $team->captain_id   = $captain->ID;
                $team->contactno    = get_user_meta( $captain->ID, 'contactno', true );
                $team->contactemail = $captain->user_email;
            } else {
                $team->captain      = 'Unknown';
                $team->captain_id   = '';
                $team->contactno    = '';
                $team->contactemail = '';
            }
            $team->club_id = stripslashes( (string) ( $team->club_id ?? '' ) );
            $team->club    = ! empty( $team->club_id ) ? $club_repo->find_by_id( (int) $team->club_id ) : null;
            $team->stadium = stripslashes( (string) ( $team->stadium ?? '' ) );
            $team->class   = $class;
            $teams[ $i ]   = $team;
        }

        return $teams;
    }

    /**
     * Retrieves team information for the given event and team, including season-specific details, match day number, and captain contact data when available.
     *
     * @param Event $event Event used to determine the event ID and season.
     * @param int   $team_id Team identifier.
     *
     * @return object|null Team information object, or null when the event ID is unavailable or no team is found.
     */
    public function get_team_info( Event $event, int $team_id ): ?object {
        if ( ! isset( $event->id ) ) {
            return null;
        }
        $season = $event->get_season();
        $team   = $this->event_repository->get_team_info( (int) $event->id, $team_id, $season );

        if ( $team ) {
            if ( ! empty( $team->match_day ) ) {
                $team->match_day_num = Util_Lookup::get_match_day_number( $team->match_day );
            } else {
                $team->match_day_num = null;
            }
            $captain = get_userdata( $team->captain );
            if ( $captain ) {
                $team->captain      = $captain->display_name;
                $team->captain_id   = $captain->ID;
                $team->contactno    = get_user_meta( $captain->ID, 'contactno', true );
                $team->contactemail = $captain->user_email;
            } else {
                $team->captain      = 'Unknown';
                $team->captain_id   = '';
                $team->contactno    = '';
                $team->contactemail = '';
            }
        }

        return $team;
    }

    /**
     * Retrieves the players associated with an event.
     *
     * Applies the provided query arguments, defaults the season to the event's current season when omitted,
     * filters out missing and system-record players, stores the resolved players on the event, and returns
     * either the player list or a count when requested.
     *
     * @param Event        $event Event for which players should be retrieved.
     * @param array|string $args Optional query arguments, including offset, limit, season, orderby, club, team, and count.
     *
     * @return array|int Array of player entities for the event, or the total player count when count is requested.
     */
    public function get_players( Event $event, array|string $args = array() ): array|int {
        if ( ! isset( $event->id ) ) {
            return ! empty( $args['count'] ) ? 0 : array();
        }
        $defaults = array(
            'offset'  => 0,
            'limit'   => 99999999,
            'season'  => false,
            'orderby' => array(),
            'club'    => false,
            'team'    => false,
            'count'   => false,
        );
        $args     = array_merge( $defaults, (array) $args );
        $count    = $args['count'];
        if ( empty( $args['season'] ) ) {
            $args['season'] = is_array( $event->current_season ) ? ( $event->current_season['name'] ?? '' ) : '';
        }

        $is_player_entry = (bool) ( $event->competition->is_player_entry ?? false );
        $result          = $this->event_repository->query_players( (int) $event->id, $is_player_entry, $args );
        if ( $count ) {
            return (int) $result;
        }

        $player_repo   = $this->get_player_repository();
        $players       = is_array( $result ) ? $result : [];
        $event_players = array();
        foreach ( $players as $player ) {
            $player_entity = null;
            if ( $player_repo ) {
                try {
                    $player_entity = $player_repo->find( (int) $player->player_id );
                } catch ( Player_Not_Found_Exception ) {
                    $player_entity = null;
                }
            }
            if ( ! $player_entity || $player_entity->system_record ) {
                continue;
            }
            $event_players[] = $player_entity;
        }
        asort( $event_players );
        $event->set_players( $event_players );
        return $event->players;
    }

    /**
     * Retrieves the constitution entries associated with an event.
     *
     * Applies the provided query arguments, returns a count when requested, resolves old league names
     * for returned constitution entries, stores the entries on the event, and returns the constitution list.
     *
     * @param Event $event Event for which constitution entries should be retrieved.
     * @param array $args Optional query arguments, including offset, limit, season, oldseason, club, and count.
     *
     * @return array|int Array of constitution entries for the event, or the total constitution count when requested.
     */
    public function get_constitution( Event $event, array $args = array() ): array|int {
        if ( ! isset( $event->id ) ) {
            return ! empty( $args['count'] ) ? 0 : array();
        }
        $defaults = array(
            'offset'    => 0,
            'limit'     => 99999999,
            'season'    => false,
            'oldseason' => false,
            'club'      => false,
            'count'     => false,
        );
        $args     = array_merge( $defaults, $args );
        $count    = $args['count'];

        $result = $this->event_repository->query_constitution( $event->id, $args );
        if ( $count ) {
            return (int) $result;
        }

        $league_repo   = $this->get_league_repository();
        $constitutions = is_array( $result ) ? $result : [];
        foreach ( $constitutions as $i => $constitution ) {
            $old_league_name = '';
            if ( isset( $constitution->old_league_id ) ) {
                $loaded_league   = $league_repo?->find_by_id( (int) $constitution->old_league_id );
                $old_league_name = $loaded_league?->get_name() ?? '';
                if ( '' === $old_league_name ) {
                    $old_league_name = (string) ( $loaded_league?->name ?? $loaded_league?->title ?? '' );
                }
            }
            $constitution->old_league_name  = $old_league_name;
            $constitution->old_league_title = $old_league_name;
            $constitutions[ $i ]            = $constitution;
        }

        $event->set_constitutions( $constitutions );

        return $constitutions;
    }

    /**
     * Builds and assigns the constitution rows for an event.
     *
     * Merges the provided arguments with default query values, retrieves the constitution data
     * for the event, normalizes the returned rows, stores them on the event, and returns them.
     *
     * @param Event        $event Event for which the constitution should be built.
     * @param array|string $args Optional query arguments, including offset, limit, season, and club.
     *
     * @return array Normalized constitution rows for the event, or an empty array when the event has no ID.
     */
    public function build_constitution( Event $event, array|string $args = array() ): array {
        if ( ! isset( $event->id ) ) {
            return array();
        }
        $defaults = array(
            'offset' => 0,
            'limit'  => 99999999,
            'season' => false,
            'club'   => false,
        );
        $args     = array_merge( $defaults, (array) $args );

        $constitutions = $this->event_repository->build_constitution( $event->id, $args );
        $constitutions = Competition_Season_Service::normalize_constitution_rows( is_array( $constitutions ) ? $constitutions : array() );

        $event->set_constitutions( $constitutions );

        return $constitutions;
    }

    /**
     * Retrieves the clubs associated with an event.
     *
     * Applies the provided query arguments, defaults the season to the event's current season when omitted,
     * loads matching club entities, assigns team and player counts to each loaded club, stores the resolved
     * clubs on the event, and returns either the club list or a count when requested.
     *
     * @param Event        $event Event for which clubs should be retrieved.
     * @param array|string $args Optional query arguments, including offset, limit, season, status, count, and name.
     *
     * @return array|int Array of club entities for the event, or the total club count when count is requested.
     */
    public function get_clubs( Event $event, array|string $args = array() ): array|int {
        if ( ! isset( $event->id ) ) {
            return ! empty( $args['count'] ) ? 0 : array();
        }
        $defaults       = array(
            'offset' => 0,
            'limit'  => 99999999,
            'season' => false,
            'status' => false,
            'count'  => false,
            'name'   => false,
        );
        $args           = array_merge( $defaults, (array) $args );
        $count          = $args['count'];
        $season         = $args['season'] ?: ( $event->current_season['name'] ?? false );
        $args['season'] = $season;

        if ( ! $count ) {
            $club_repo   = $this->get_club_repository();
            $player_repo = $this->get_player_repository();
        }

        $result = $this->event_repository->query_clubs( $event->id, $args );
        if ( $count ) {
            return (int) $result;
        }

        $event_clubs = is_array( $result ) ? $result : [];
        foreach ( $event_clubs as $i => $event_club ) {
            $team_count  = (int) $event_club->team_count ?? 0;
            $loaded_club = $club_repo?->find_by_id( (int) $event_club->club_id );
            if ( ! $loaded_club ) {
                continue;
            }
            $player_count = (int) (
                $this->get_players(
                    $event,
                    [
                        'season' => $season,
                        'count'  => true,
                        'club'   => $loaded_club->id,
                    ]
                ) ?? 0
            );
            $loaded_club->set_team_count( $team_count );
            $loaded_club->set_player_count( $player_count );
            $event_clubs[ $i ] = $loaded_club;
        }

        $event->set_clubs( $event_clubs );

        return $event_clubs;
    }

    /**
     * Retrieves player statistics for an event.
     *
     * Returns an empty array when the event does not have an ID; otherwise delegates retrieval
     * to the event repository using the provided arguments.
     *
     * @param Event $event Event for which player statistics should be retrieved.
     * @param array $args Arguments used to filter or customize the player statistics query.
     *
     * @return array Player statistics for the event, or an empty array when the event ID is not set.
     */
    public function get_player_stats( Event $event, array $args ): array {
        if ( ! isset( $event->id ) ) {
            return array();
        }
        return $this->event_repository->get_player_stats( $event->id, $args );
    }

    /**
     * Determines whether an event has associated leagues.
     *
     * Counts the leagues linked to the event and returns zero when the event does not have an ID.
     *
     * @param Event $event Event for which associated leagues should be counted.
     *
     * @return int Number of leagues associated with the event.
     */
    public function has_leagues( Event $event ): int {
        if ( ! isset( $event->id ) ) {
            return 0;
        }
        return $this->event_repository->count_leagues( $event->id );
    }

    /**
     * Sets the league count for an event when a total count is requested.
     *
     * Counts leagues through the event repository and stores the result on the event when the event has an ID.
     *
     * @param Event $event Event for which the league count should be set.
     * @param bool  $total Whether to calculate and set the total league count.
     *
     * @return void
     */
    public function set_num_leagues( Event $event, bool $total = false ): void {
        if ( true === $total && isset( $event->id ) ) {
            $event->set_league_count( $this->event_repository->count_leagues( $event->id ) );
        }
    }

    /**
     * Retrieves the primary league associated with the given event.
     *
     * @param Event $event Event for which to resolve the primary league.
     *
     * @return League|null Primary league instance, or null if no primary league is found.
     */
    public function get_primary_league( Event $event ): ?League {
        if ( ! empty( $event->primary_league_obj ) && $event->primary_league_obj instanceof League ) {
            return $event->primary_league_obj;
        }

        if ( ! empty( $event->primary_league ) && $event->primary_league > 0 ) {
            $primary_league_id = $event->primary_league;

            if ( is_array( $event->leagues ) ) {
                if ( is_array( $event->league_index ?? null ) && isset( $event->league_index[ $primary_league_id ] ) ) {
                    $index = $event->league_index[ $primary_league_id ];
                    if ( isset( $event->leagues[ $index ] ) ) {
                        $candidate = $event->leagues[ $index ];
                        if ( $candidate instanceof League && isset( $candidate->id ) && (int) $candidate->id === $primary_league_id ) {
                            return $candidate;
                        }
                    }
                }

                foreach ( $event->leagues as $league ) {
                    if ( $league instanceof League && isset( $league->id ) && (int) $league->id === $primary_league_id ) {
                        return $league;
                    }
                }
            }

            return $this->get_league_repository()->find_by_id( $primary_league_id );
        }

        $leagues = ! empty( $event->leagues ) ? $event->leagues : ( ! empty( $event->id ) ? $this->get_leagues( $event ) : array() );
        if ( is_array( $leagues ) && ! empty( $leagues ) ) {
            $first_league = reset( $leagues );
            if ( $first_league instanceof League ) {
                return $first_league;
            }
            if ( is_object( $first_league ) && ! empty( $first_league->id ) ) {
                $hydrated = $this->get_league_repository()->find_by_id( (int) $first_league->id );
                if ( $hydrated instanceof League ) {
                    $first_key = array_key_first( $leagues );
                    if ( null !== $first_key && is_array( $event->leagues ) && isset( $event->leagues[ $first_key ] ) ) {
                        $updated_leagues               = $event->leagues;
                        $updated_leagues[ $first_key ] = $hydrated;
                        $event->set_leagues( $updated_leagues );
                    }
                    return $hydrated;
                }
            }
        }

        return null;
    }

    /**
     * Gets the primary league ID for an event.
     *
     * Uses the event's primary league when available, otherwise falls back to the
     * first league associated with the event.
     *
     * @param Event $event Event to retrieve the primary league ID for.
     *
     * @return int|null Primary league ID, or null when no valid league ID is available.
     */
    public function get_primary_league_id( Event $event ): ?int {
        if ( ! empty( $event->primary_league ) && $event->primary_league > 0 ) {
            return $event->primary_league;
        }

        $primary_league = $this->get_primary_league( $event );
        return ( $primary_league && isset( $primary_league->id ) && (int) $primary_league->id > 0 ) ? (int) $primary_league->id : null;
    }

    /**
     * Reloads settings for an event from the repository.
     *
     * If the event has no ID, no settings are loaded. Otherwise, the stored settings are retrieved
     * and applied to the event.
     *
     * @param Event $event Event whose settings should be reloaded.
     *
     * @return void
     */
    public function reload_settings( Event $event ): void {
        if ( ! isset( $event->id ) ) {
            return;
        }
        $settings = $this->event_repository->get_settings( $event->id );
        $event->apply_settings( $settings );
    }

    /**
     * Updates an event's settings and persists related rules.
     *
     * Applies the supplied settings to the event, creates reverse rubbers for eligible existing fixtures when
     * reverse rubbers are newly enabled, and updates the event settings and rule values in the repository when
     * the event has an identifier.
     *
     * @param Event $event Event whose settings should be updated.
     * @param array $settings Settings to apply to the event.
     *
     * @return void
     */
    public function set_settings( Event $event, array $settings ): void {
        $old_reverse_rubbers = $event->settings['reverse_rubbers'] ?? null;
        $event->set_settings( $settings );
        $num_rubbers = $event->num_rubbers ?? null;
        $num_sets    = $event->num_sets ?? null;
        $type        = $event->type;
        if ( isset( $settings['reverse_rubbers'] ) && '1' === $settings['reverse_rubbers'] ) {
            $match_args             = array();
            $match_args['season']   = $event->current_season['name'] ?? null;
            $match_args['event_id'] = $event->id;
            if ( null === $old_reverse_rubbers || $old_reverse_rubbers !== $settings['reverse_rubbers'] ) {
                $raw_matches = $this->event_repository->query_matches( $event->id, $match_args );
                $matches     = array();
                if ( is_array( $raw_matches ) ) {
                    foreach ( $raw_matches as $row ) {
                        $match_id = is_object( $row ) ? $row->id ?? 0 : (int) $row;
                        $fixture  = $this->get_fixture_repository()->find_by_id_with_relations( $match_id );
                        if ( $fixture ) {
                            $matches[] = $fixture;
                        }
                    }
                }

                $rubber_repo = $this->get_rubber_repository();
                $league_repo = $this->get_league_repository();

                foreach ( $matches as $fixture ) {
                    if ( ! is_object( $fixture ) || ! method_exists( $fixture, 'get_id' ) ) {
                        continue;
                    }
                    $match_id              = (int) $fixture->get_id();
                    $rubber_count          = $rubber_repo->count_by_fixture_id( $match_id );
                    $league_id             = $fixture->get_league_id();
                    $league                = $league_id ? $league_repo->find_by_id( $league_id ) : null;
                    $num_rubbers_threshold = $league ? $league->num_rubbers ?? 0 : 0;
                    $total_rubbers         = $rubber_count * 2;
                    if ( $num_rubbers_threshold > 0 && $rubber_count === $num_rubbers_threshold ) {
                        for ( $ix = $rubber_count + 1; $ix <= $total_rubbers; $ix++ ) {
                            $rubber_data                = new stdClass();
                            $rubber_data->type          = $event->type;
                            $rubber_data->rubber_number = $ix;
                            $rubber_data->date          = $fixture->get_date();
                            $rubber_data->match_id      = $fixture->get_id();
                            $rubber_entity              = Rubber_Factory::from_object( $rubber_data );
                            $rubber_repo->save( $rubber_entity );
                        }
                    }
                }
            }
        }

        if ( isset( $event->id ) ) {
            $this->event_repository->update_settings_and_rules( $event->id, $settings, $num_rubbers, $num_sets, $type );
        }
    }

    /**
     * Updates an event season and persists the event's seasons when the event has been modified.
     *
     * Applies the supplied season data to the event, and when the update succeeds for an existing event,
     * saves the updated seasons collection through the event repository.
     *
     * @param Event $event Event whose season data should be updated.
     * @param array $season Season data to apply to the event.
     *
     * @return void
     */
    public function update_season( Event $event, array $season ): void {
        if ( $event->update_season( $season ) && isset( $event->id ) ) {
            $this->event_repository->update_seasons( $event->id, $event->get_seasons() );
        }
    }

    /**
     * Updates the seasons assigned to an event.
     *
     * Applies the season data to the event entity and persists the changes to the event repository
     * when the event was updated successfully and has an identifier.
     *
     * @param Event $event Event whose seasons should be updated.
     * @param array $seasons Season data to assign to the event.
     *
     * @return bool True when the event seasons were updated, false otherwise.
     */
    public function update_seasons( Event $event, array $seasons ): bool {
        $updated = $event->update_seasons( $seasons );
        if ( $updated && isset( $event->id ) ) {
            $this->event_repository->update_seasons( $event->id, $seasons );
        }
        return $updated;
    }

    /**
     * Marks teams from a club as withdrawn for an event season.
     *
     * Delegates the withdrawal update to the event repository when the event has a valid ID.
     *
     * @param Event     $event Event for which teams should be marked as withdrawn.
     * @param string    $season Season in which the teams should be marked as withdrawn.
     * @param int       $club Club ID whose teams should be withdrawn.
     * @param false|int $team Optional team ID to withdraw, or false to withdraw all matching teams for the club.
     *
     * @return bool True when the teams are successfully marked as withdrawn, false otherwise.
     */
    public function mark_teams_withdrawn( Event $event, string $season, int $club, false|int $team = false ): bool {
        if ( ! isset( $event->id ) ) {
            return false;
        }
        return $this->event_repository->mark_teams_withdrawn( $event->id, $season, $club, $team );
    }

    /**
     * Marks a team as entered for an event in the specified season.
     *
     * Returns false when the event does not have an identifier; otherwise delegates the update to the event repository.
     *
     * @param Event  $event Event for which the team entry should be marked.
     * @param int    $team Team identifier to mark as entered.
     * @param string $season Season in which the team is entered.
     *
     * @return bool True when the team entry is successfully marked, false otherwise.
     */
    public function mark_teams_entered( Event $event, int $team, string $season ): bool {
        if ( ! isset( $event->id ) ) {
            return false;
        }
        return $this->event_repository->mark_teams_entered( $event->id, $team, $season );
    }

    /**
     * Retrieves and enriches a club with event-specific data for the current season.
     *
     * Loads the club's active teams, upcoming matches grouped by match date, latest results grouped by match date,
     * and associated players. The loaded data is assigned using Club setters when available, or added as dynamic
     * object properties otherwise.
     *
     * @param Event  $event Event context used to determine the current season and retrieve related club data.
     * @param object $club Club object to enrich with teams, matches, results, and players.
     *
     * @return object The enriched club object.
     */
    public function get_club( Event $event, object $club ): object {
        $season       = $event->current_season['name'] ?? '';
        $teams        = $this->get_teams(
            $event,
            array(
                'club'   => $club->id,
                'season' => $season,
                'status' => 1,
            )
        );
        $club_matches = array();
        $matches      = $this->get_matches(
            $event,
            array(
                'season'  => $season,
                'club'    => $club->id,
                'time'    => 'next',
                'orderby' => array(
                    'date'      => 'ASC',
                    'league_id' => 'DESC',
                ),
            )
        );
        foreach ( $matches as $match ) {
            $key = substr( (string) $match->date, 0, 10 );
            if ( false === array_key_exists( $key, $club_matches ) ) {
                $club_matches[ $key ] = array();
            }
            $club_matches[ $key ][] = $match;
        }
        $club_results = array();
        $matches      = $this->get_matches(
            $event,
            array(
                'season'  => $season,
                'club'    => $club->id,
                'time'    => 'latest',
                'orderby' => array(
                    'date'      => 'ASC',
                    'league_id' => 'DESC',
                ),
            )
        );
        foreach ( $matches as $match ) {
            $key = substr( (string) $match->date, 0, 10 );
            if ( false === array_key_exists( $key, $club_results ) ) {
                $club_results[ $key ] = array();
            }
            $club_results[ $key ][] = $match;
        }
        $players = $this->get_players(
            $event,
            array(
                'club'   => $club->id,
                'season' => $season,
            )
        );

        if ( $club instanceof Club ) {
            $club->set_teams( $teams );
            $club->set_matches( $club_matches );
            $club->set_results( $club_results );
            $club->set_players( $players );
        } else {
            $club->teams   = $teams;
            $club->matches = $club_matches;
            $club->results = $club_results;
            $club->players = $players;
        }

        return $club;
    }
}
