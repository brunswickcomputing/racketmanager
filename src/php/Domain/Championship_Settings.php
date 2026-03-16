<?php
/**
 * Championship Settings API: Championship Settings class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain
 */

namespace Racketmanager\Domain;

final class Championship_Settings {
    /**
     * Preliminary groups.
     *
     * @var array
     */
    public array $groups;

    /**
     * Number of teams per group.
     *
     * @var int
     */
    public int $teams_per_group;

    /**
     * Number of teams to advance.
     *
     * @var int
     */
    public int $num_advance;

    /**
     * Whether a third place match should be created.
     *
     * @var bool
     */
    public bool $match_place3;

    /**
     * Constructor.
     *
     * @param array $groups groups.
     * @param int $teams_per_group teams per group.
     * @param int $num_advance number of teams to advance.
     * @param bool $match_place3 third place match flag.
     */
    public function __construct(
        array $groups = array(),
        int $teams_per_group = 4,
        int $num_advance = 0,
        bool $match_place3 = false,
    ) {
        $this->groups          = $groups;
        $this->teams_per_group = $teams_per_group;
        $this->num_advance     = $num_advance;
        $this->match_place3    = $match_place3;
    }

    /**
     * Create settings from the raw league settings array.
     *
     * @param array $settings raw settings.
     *
     * @return self
     */
    public static function from_array( array $settings ): self {
        return new self(
            groups: isset( $settings['groups'] ) && is_array( $settings['groups'] ) ? $settings['groups'] : array(),
            teams_per_group: isset( $settings['teams_per_group'] ) ? (int) $settings['teams_per_group'] : 4,
            num_advance: isset( $settings['num_advance'] ) ? (int) $settings['num_advance'] : 0,
            match_place3: isset( $settings['match_place3'] ) && 1 === (int) $settings['match_place3'],
        );
    }

    /**
     * Check if settings contain groups.
     *
     * @return bool
     */
    public function has_groups(): bool {
        return ! empty( $this->groups );
    }

    /**
     * Get the number of groups.
     *
     * @return int
     */
    public function num_groups(): int {
        return count( $this->groups );
    }
}
