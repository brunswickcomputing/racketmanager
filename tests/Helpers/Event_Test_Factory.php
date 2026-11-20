<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\Event_Factory;
use Racketmanager\Domain\Competition\League;

/**
 * Builds real Event aggregates for tests.
 */
final class Event_Test_Factory {
    /**
     * Create an Event entity with explicit parameters and sensible test defaults.
     *
     * @param int|null          $id             Event ID.
     * @param string            $name           Event name.
     * @param string            $type           Event type (e.g. 'MD', 'MS', 'WD', 'WS', 'XD').
     * @param Competition|null  $competition    Parent competition.
     * @param int|null          $competition_id Competition ID.
     * @param int|null          $num_sets       Number of sets.
     * @param int|null          $num_rubbers    Number of rubbers.
     * @param array             $settings       Event settings.
     * @param array|string      $seasons        Seasons data.
     * @param array             $matches        Event matches.
     * @param array             $stages         Event stages.
     * @param array             $rounds         Event rounds.
     * @param int|null          $champion       Champion ID.
     * @param int|null          $runner_up      Runner-up ID.
     * @param League|null       $parent_league  Parent league.
     * @param string|null       $draw           Draw data.
     * @param string            $status         Event status.
     * @param int|null          $primary_league Primary league ID.
     * @param array             $overrides      Property overrides.
     * @return Event
     */
    public static function create(
        ?int $id = 1,
        string $name = 'Test Event',
        string $type = 'MD',
        ?Competition $competition = null,
        ?int $competition_id = null,
        ?int $num_sets = 3,
        ?int $num_rubbers = 0,
        array $settings = [],
        array|string $seasons = [],
        array $matches = [],
        array $stages = [],
        array $rounds = [],
        ?int $champion = null,
        ?int $runner_up = null,
        ?League $parent_league = null,
        ?string $draw = null,
        string $status = '',
        ?int $primary_league = null,
        array $overrides = []
    ): Event {
        if ( null === $competition && null === $competition_id ) {
            $competition    = Competition_Test_Factory::create( id: 1, type: Competition_Type::LEAGUE );
            $competition_id = $competition->get_id() ?? 1;
        } elseif ( null !== $competition && null === $competition_id ) {
            $competition_id = $competition->get_id() ?? 1;
        }

        $dto_properties = [
            'id'             => $id,
            'name'           => $name,
            'type'           => $type,
            'competition_id' => $competition_id,
            'num_sets'       => $num_sets,
            'num_rubbers'    => $num_rubbers,
            'settings'       => $settings,
            'seasons'        => $seasons,
            'matches'        => $matches,
            'stages'         => $stages,
            'rounds'         => $rounds,
            'champion'       => $champion,
            'runner_up'      => $runner_up,
            'parent_league'  => $parent_league,
            'draw'           => $draw,
            'status'         => $status,
            'primary_league' => $primary_league,
        ];

        $post_hydrate_overrides = [];

        foreach ( $overrides as $key => $value ) {
            $key_str = (string) $key;
            if ( array_key_exists( $key_str, $dto_properties ) ) {
                $dto_properties[ $key_str ] = $value;
                continue;
            }

            if ( ! property_exists( Event::class, $key_str ) ) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new \InvalidArgumentException( sprintf( 'Unknown Event test property: %s', $key_str ) );
            }

            $ref_prop = new \ReflectionProperty( Event::class, $key_str );
            if ( ! $ref_prop->isPublic() ) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new \InvalidArgumentException( sprintf( 'Cannot override non-public Event property: %s', $key_str ) );
            }

            $setter_map = [
                'num_leagues' => 'set_league_count',
            ];
            $setter     = $setter_map[ $key_str ] ?? ( 'set_' . $key_str );

