<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Competition;

use Racketmanager\Domain\Competition\Competition_Type;

/**
 * Data Transfer Object for hydrating a Competition entity from raw data.
 */
readonly class Competition_Hydration_DTO {
    /**
     * Creates a competition instance with its identifying details, classification, seasons, and settings.
     *
     * @param int|null         $id The competition identifier, or null when not yet assigned.
     * @param string           $name The competition name.
     * @param Competition_Type $type The competition type.
     * @param string|null      $age_group The optional age group for the competition.
     * @param array            $seasons The seasons associated with the competition.
     * @param array            $settings Additional competition settings.
     *
     * @return void
     */
    public function __construct(
        public ?int $id,
        public string $name,
        public Competition_Type $type,
        public ?string $age_group = null,
        public array $seasons = [],
        public array $settings = []
    ) {
    }

    /**
     * Creates an instance from an object representation.
     *
     * @param object $data Source object containing competition data.
     *
     * @return self
     */
    public static function from_object( object $data ): self {
        $seasons = $data->seasons ?? [];
        if ( is_string( $seasons ) ) {
            $seasons = json_decode( $seasons, true ) ?: [];
        }

        $settings = $data->settings ?? [];
        if ( is_string( $settings ) ) {
            $settings = json_decode( $settings, true ) ?: [];
        }

        return new self(
            id: isset( $data->id ) ? (int) $data->id : null,
            name: $data->name ?? '',
            type: Competition_Type::tryFrom( $data->type ?? '' ) ?: Competition_Type::LEAGUE,
            age_group: $data->age_group ?? null,
            seasons: (array) $seasons,
            settings: (array) $settings
        );
    }
}
