<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Fixture;

/**
 * Encapsulated policy rules for fixture statuses and state flags.
 */
final class Fixture_Status_Policy {
	public const STATUS_WALKOVER  = 1;
	public const STATUS_RETIRED   = 2;
	public const STATUS_SHARED    = 3;
	public const STATUS_ABANDONED = 6;
	public const STATUS_WITHDRAWN = 7;
	public const STATUS_CANCELLED = 8;

	/**
	 * Compute status flags array based on status ID and winner ID.
	 *
	 * @param int|null $status
	 * @param int|null $winner_id
	 * @return array{is_walkover: bool, is_retired: bool, is_shared: bool, is_abandoned: bool, is_withdrawn: bool, is_cancelled: bool, is_pending: bool}
	 */
	public static function calculate_status_flags( ?int $status, ?int $winner_id = null ): array {
		return [
			'is_walkover'  => self::STATUS_WALKOVER === $status,
			'is_retired'   => self::STATUS_RETIRED === $status,
			'is_shared'    => self::STATUS_SHARED === $status,
			'is_abandoned' => self::STATUS_ABANDONED === $status,
			'is_withdrawn' => self::STATUS_WITHDRAWN === $status,
			'is_cancelled' => self::STATUS_CANCELLED === $status,
			'is_pending'   => empty( $winner_id ),
		];
	}

	/**
	 * Apply status flags directly to a Fixture entity instance.
	 *
	 * @param Fixture $fixture
	 * @return void
	 */
	public static function apply_to_fixture( Fixture $fixture ): void {
		$flags = self::calculate_status_flags( $fixture->status, $fixture->winner_id );

		$fixture->is_walkover  = $flags['is_walkover'];
		$fixture->is_retired   = $flags['is_retired'];
		$fixture->is_shared    = $flags['is_shared'];
		$fixture->is_abandoned = $flags['is_abandoned'];
		$fixture->is_withdrawn = $flags['is_withdrawn'];
		$fixture->is_cancelled = $flags['is_cancelled'];
		$fixture->is_pending   = $flags['is_pending'];
	}

	public static function is_walkover( ?int $status ): bool {
		return self::STATUS_WALKOVER === $status;
	}

	public static function is_retired( ?int $status ): bool {
		return self::STATUS_RETIRED === $status;
	}

	public static function is_shared( ?int $status ): bool {
		return self::STATUS_SHARED === $status;
	}

	public static function is_abandoned( ?int $status ): bool {
		return self::STATUS_ABANDONED === $status;
	}

	public static function is_withdrawn( ?int $status ): bool {
		return self::STATUS_WITHDRAWN === $status;
	}

	public static function is_cancelled( ?int $status ): bool {
		return self::STATUS_CANCELLED === $status;
	}

	public static function is_pending( ?int $winner_id ): bool {
		return empty( $winner_id );
	}
}
