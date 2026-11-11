<?php
/**
 * Player_Query_Service class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

declare( strict_types=1 );

namespace Racketmanager\Services;

use ArrayAccess;
use Closure;
use Racketmanager\Exceptions\Repository_Missing_Exception;
use Racketmanager\Exceptions\Service_Missing_Exception;
use Racketmanager\Services\Competition\Event_Query_Service;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\Event_Factory;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\League_Factory;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Repositories\Interfaces\Competition_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Statistics_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Rubber_Repository_Interface;

/**
 * Service for querying player matches, competitions, and raw match statistics.
 */
class Player_Query_Service {
    /**
     * @var Player_Statistics_Repository_Interface|null
     */
    private ?Player_Statistics_Repository_Interface $statistics_repository;
    /**
     * @var Competition_Repository_Interface|null
     */
    private ?Competition_Repository_Interface $competition_repository;
    /**
     * @var Fixture_Repository_Interface|null
     */
    private ?Fixture_Repository_Interface $fixture_repository;
    /**
     * @var Event_Repository_Interface|null
     */
    private ?Event_Repository_Interface $event_repository;
    /**
     * @var Rubber_Repository_Interface|null
     */
    private ?Rubber_Repository_Interface $rubber_repository;

    /**
     * @param Player_Statistics_Repository_Interface|null $statistics_repository Player statistics repository.
     * @param Competition_Repository_Interface|null       $competition_repository Competition repository.
     * @param Fixture_Repository_Interface|null           $fixture_repository Fixture repository.
     * @param Event_Repository_Interface|null             $event_repository Event repository.
     * @param Rubber_Repository_Interface|null            $rubber_repository Rubber repository.
     * @param Event_Query_Service|Closure|null            $event_query_service Event query service.
     * @param League_Query_Service|Closure|null           $league_query_service League query service.
     */
    public function __construct(
        ?Player_Statistics_Repository_Interface $statistics_repository = null,
        ?Competition_Repository_Interface $competition_repository = null,
        ?Fixture_Repository_Interface $fixture_repository = null,
        ?Event_Repository_Interface $event_repository = null,
        ?Rubber_Repository_Interface $rubber_repository = null,
        private Event_Query_Service|Closure|null $event_query_service = null,
        private League_Query_Service|Closure|null $league_query_service = null
    ) {
        $this->statistics_repository  = $statistics_repository;
        $this->competition_repository = $competition_repository;
        $this->fixture_repository     = $fixture_repository;
        $this->event_repository       = $event_repository;
        $this->rubber_repository      = $rubber_repository;
    }

    /**
     * @return Event_Query_Service
     */
    private function get_event_query_service(): Event_Query_Service {
        if ( $this->event_query_service instanceof Closure ) {
            $this->event_query_service = ( $this->event_query_service )();
        }
        if ( null === $this->event_query_service ) {
            $this->event_query_service = new Event_Query_Service( $this->event_repository, $this->fixture_repository, $this->rubber_repository );
        }

        return $this->event_query_service;
    }

    /**
     * @return League_Query_Service
     * @throws Service_Missing_Exception Missing League_Query_Service.
     */
    private function get_league_query_service(): League_Query_Service {
        if ( $this->league_query_service instanceof Closure ) {
            $this->league_query_service = ( $this->league_query_service )();
        }
        if ( ! $this->league_query_service instanceof League_Query_Service ) {
            throw new Service_Missing_Exception( 'Player_Query_Service requires a League_Query_Service.' );
        }

        return $this->league_query_service;
    }

