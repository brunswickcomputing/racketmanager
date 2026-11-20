<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Competition;

use InvalidArgumentException;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Shared\Serialized_Column;

/**
 * Data Transfer Object for hydrating an Event entity from raw data.
 *
 * Note: Fields $matches, $stages, $rounds, $champion, $runner_up, $parent_league, $draw,
 * and $status represent in-memory domain and test state; they are not persisted to the database.
 */
readonly class Event_Hydration_DTO {
    /**
     * Constructor.
     *
     * @param int|null     $id             Event ID.
     * @param string       $name           Event name.
     * @param string       $type           Event type.
     * @param int|null     $num_sets       Number of sets.
     * @param int|null     $num_rubbers    Number of rubbers.
     * @param int|null     $competition_id Competition ID.
     * @param array        $settings       Event settings.
     * @param array|string $seasons        Seasons data.
     * @param array        $matches        In-memory event matches (not persisted).
     * @param array        $stages         In-memory event stages (not persisted).
     * @param array        $rounds         In-memory event rounds (not persisted).
     * @param int|null     $champion       In-memory champion player/team ID (not persisted).
     * @param int|null     $runner_up      In-memory runner-up player/team ID (not persisted).
     * @param League|null  $parent_league  In-memory parent league (not persisted).
     * @param string|null  $draw           In-memory draw data (not persisted).
     * @param string       $status         In-memory event status (not persisted).
     * @param int|null     $primary_league Primary league ID.
     * @throws InvalidArgumentException    When ID or competition ID is invalid.
     */
    public function __construct(
        public ?int $id = null,
        public string $name = '',
        public string $type = '',
        public ?int $num_sets = null,
        public ?int $num_rubbers = null,
        public ?int $competition_id = null,
        public array $settings = [],
        public array|string $seasons = [],
        public array $matches = [],
        public array $stages = [],
        public array $rounds = [],
        public ?int $champion = null,
        public ?int $runner_up = null,
        public ?League $parent_league = null,
        public ?string $draw = null,
        public string $status = '',
        public ?int $primary_league = null
    ) {
        if ( null !== $this->id && $this->id < 0 ) {
            throw new InvalidArgumentException( 'Event id must be non-negative.' );
        }
        if ( null !== $this->competition_id && $this->competition_id <= 0 ) {
            throw new InvalidArgumentException( 'Event competition_id must be a positive integer.' );
        }
    }

    /**
     * Create from a database row or generic object.
     *
     * @param object $data Row data.
     * @return self
     */
    public static function from_object( object $data ): self {
        $settings = $data->settings ?? [];
        if ( is_string( $settings ) ) {
            $unserialized = self::decode_serialized_array( $settings );
            if ( is_array( $unserialized ) ) {
                $settings = $unserialized;
            } else {
                $settings = json_decode( $settings, true ) ?: [];
            }
        }

        $seasons = $data->seasons ?? [];
        if ( is_string( $seasons ) ) {
            $unserialized = self::decode_serialized_array( $seasons );
            if ( is_array( $unserialized ) ) {
                $seasons = $unserialized;
            }
        }

        $raw_name = $data->name ?? ( $data->title ?? '' );
        $name     = is_scalar( $raw_name ) ? (string) $raw_name : '';

        $raw_type = $data->type ?? ( $data->event_type ?? '' );
        $type     = is_scalar( $raw_type ) ? (string) $raw_type : '';

        $matches = isset( $data->matches ) && is_array( $data->matches ) ? $data->matches : [];
        $stages  = isset( $data->stages ) && is_array( $data->stages ) ? $data->stages : [];
        $rounds  = isset( $data->rounds ) && is_array( $data->rounds ) ? $data->rounds : [];

        $champion  = isset( $data->champion ) && is_numeric( $data->champion ) ? (int) $data->champion : null;
        $runner_up = isset( $data->runner_up ) && is_numeric( $data->runner_up ) ? (int) $data->runner_up : null;

        $parent_league = ( $data->parent_league ?? null ) instanceof League ? $data->parent_league : null;

        $draw           = isset( $data->draw ) && is_scalar( $data->draw ) ? (string) $data->draw : null;
        $status         = isset( $data->status ) && is_scalar( $data->status ) ? (string) $data->status : '';
        $primary_league = isset( $data->primary_league ) && is_numeric( $data->primary_league ) ? (int) $data->primary_league : null;

        return new self(
            id: isset( $data->id ) && is_numeric( $data->id ) ? (int) $data->id : null,
            name: $name,
            type: $type,
            num_sets: isset( $data->num_sets ) && is_numeric( $data->num_sets ) ? (int) $data->num_sets : null,
            num_rubbers: isset( $data->num_rubbers ) && is_numeric( $data->num_rubbers ) ? (int) $data->num_rubbers : null,
            competition_id: isset( $data->competition_id ) && is_numeric( $data->competition_id ) && (int) $data->competition_id > 0 ? (int) $data->competition_id : null,
            settings: (array) $settings,
            seasons: $seasons,
            matches: $matches,
            stages: $stages,
            rounds: $rounds,
            champion: $champion,
            runner_up: $runner_up,
            parent_league: $parent_league,
            draw: $draw,
            status: $status,
            primary_league: $primary_league
        );
    }

    /**
     * Create from an associative array.
     *
     * @param array $data Row data.
     * @return self
     */
    public static function from_array( array $data ): self {
        return self::from_object( (object) $data );
    }

    /**
     * Decode only legacy PHP-serialized arrays; leave JSON and malformed values unchanged.
     *
     * @param mixed $value Serialized or raw string.
     * @return mixed
     */
    private static function decode_serialized_array( mixed $value ): mixed {
        if ( ! is_string( $value ) ) {
            return $value;
        }
        $serialized = trim( $value );
        if ( ! preg_match( '/^a:\d+:\{.*\}$/sD', $serialized ) ) {
            return $value;
        }
        $unserialized = Serialized_Column::unserialize_safe( $serialized, false );
        return false === $unserialized ? $value : $unserialized;
    }
}
