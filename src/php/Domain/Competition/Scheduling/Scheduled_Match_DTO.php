<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition\Scheduling;

/**
 * A single scheduled fixture (or bye) in one week of a division.
 */
readonly class Scheduled_Match_DTO {
    /**
     * @param bool        $is_bye            True when this entry is a bye for `bye_team_id`.
     * @param string|null $date              Match date (`Y-m-d`), null when no week date is known.
     * @param int|null    $home_id           Home team id.
     * @param int|null    $away_id           Away team id.
     * @param string|null $home              Home team name.
     * @param string|null $away              Away team name.
     * @param string|null $slot              Home team's court slot.
     * @param string|null $time              Home team's match time.
     * @param string|null $location          Home location, or "Extra Match" for bye replacements.
     * @param string|null $resource          Physical court resource id used by the home team.
     * @param bool        $is_cross_division True for bye-replacement extra matches.
     * @param int|null    $bye_team_id       Team with the bye.
     * @param string|null $bye_team          Name of the team with the bye.
     */
    public function __construct(
        public bool $is_bye,
        public ?string $date,
        public ?int $home_id = null,
        public ?int $away_id = null,
        public ?string $home = null,
        public ?string $away = null,
        public ?string $slot = null,
        public ?string $time = null,
        public ?string $location = null,
        public ?string $resource = null, // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.resourceFound
        public bool $is_cross_division = false,
        public ?int $bye_team_id = null,
        public ?string $bye_team = null,
    ) {
    }

    /**
     * Creates a bye schedule entry for the given team.
     *
     * @param Schedule_Team_DTO $team Team assigned to the bye.
     * @param string|null       $date Match date (`Y-m-d`), or null when no date is known.
     *
     * @return self Bye schedule entry for the team.
     */
    public static function bye( Schedule_Team_DTO $team, ?string $date ): self {
        return new self(
            is_bye: true,
            date: $date,
            bye_team_id: $team->id,
            bye_team: $team->name,
        );
    }

    /**
     * Creates a scheduled fixture entry between the given home and away teams.
     *
     * @param Schedule_Team_DTO $home Home team data used for team details, slot, time, and default location.
     * @param Schedule_Team_DTO $away Away team data used for opponent details.
     * @param string|null       $date Match date, or null when no date is assigned.
     * @param string            $resource Physical court resource id assigned to the fixture.
     * @param string|null       $location Fixture location, or null to use the home team's location.
     * @param bool              $is_cross_division True when the fixture is a cross-division match.
     *
     * @return self Scheduled fixture DTO instance.
     */
    public static function fixture( Schedule_Team_DTO $home, Schedule_Team_DTO $away, ?string $date, string $resource, ?string $location = null, bool $is_cross_division = false ): self { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.resourceFound
        return new self(
            is_bye: false,
            date: $date,
            home_id: $home->id,
            away_id: $away->id,
            home: $home->name,
            away: $away->name,
            slot: $home->slot(),
            time: $home->match_time,
            location: $location ?? $home->location,
            resource: $resource,
            is_cross_division: $is_cross_division,
        );
    }

    /**
     * Array representation (keys follow the legacy 9.7.0 schedule shape).
     *
     * @return array
     */
    public function to_array(): array {
        if ( $this->is_bye ) {
            return [
                'team'    => $this->bye_team,
                'team_id' => $this->bye_team_id,
                'is_bye'  => true,
                'date'    => $this->date,
            ];
        }
        return [
            'home'              => $this->home,
            'home_id'           => $this->home_id,
            'away'              => $this->away,
            'away_id'           => $this->away_id,
            'slot'              => $this->slot,
            'time'              => $this->time,
            'location'          => $this->location,
            'resource'          => $this->resource,
            'is_bye'            => false,
            'is_cross_division' => $this->is_cross_division,
            'date'              => $this->date,
        ];
    }
}