    /**
     * Resolve a league grouping the way the legacy get_league() helper did for objects:
     * a League entity is returned as is, a raw row is built through League_Factory
     * (event from the row or the event repository) and its season is set.
     *
     * @param object $grouping grouping object.
     *
     * @return League|null
     */
    private function resolve_league( object $grouping ): ?League {
        if ( $grouping instanceof League ) {
            return $grouping;
        }
        $event = null;
        if ( isset( $grouping->event ) && is_object( $grouping->event ) ) {
            $event = $grouping->event instanceof Event ? $grouping->event : Event_Factory::from_object( $grouping->event );
        } elseif ( ! empty( $grouping->event_id ) && $this->event_repository ) {
            $event = $this->event_repository->find_by_id( (int) $grouping->event_id );
        }
        $league = League_Factory::from_object( $grouping, $event );
        if ( $league ) {
            $this->get_league_query_service()->set_season( $league, false, true );
        }
        return $league;
    }

    /**
     * Resolve an event grouping the way the legacy get_event() helper did for objects:
     * an Event entity is returned as is, a raw row is hydrated with its competition.
     *
     * @param object $grouping grouping object.
     *
     * @return Event|null
     */
    private function resolve_event( object $grouping ): ?Event {
        if ( $grouping instanceof Event ) {
            return $grouping;
        }
        $competition = null;
        if ( ! empty( $grouping->competition_id ) && $this->competition_repository ) {
            $competition = $this->competition_repository->find_by_id( (int) $grouping->competition_id );
        }
        return Event_Factory::from_object( $grouping, $competition instanceof Competition ? $competition : null );
    }

    /**
     * @return Player_Statistics_Repository_Interface
     * @throws Repository_Missing_Exception In case no repository is available.
     */
    private function get_statistics_repository(): Player_Statistics_Repository_Interface {
        if ( $this->statistics_repository ) {
            return $this->statistics_repository;
        }

        throw new Repository_Missing_Exception( 'Player_Query_Service requires a Player_Statistics_Repository_Interface.' );
    }

    /**
     * Normalise a legacy search argument (false / '' / null mean "no filter").
     *
     * @param mixed $value legacy search argument.
     * @return string|null
     */
    private function filter_value( mixed $value ): ?string {
        if ( false === $value || null === $value || '' === $value ) {
            return null;
        }

        return (string) $value;
    }

    /**
     * @return Competition_Repository_Interface
     * @throws Repository_Missing_Exception In case no repository is available.
     */
    private function get_competition_repository(): Competition_Repository_Interface {
        if ( $this->competition_repository ) {
            return $this->competition_repository;
        }

        throw new Repository_Missing_Exception( 'Player_Query_Service requires a Competition_Repository_Interface.' );
    }

    /**
     * @return Fixture_Repository_Interface
     * @throws Repository_Missing_Exception In case no repository is available.
     */
    private function get_fixture_repository(): Fixture_Repository_Interface {
        if ( $this->fixture_repository ) {
            return $this->fixture_repository;
        }

        throw new Repository_Missing_Exception( 'Player_Query_Service requires a Fixture_Repository_Interface.' );
    }

    /**
     * Get matches for a player.
     *
     * @param Player      $player player object.
     * @param object|null $grouping grouping object.
     * @param string|null $season season string.
     * @param string      $match_source match source string.
     * @param int|null    $period period integer.
     * @param string|null $type type string.
     * @return array
     */
    public function get_matches( Player $player, ?object $grouping, ?string $season, string $match_source, ?int $period = null, ?string $type = null ): array {
        $matches    = $this->fetch_matches( $player, $grouping, $season, $match_source, $period, $type );
        $stats_ref  = array();
        $league_ref = 0;

        foreach ( $matches as $match ) {
            $league_ref = $this->determine_league_ref( $match, $match_source );
            $this->record_player_match( $player, $match, $match_source );
            $this->accumulate_match_statistics( $stats_ref, $league_ref, $match, $player );
        }

        if ( $league_ref ) {
            $player->statistics = $stats_ref;
        } else {
            $player->statistics = $stats_ref[0] ?? array();
        }

        return $player->matches;
    }