            if ( ! method_exists( Event::class, $setter ) ) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new \InvalidArgumentException( sprintf( 'Event property "%s" has no setter method.', $key_str ) );
            }

            $ref_method = new \ReflectionMethod( Event::class, $setter );
            if ( ! $ref_method->isPublic() || 1 !== $ref_method->getNumberOfRequiredParameters() ) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new \InvalidArgumentException( sprintf( 'Setter "%s" for Event property "%s" must be public and accept exactly 1 parameter.', $setter, $key_str ) );
            }

            $post_hydrate_overrides[ $setter ] = $value;
        }

        $row = (object) array_filter( $dto_properties, static fn( $value ) => null !== $value );

        $event = Event_Factory::from_object( $row, $competition );

        foreach ( $post_hydrate_overrides as $setter => $value ) {
            $event->$setter( $value );
        }

        return $event;
    }

    /**
     * Create a league-flavored Event.
     *
     * @param int               $id          Event ID.
     * @param string            $name        Event name.
     * @param string            $type        Event type code (default: 'MD').
     * @param Competition|null  $competition Parent competition.
     * @param array|string      $seasons     Seasons data.
     * @param array             $settings    Settings array.
     * @param array             $overrides   Property overrides.
     * @return Event
     */
    public static function league_event(
        int $id = 1,
        string $name = 'Division 1 Event',
        string $type = 'MD',
        ?Competition $competition = null,
        array|string $seasons = [],
        array $settings = [],
        array $overrides = []
    ): Event {
        $competition ??= Competition_Test_Factory::create( id: 1, type: Competition_Type::LEAGUE );
        return self::create(
            id: $id,
            name: $name,
            type: $type,
            competition: $competition,
            settings: $settings,
            seasons: $seasons,
            overrides: $overrides
        );
    }

    /**
     * Create a tournament-flavored Event.
     *
     * @param int               $id          Event ID.
     * @param string            $name        Event name.
     * @param string            $type        Event type code (default: 'MS').
     * @param Competition|null  $competition Parent competition.
     * @param array|string      $seasons     Seasons data.
     * @param array             $settings    Settings array.
     * @param array             $matches     Matches array.
     * @param array             $stages      Stages array.
     * @param array             $rounds      Rounds array.
     * @param int|null          $champion    Champion ID.
     * @param int|null          $runner_up   Runner-up ID.
     * @param array             $overrides   Property overrides.
     * @return Event
     */
    public static function tournament_event(
        int $id = 1,
        string $name = 'Men Singles Knockout',
        string $type = 'MS',
        ?Competition $competition = null,
        array|string $seasons = [],
        array $settings = [],
        array $matches = [],
        array $stages = [],
        array $rounds = [],
        ?int $champion = null,
        ?int $runner_up = null,
        array $overrides = []
    ): Event {
        $competition ??= Competition_Test_Factory::create( id: 2, type: Competition_Type::TOURNAMENT );
        return self::create(
            id: $id,
            name: $name,
            type: $type,
            competition: $competition,
            settings: $settings,
            seasons: $seasons,
            matches: $matches,
            stages: $stages,
            rounds: $rounds,
            champion: $champion,
            runner_up: $runner_up,
            overrides: $overrides
        );
    }

    /**
     * Create a cup-flavored Event.
     *
     * @param int               $id          Event ID.
     * @param string            $name        Event name.
     * @param string            $type        Event type code (default: 'MD').
     * @param Competition|null  $competition Parent competition.
     * @param array|string      $seasons     Seasons data.
     * @param array             $settings    Settings array.
     * @param array             $overrides   Property overrides.
     * @return Event
     */
    public static function cup_event(
        int $id = 1,
        string $name = 'Men Cup',
        string $type = 'MD',
        ?Competition $competition = null,
        array|string $seasons = [],
        array $settings = [],
        array $overrides = []
    ): Event {
        $competition ??= Competition_Test_Factory::create( id: 3, type: Competition_Type::CUP );
        return self::create(
            id: $id,
            name: $name,
            type: $type,
            competition: $competition,
            settings: $settings,
            seasons: $seasons,
            overrides: $overrides
        );
    }

    /**
     * Create an Event entity from a generic object.
     *
     * @param object           $row         Row data.
     * @param Competition|null $competition Parent competition.
     * @return Event
     */
    public static function from_object( object $row, ?Competition $competition = null ): Event {
        return Event_Factory::from_object( $row, $competition );
    }

    /**
     * Create an Event entity from an associative array.
     *
     * @param array            $data        Row data.
     * @param Competition|null $competition Parent competition.
     * @return Event
     */
    public static function from_array( array $data, ?Competition $competition = null ): Event {
        return Event_Factory::from_array( $data, $competition );
    }
}
