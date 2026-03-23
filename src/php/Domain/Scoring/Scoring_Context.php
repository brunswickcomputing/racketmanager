<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Scoring;

/**
 * Data Transfer Object for scoring context used in validation and calculations.
 */
readonly class Scoring_Context {
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
	 * Create from legacy Racketmanager_Match object.
	 *
	 * @param object $match
	 *
	 * @return self
	 */
	public static function from_legacy_match( object $match ): self {
		return new self(
			num_sets_to_win: (int) $match->league->num_sets_to_win,
			scoring_type: $match->league->scoring ?? 'TB',
			point_rule: $match->league->get_point_rule(),
			is_championship: (bool) $match->league->is_championship,
			final_round: $match->final_round,
			num_rubbers: (int) $match->num_rubbers,
			leg: $match->leg,
			num_sets: (int) $match->league->num_sets
		);
	}
}
