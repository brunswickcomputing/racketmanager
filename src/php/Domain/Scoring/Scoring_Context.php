<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Scoring;

/**
 * Immutable scoring configuration context for a match or fixture.
 *
 * Encapsulates the scoring settings required to evaluate match progress and
 * determine winning conditions, including set requirements, scoring type,
 * point rules, championship status, and optional round or rubber metadata.
 */
readonly class Scoring_Context {
    /**
     * Initialises the scoring configuration.
     *
     * @param int         $num_sets_to_win Number of sets required to win.
     * @param string      $scoring_type Scoring system type.
     * @param array       $point_rule Point scoring rules.
     * @param bool        $is_championship Whether the configuration is for a championship.
     * @param string|null $final_round Optional final round identifier.
     * @param int|null    $num_rubbers Optional number of rubbers.
     * @param int|null    $leg Optional leg number.
     * @param int|null    $num_sets Optional total number of sets.
     *
     * @return void
     */
    public function __construct(
        public int $num_sets_to_win,
        public string $scoring_type,
        public array $point_rule,
        public bool $is_championship,
        public ?string $final_round = null,
        public ?int $num_rubbers = null,
        public ?int $leg = null,
        public ?int $num_sets = null
    ) {
    }

    /**
     * Creates a scoring configuration from a legacy match object.
     *
     * @param object $match Legacy match object containing league and match scoring data.
     *
     * @return self Scoring configuration populated from the legacy match data.
     */
    public static function from_legacy_match( object $match ): self {
        return new self(
            num_sets_to_win: (int) $match->league->num_sets_to_win,
            scoring_type: $match->league->scoring ?? 'TB',
            point_rule: $match->league->get_point_rule(),
            is_championship: (bool) $match->league->is_championship,
            final_round: $match->final_round,
            num_rubbers: (int) ( $match->num_rubbers ?? $match->league->num_rubbers ?? 0 ),
            leg: $match->leg,
            num_sets: (int) $match->league->num_sets
        );
    }
}
