<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Fixture;

use Racketmanager\Domain\Enums\Fixture_Status;

/**
 * Encapsulated policy rules for fixture statuses and state flags.
 */
final class Fixture_Status_Policy {
    public const int STATUS_WALKOVER  = Fixture_Status::WALKOVER->value;
    public const int STATUS_RETIRED   = Fixture_Status::RETIRED->value;
    public const int STATUS_SHARED    = Fixture_Status::SHARED->value;
    public const int STATUS_ABANDONED = Fixture_Status::ABANDONED->value;
    public const int STATUS_WITHDRAWN = Fixture_Status::WITHDRAWN->value;
    public const int STATUS_CANCELLED = Fixture_Status::CANCELLED->value;

    /**
     * Calculates boolean status flags for a fixture from its status value and winner state.
     *
     * @param int|null $status Fixture status value to evaluate.
     * @param int|null $winner_id Optional winner identifier used to determine whether the fixture is pending.
     *
     * @return array<string, bool> Associative array of status flag names mapped to their boolean values.
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
     * Applies calculated status flags to the provided fixture.
     *
     * @param Fixture $fixture Fixture instance to update with status flags.
     *
     * @return void
     */
    public static function apply_to_fixture( Fixture $fixture ): void {
        $fixture->set_status_flags();
    }

    /**
     * Determines whether the given fixture status represents a walkover.
     *
     * @param int|null $status Fixture status value to check.
     *
     * @return bool True if the status represents a walkover, false otherwise.
     */
    public static function is_walkover( ?int $status ): bool {
        return Fixture_Status::WALKOVER === Fixture_Status::try_from_value( $status );
    }

    /**
     * Determines whether the given fixture status represents a retired fixture.
     *
     * @param int|null $status Fixture status value to check.
     *
     * @return bool True if the status represents a retired fixture, false otherwise.
     */
    public static function is_retired( ?int $status ): bool {
        return Fixture_Status::RETIRED === Fixture_Status::try_from_value( $status );
    }

    /**
     * Determines whether the given fixture status represents a shared result.
     *
     * @param int|null $status Fixture status value to check.
     *
     * @return bool True if the status represents a shared result, false otherwise.
     */
    public static function is_shared( ?int $status ): bool {
        return Fixture_Status::SHARED === Fixture_Status::try_from_value( $status );
    }

    /**
     * Determines whether the given fixture status represents an abandoned fixture.
     *
     * @param int|null $status Fixture status value to check.
     *
     * @return bool True if the status represents an abandoned fixture, false otherwise.
     */
    public static function is_abandoned( ?int $status ): bool {
        return Fixture_Status::ABANDONED === Fixture_Status::try_from_value( $status );
    }

    /**
     * Determines whether the given status represents a withdrawn fixture.
     *
     * @param int|null $status The fixture status value to check.
     *
     * @return bool True if the status is withdrawn, false otherwise.
     */
    public static function is_withdrawn( ?int $status ): bool {
        return Fixture_Status::WITHDRAWN === Fixture_Status::try_from_value( $status );
    }

    /**
     * Determines whether the given status represents a cancelled fixture.
     *
     * @param int|null $status The fixture status value to check.
     *
     * @return bool True if the status is cancelled, false otherwise.
     */
    public static function is_cancelled( ?int $status ): bool {
        return Fixture_Status::CANCELLED === Fixture_Status::try_from_value( $status );
    }

    /**
     * Determines whether the result is still pending based on the winner ID.
     *
     * @param int|null $winner_id The winner ID to evaluate.
     *
     * @return bool True if no winner ID is set, false otherwise.
     */
    public static function is_pending( ?int $winner_id ): bool {
        return empty( $winner_id );
    }
}