    /**
     * Fetch matches based on match source.
     *
     * @param Player      $player player object.
     * @param object|null $grouping grouping object.
     * @param string|null $season season string.
     * @param string      $match_source match source string.
     * @param int|null    $period period integer.
     * @param string|null $type type string.
     * @return array
     */
    private function fetch_matches( Player $player, ?object $grouping, ?string $season, string $match_source, ?int $period, ?string $type ): array {
        return match ( $match_source ) {
            'league'      => $this->fetch_league_matches( $player, $grouping, $season ),
            'event'       => $this->fetch_event_matches( $player, $grouping, $season ),
            'competition' => $this->fetch_competition_matches( $player, $grouping, $season ),
            'all'         => $this->fetch_all_matches( $player, $season, $period, $type ),
            default       => array(),
        };
    }

    /**
     * Fetch matches for a league.
     *
     * @param Player      $player player object.
     * @param object|null $grouping grouping object.
     * @param string|null $season season string.
     * @return array
     */
    private function fetch_league_matches( Player $player, ?object $grouping, ?string $season ): array {
        $league = $grouping ? $this->resolve_league( $grouping ) : null;
        if ( ! $league ) {
            return array();
        }

        $matches = $this->get_league_query_service()->get_matches(
            $league,
            array(
                'season'    => $season,
                'player'    => $player->id,
                'match_day' => false,
                'final'     => 'all',
                'orderby'   => array(
                    'date' => 'ASC',
                ),
            )
        );

        return is_array( $matches ) ? $matches : array();
    }

    /**
     * Fetch matches for an event.
     *
     * @param Player      $player player object.
     * @param object|null $grouping grouping object.
     * @param string|null $season season string.
     * @return array
     */
    private function fetch_event_matches( Player $player, ?object $grouping, ?string $season ): array {
        $event = $grouping ? $this->resolve_event( $grouping ) : null;
        if ( ! $event ) {
            return array();
        }

        $matches = $this->get_event_query_service()->get_matches(
            $event,
            array(
                'season'  => $season,
                'player'  => $player->id,
                'orderby' => array(
                    'date'      => 'ASC',
                    'league_id' => 'DESC',
                ),
            )
        );

        return is_array( $matches ) ? $matches : array();
    }

    /**
     * Fetch matches for a competition.
     *
     * @param Player      $player player object.
     * @param object|null $grouping grouping object.
     * @param string|null $season season string.
     * @return array
     */
    private function fetch_competition_matches( Player $player, ?object $grouping, ?string $season ): array {
        $competition_repository = $this->get_competition_repository();
        if ( $grouping instanceof Competition ) {
            $competition = $grouping;
        } elseif ( $grouping ) {
            $competition = $competition_repository->find_by_id( (int) ( $grouping->id ?? $grouping ) );
        } else {
            $competition = null;
        }

        if ( ! $competition ) {
            return array();
        }

        $matches = $competition_repository->find_matches(
            (int) $competition->get_id(),
            array(
                'season'  => $season,
                'player'  => $player->id,
                'orderby' => array(
                    'date'      => 'ASC',
                    'league_id' => 'DESC',
                ),
            )
        );

        $matches = is_array( $matches ) ? $matches : array();
        $this->hydrate_matches_rubbers( $matches, $player );

        return $matches;
    }

    /**
     * Hydrate rubbers on matches for a player.
     *
     * @param mixed  $matches matches list.
     * @param Player $player player object.
     * @return void
     */
    private function hydrate_matches_rubbers( mixed $matches, Player $player ): void {
        if ( ! is_array( $matches ) || ! $this->rubber_repository || empty( $player->id ) ) {
            return;
        }

        $player_id = (int) $player->id;

        foreach ( $matches as $match ) {
            if ( ! is_object( $match ) || ( isset( $match->rubbers ) && is_array( $match->rubbers ) ) ) {
                continue;
            }

            $match_id = method_exists( $match, 'get_id' ) ? (int) $match->get_id() : ( $match->id ?? 0 );
            if ( $match_id <= 0 ) {
                continue;
            }

            $rubbers = $this->rubber_repository->find_by_fixture_id( $match_id, $player_id );
            if ( method_exists( $match, 'set_rubbers' ) ) {
                $match->set_rubbers( $rubbers );
            } else {
                $match->rubbers = $rubbers;
            }
        }
    }

