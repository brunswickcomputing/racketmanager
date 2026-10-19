<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Fixture;

use Racketmanager\Domain\Enums\Fixture_Status;

/**
 * Encapsulated policy rules for fixture statuses and state flags.
 */
final class Fixture_Status_Policy {
	public const STATUS_WALKOVER  = Fixture_Status::WALKOVER->value;
	public const STATUS_RETIRED   = Fixture_Status::RETIRED->value;
	public const STATUS_SHARED    = Fixture_Status::SHARED->value;
	public const STATUS_ABANDONED = Fixture_Status::ABANDONED->value;
	public const STATUS_WITHDRAWN = Fixture_Status::WITHDRAWN->value;
	public const STATUS_CANCELLED = Fixture_Status::CANCELLED->value;

	/**
	 * Compute status flags array based on status ID and winner ID.
	 *
	 * @param int|null $status
	 * @param int|null $winner_id
	 * @return array{is_walkover: bool, is_retired: bool, is_shared: bool, is_abandoned: bool, is_withdrawn: bool, is_cancelled: bool, is_pending: bool}
	 */
	public static function calculate_status_flags( ?int $status, ?int $winner_id = null ): array {
		$enum = Fixture_Status::try_from_value( $status );

		return [
			'is_walkover'  => Fixture_Status::WALKOVER === $enum,
			'is_retired'   => Fixture_Status::RETIRED === $enum,
			'is_shared'    => Fixture_Status::SHARED === $enum,
			'is_abandoned' => Fixture_Status::ABANDONED === $enum,
			'is_withdrawn' => Fixture_Status::WITHDRAWN === $enum,
			'is_cancelled' => Fixture_Status::CANCELLED === $enum,
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
		return Fixture_Status::WALKOVER === Fixture_Status::try_from_value( $status );
	}

	public static function is_retired( ?int $status ): bool {
		return Fixture_Status::RETIRED === Fixture_Status::try_from_value( $status );
	}

	public static function is_shared( ?int $status ): bool {
		return Fixture_Status::SHARED === Fixture_Status::try_from_value( $status );
	}

	public static function is_abandoned( ?int $status ): bool {
		return Fixture_Status::ABANDONED === Fixture_Status::try_from_value( $status );
	}

	public static function is_withdrawn( ?int $status ): bool {
		return Fixture_Status::WITHDRAWN === Fixture_Status::try_from_value( $status );
	}

	public static function is_cancelled( ?int $status ): bool {
		return Fixture_Status::CANCELLED === Fixture_Status::try_from_value( $status );
	}

	public static function is_pending( ?int $winner_id ): bool {
		return empty( $winner_id );
	}
}
