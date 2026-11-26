<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Competition;

use Racketmanager\Domain\Competition\Competition_Type;
use function is_array;
use function is_object;

/**
 * Data Transfer Object representing competition summary information.
 */
readonly class Competition_Summary_DTO {
    public int $id;
    public string $name;
    public ?string $age_group;
    public string $type;
    public int $season_count;
    public int $event_count;

    /**
     * Initialises the instance from an object or array of competition data.
     *
     * @param object|array $data Source data used to populate the instance.
     *
     * @return void
     */
    public function __construct( object|array $data ) {
        if ( is_array( $data ) ) {
            $data = (object) $data;
        }

        $this->id           = (int) ( $data->id ?? 0 );
        $this->name         = (string) ( $data->name ?? '' );
        $this->age_group    = isset( $data->age_group ) && '' !== $data->age_group ? (string) $data->age_group : null;
        $this->type         = is_object( $data->type ?? null ) && $data->type instanceof Competition_Type
            ? $data->type->value
            : (string) ( $data->type ?? '' );
        $this->season_count = (int) ( $data->season_count ?? 0 );
        $this->event_count  = (int) ( $data->event_count ?? 0 );
    }

    /**
     * Gets the identifier.
     *
     * @return int The identifier.
     */
    public function get_id(): int {
        return $this->id;
    }

    /**
     * Gets the name.
     *
     * @return string The name.
     */
    public function get_name(): string {
        return $this->name;
    }

    /**
     * Gets the competition type.
     *
     * @return string Competition type.
     */
    public function get_type(): string {
        return $this->type;
    }

    /**
     * Retrieves the age group assigned to the competition.
     *
     * @return string|null Age group value, or null when no age group is set.
     */
    public function get_age_group(): ?string {
        return $this->age_group;
    }

    /**
     * Gets the number of seasons associated with the competition.
     *
     * @return int Season count.
     */
    public function get_season_count(): int {
        return $this->season_count;
    }

    /**
     * Gets the number of events associated with the instance.
     *
     * @return int Event count.
     */
    public function get_event_count(): int {
        return $this->event_count;
    }
}