    /**
     * Fetch all matches for a player.
     *
     * @param Player      $player player object.
     * @param string|null $season season string.
     * @param int|null    $period period integer.
     * @param string|null $type type string.
     * @return array
     */
    private function fetch_all_matches( Player $player, ?string $season, ?int $period, ?string $type ): array {
        return $this->get_fixture_repository()->find_player_matches(
            (int) $player->id,
            array(
                'season'  => $season,
                'time'    => $period,
                'type'    => $type,
                'status'  => 'Y',
                'orderby' => array(
                    'date' => 'ASC',
                ),
            )
        );
    }

    /**
     * Determine the league reference key for statistics.
     *
     * @param object $match match object.
     * @param string $match_source match source string.
     * @return string|int
     */
    private function determine_league_ref( object $match, string $match_source ): string|int {
        if ( 'all' !== $match_source ) {
            return 0;
        }

        $match_league = method_exists( $match, 'get_league' ) ? $match->get_league() : ( $match->league ?? null );
        if ( ! empty( $match_league->is_championship ) ) {
            $league_no = 1;
        } else {
            $league_title = explode( ' ', $match_league->title ?? '' );
            $league_no    = end( $league_title );
        }

        $age_limit    = $match_league->event->age_limit ?? 'open';
        $rubber_order = empty( $match_league->event->reverse_rubbers ) ? 'true' : 'false';

        return $league_no . '-' . ( $match_league->num_rubbers ?? '' ) . '-' . $age_limit . '-' . $rubber_order;
    }

    /**
     * Record a match on the player object, grouping by league if applicable.
     *
     * @param Player $player player object.
     * @param object $match match object.
     * @param string $match_source match source string.
     * @return void
     */
    private function record_player_match( Player $player, object $match, string $match_source ): void {
        if ( 'competition' !== $match_source && 'event' !== $match_source ) {
            $player->matches[] = $match;
            return;
        }

        $match_league = method_exists( $match, 'get_league' ) ? $match->get_league() : ( $match->league ?? null );
        if ( $match_league && isset( $match_league->title ) ) {
            $key = $match_league->title;
        } elseif ( $match_league && method_exists( $match_league, 'get_name' ) ) {
            $key = $match_league->get_name();
        } else {
            $key = $match->league->title ?? '';
        }

        if ( false === array_key_exists( $key, $player->matches ) ) {
            $player->matches[ $key ]           = array();
            $player->matches[ $key ]['league'] = $match_league;
            if ( is_object( $player->matches[ $key ]['league'] ) ) {
                $player->matches[ $key ]['league']->season = $match->season ?? ( method_exists( $match, 'get_season' ) ? $match->get_season() : null );
            }
        }

        $player->matches[ $key ]['matches'][] = $match;
    }

    /**
     * Accumulate statistics for all rubbers in a match.
     *
     * @param array      $stats_ref statistics reference array.
     * @param string|int $league_ref league reference key.
     * @param object     $match match object.
     * @param Player     $player player object.
     * @return void
     */
    private function accumulate_match_statistics( array &$stats_ref, string|int $league_ref, object $match, Player $player ): void {
        if ( empty( $match->rubbers ) || ! is_array( $match->rubbers ) ) {
            return;
        }

        foreach ( $match->rubbers as $rubber ) {
            $this->accumulate_rubber_statistics( $stats_ref, $league_ref, $match, $rubber, $player );
        }
    }

    /**
     * Accumulate statistics for a single rubber.
     *
     * @param array      $stats_ref statistics reference array.
     * @param string|int $league_ref league reference key.
     * @param object     $match match object.
     * @param mixed      $rubber rubber object or mixed data.
     * @param Player     $player player object.
     * @return void
     */
    private function accumulate_rubber_statistics( array &$stats_ref, string|int $league_ref, object $match, mixed $rubber, Player $player ): void {
        $teams       = $this->determine_rubber_teams( $match, $rubber );
        $player_info = $this->find_player_team_and_ref( $rubber, $player );
        $player_team = $player_info['player_team'];

        if ( $teams['winner'] === $player_team ) {
            $player_team_status = 'winner';
        } elseif ( $teams['loser'] === $player_team ) {
            $player_team_status = 'loser';
        } else {
            $player_team_status = 'draw';
        }

        $title = is_object( $rubber ) ? ( $rubber->title ?? '' ) : '';

        if ( ! isset( $stats_ref[ $league_ref ][ $title ]['played'][ $player_team_status ] ) ) {
            $stats_ref[ $league_ref ][ $title ]['played'][ $player_team_status ] = 0;
        }
        ++$stats_ref[ $league_ref ][ $title ]['played'][ $player_team_status ];

        $sets = is_object( $rubber ) && ! empty( $rubber->custom['sets'] ) ? $rubber->custom['sets'] : array();
        if ( is_iterable( $sets ) ) {
            foreach ( $sets as $set ) {
                $this->accumulate_set_and_games( $stats_ref, $league_ref, $title, $set, $player_info['player_ref'] );
            }
        }
    }

    /**
     * Determine winner and loser teams for a rubber.
     *
     * @param object $match match object.
     * @param mixed  $rubber rubber object or mixed data.
     * @return array
     */
    private function determine_rubber_teams( object $match, mixed $rubber ): array {
        $winner = null;
        $loser  = null;

        if ( is_object( $rubber ) && ! empty( $rubber->winner_id ) ) {
            if ( (string) $rubber->winner_id === (string) $match->home_team ) {
                $winner = 'home';
                $loser  = 'away';
            } elseif ( (string) $rubber->winner_id === (string) $match->away_team ) {
                $winner = 'away';
                $loser  = 'home';
            }
        }

        return array(
            'winner' => $winner,
            'loser'  => $loser,
        );
    }

    /**
     * Find the player's team and reference side in a rubber.
     *
     * @param mixed  $rubber rubber object or mixed data.
     * @param Player $player player object.
     * @return array
     */
    private function find_player_team_and_ref( mixed $rubber, Player $player ): array {
        if ( ! is_object( $rubber ) || ! isset( $rubber->players ) || null === $player->display_name ) {
            return array(
                'player_team' => null,
                'player_ref'  => null,
            );
        }

        $rubber_type    = $rubber->type ?? '';
        $match_type     = strtolower( substr( $rubber_type, 1, 1 ) );
        $rubber_players = ( 'd' === $match_type ) ? array( '1', '2' ) : array( '1' );

        foreach ( array( 'home', 'away' ) as $opponent ) {
            if ( array_any( $rubber_players, fn( $p ) => isset( $rubber->players[ $opponent ][ $p ]->display_name ) && $rubber->players[ $opponent ][ $p ]->display_name === $player->display_name ) ) {
                return array(
                    'player_team' => $opponent,
                    'player_ref'  => 'home' === $opponent ? 'player1' : 'player2',
                );
            }
        }

        return array(
            'player_team' => null,
            'player_ref'  => null,
        );
    }

    /**
     * Accumulate statistics for a single set and its games.
     *
     * @param array       $stats_ref statistics reference array.
     * @param string|int  $league_ref league reference key.
     * @param mixed       $title rubber title.
     * @param mixed       $set set data array or ArrayAccess object.
     * @param string|null $player_ref player reference side.
     * @return void
     */
    private function accumulate_set_and_games( array &$stats_ref, string|int $league_ref, mixed $title, mixed $set, ?string $player_ref ): void {
        if ( ( ! is_array( $set ) && ! $set instanceof ArrayAccess ) || ! isset( $set['player1'] ) || '' === $set['player1'] || ! isset( $set['player2'] ) || '' === $set['player2'] ) {
            return;
        }

        $p1_won   = $set['player1'] > $set['player2'];
        $stat_ref = ( 'player1' === $player_ref ) === $p1_won ? 'winner' : 'loser';

        if ( ! isset( $stats_ref[ $league_ref ][ $title ]['sets'][ $stat_ref ] ) ) {
            $stats_ref[ $league_ref ][ $title ]['sets'][ $stat_ref ] = 0;
        }
        ++$stats_ref[ $league_ref ][ $title ]['sets'][ $stat_ref ];

        $opponents_pt = array( 'player1', 'player2' );
        foreach ( $opponents_pt as $opponent ) {
            if ( isset( $set[ $opponent ] ) && is_numeric( $set[ $opponent ] ) ) {
                $game_status = ( $player_ref === $opponent ) ? 'winner' : 'loser';
                if ( ! isset( $stats_ref[ $league_ref ][ $title ]['games'][ $game_status ] ) ) {
                    $stats_ref[ $league_ref ][ $title ]['games'][ $game_status ] = 0;
                }
                $stats_ref[ $league_ref ][ $title ]['games'][ $game_status ] += (int) $set[ $opponent ];
            }
        }
    }

    /**
     * Get competitions for player function.
     *
     * @param Player                   $player player object.
     * @param array|string             $args search parameters.
     * @param Competition_Service|null $competition_service competition service object.
     * @return array
     */
    public function get_competitions( Player $player, array|string $args = array(), ?Competition_Service $competition_service = null ): array {
        $defaults     = array(
            'type'   => false,
            'season' => false,
        );
        $args         = array_merge( $defaults, (array) $args );
        $player_id    = $player->id ?? $player->ID ?? 0;
        $competitions = $this->get_statistics_repository()->find_competitions_played(
            $player_id,
            $this->filter_value( $args['type'] ),
            $this->filter_value( $args['season'] )
        );
        $i            = 0;
        foreach ( $competitions as $competition ) {
            if ( $competition_service ) {
                $competition_dtl = $competition_service->get_competition( (int) $competition->id );
            } else {
                $competition_dtl = $this->get_competition_repository()->find_by_id( (int) $competition->id );
            }
            if ( $competition_dtl ) {
                $competition_dtl->season = (int) $competition->season;
                $competition_season      = $competition_dtl->seasons->get( (string) $competition->season );
                if ( null !== $competition_season ) {
                    $competition_dtl->date_start = $competition_season['date_start'] ?? null;
                    $competition_dtl->date_end   = $competition_season['date_end'] ?? null;
                    $competitions[ $i ]          = $competition_dtl;
                } else {
                    unset( $competitions [ $i ] );
                }
            }
            ++$i;
        }
        return array_values( $competitions );
    }

    /**
     * Get stats for teams for player function
     *
     * @param Player       $player player object.
     * @param array|string $args search parameters.
     * @return array
     */
    public function get_stats_teams( Player $player, array|string $args = array() ): array {
        $defaults  = array(
            'season' => false,
        );
        $args      = array_merge( $defaults, (array) $args );
        $player_id = $player->ID ?? $player->id ?? 0;

        return $this->get_statistics_repository()->find_team_stats( $player_id, $this->filter_value( $args['season'] ) );
    }

    /**
     * Get stats for players for player function
     *
     * @param Player       $player player object.
     * @param array|string $args search parameters.
     * @return array
     */
    public function get_stats_players( Player $player, array|string $args = array() ): array {
        $defaults  = array(
            'season' => false,
        );
        $args      = array_merge( $defaults, (array) $args );
        $player_id = $player->ID ?? $player->id ?? 0;

        return $this->get_statistics_repository()->find_player_stats( $player_id, $this->filter_value( $args['season'] ) );
    }
}
