<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Tournament;

use DomainException;
use InvalidArgumentException;
use Racketmanager\Domain\DTO\Tournament\Tournament_Entry_Hydration_DTO;
use Racketmanager\Domain\Events\Domain_Event_Interface;
use Racketmanager\Domain\Tournament\Events\Tournament_Entry_Submitted_Event;
use Racketmanager\Domain\Tournament\Events\Tournament_Entry_Withdrawn_Event;
use Racketmanager\Domain\Value_Objects\Money;

/**
 * Class to implement the Tournament Entry object (moved to PSR-4)
 */
class Tournament_Entry {
    public const int STATUS_PENDING         = 0;
    public const int STATUS_UNPAID          = 1;
    public const int STATUS_CONFIRMED       = 2;
    public const int STATUS_WITHDRAWN       = 3;
    public const int STATUS_INVOICE_PENDING = 4;

    /**
     * Active workflow statuses eligible for submission.
     */
    public const array ACTIVE_STATUSES = array(
        self::STATUS_PENDING,
        self::STATUS_UNPAID,
        self::STATUS_CONFIRMED,
        self::STATUS_INVOICE_PENDING,
    );

    /**
     * Entry ID.
     *
     * @var int|null
     */
    public private(set) ?int $id = null;
    /**
     * Tournament ID.
     *
     * @var int
     */
    public private(set) int $tournament_id;
    /**
     * Player ID.
     *
     * @var int
     */
    public private(set) int $player_id;
    /**
     * Status.
     *
     * @var int
     */
    public private(set) int $status;
    /**
     * Fee in whole pounds/major units.
     *
     * Synchronized with $fee_money.
     *
     * @var int|null
     */
    public private(set) ?int $fee = null;
    /**
     * Fee as a Money value object.
     *
     * @var Money|null
     */
    public private(set) ?Money $fee_money = null;
    /**
     * Club ID.
     *
     * @var int|null
     */
    public private(set) ?int $club_id = null;
    /**
     * Club object.
     *
     * @var object|null
     */
    public private(set) ?object $club = null;

    /**
     * Recorded domain events pending dispatch.
     *
     * @var array<int, Domain_Event_Interface>
     */
    private array $domain_events = array();

    /**
     * Whether the entry was newly created and its submission has not yet been recorded.
     *
     * Only set by create(); never set when rehydrating from storage.
     *
     * @var bool
     */
    private bool $pending_submission = false;

    /**
     * Constructor
     *
     * @param Tournament_Entry_Hydration_DTO|null $dto Hydration DTO.
     */
    public function __construct( ?Tournament_Entry_Hydration_DTO $dto = null ) {
        if ( is_null( $dto ) ) {
            return;
        }
        $this->id = $dto->id;
        if ( null !== $dto->tournament_id ) {
            $this->tournament_id = $dto->tournament_id;
        }
        if ( null !== $dto->player_id ) {
            $this->player_id = $dto->player_id;
        }
        if ( null !== $dto->status ) {
            $this->status = $dto->status;
        }
        $this->club_id = $dto->club_id;

        if ( null !== $dto->fee ) {
            $this->hydrate_fee( $dto->fee );
        }
    }

    /**
     * Create a brand-new entry that has not yet been submitted.
     *
     * The entry is flagged so that the first call to submit() (after the entry has been
     * persisted and given an ID) records a Tournament_Entry_Submitted_Event. Entries
     * rehydrated from storage are never flagged and therefore never emit on submit().
     *
     * @param int                         $tournament_id Tournament ID.
     * @param int                         $player_id     Player ID.
     * @param int                         $status        Initial active status (0, 1, 2 or 4).
     * @param Money|string|int|float|null $fee           Optional fee (whole pounds, decimal string, or Money).
     * @param int|null                    $club_id       Optional club ID.
     * @return self
     *
     * @throws InvalidArgumentException If status is not an active workflow status.
     */
    public static function create( int $tournament_id, int $player_id, int $status, Money|string|int|float|null $fee = null, ?int $club_id = null ): self {
        self::assert_active_status( $status );

        $entry                     = new self();
        $entry->tournament_id      = $tournament_id;
        $entry->player_id          = $player_id;
        $entry->status             = $status;
        $entry->club_id            = $club_id;
        $entry->pending_submission = true;
        if ( $fee instanceof Money ) {
            $entry->set_fee_money( $fee );
        } elseif ( null !== $fee ) {
            $entry->hydrate_fee( $fee );
        }

        return $entry;
    }

    /**
     * Check whether the entry is newly created and awaiting its submitted event.
     *
     * @return bool
     */
    public function is_pending_submission(): bool {
        return $this->pending_submission;
    }

    /**
     * Create from a generic object (e.g. database row) via Tournament_Entry_Factory.
     *
     * @param object $row Tournament entry row.
     *
     * @return self
     */
    public static function from_object( object $row ): self {
        return Tournament_Entry_Factory::from_object( $row );
    }

    /**
     * Create from an array via Tournament_Entry_Factory.
     *
     * @param array $data Tournament entry data.
     *
     * @return self
     */
    public static function from_array( array $data ): self {
        return Tournament_Entry_Factory::from_array( $data );
    }

    /**
     * Get entry ID.
     *
     * @return int|null
     */
    public function get_id(): ?int {
        return $this->id;
    }

    /**
     * Set entry ID.
     *
     * @param int|null $insert_id Entry ID.
     */
    public function set_id( ?int $insert_id ): void {
        $this->id = $insert_id;
    }

    /**
     * Get tournament ID.
     *
     * @return int
     */
    public function get_tournament_id(): int {
        return $this->tournament_id;
    }

    /**
     * Set tournament ID.
     *
     * @param int $tournament_id Tournament ID.
     */
    public function set_tournament_id( int $tournament_id ): void {
        $this->tournament_id = $tournament_id;
    }

    /**
     * Get player ID.
     *
     * @return int
     */
    public function get_player_id(): int {
        return $this->player_id;
    }

    /**
     * Set player ID.
     *
     * @param int $player_id Player ID.
     */
    public function set_player_id( int $player_id ): void {
        $this->player_id = $player_id;
    }

    /**
     * Get entry status.
     *
     * @return int
     */
    public function get_status(): int {
        return $this->status;
    }

    /**
     * Set tournament entry status.
     *
     * @param int $status Status.
     */
    public function set_status( int $status ): void {
        $this->status = $status;
    }

    /**
     * Get fee in whole pounds/major units.
     *
     * @return int|null
     */
    public function get_fee(): ?int {
        return $this->fee;
    }

    /**
     * Get fee as a Money value object.
     *
     * @return Money|null
     */
    public function get_fee_money(): ?Money {
        return $this->fee_money;
    }

    /**
     * Hydrate fee from raw database value or input (string decimal, float, int, or null).
     *
     * @param string|int|float|null $fee Raw fee value.
     * @return void
     */
    private function hydrate_fee( string|int|float|null $fee ): void {
        if ( null === $fee || '' === $fee ) {
            $this->fee_money = null;
            $this->fee       = null;
            return;
        }

        if ( is_string( $fee ) ) {
            $this->fee_money = Money::from_string( $fee, Currency::GBP );
            $this->fee       = (int) round( $this->fee_money->get_amount() );
            return;
        }

        if ( is_float( $fee ) ) {
            $this->fee_money = Money::from_float( $fee, Currency::GBP );
            $this->fee       = (int) round( $fee );
            return;
        }

        $this->fee_money = Money::from_float( (float) $fee, Currency::GBP );
        $this->fee       = $fee;
    }

    /**
     * Set tournament entry fee in whole pounds/major units or null.
     *
     * @param int|null $fee Tournament fee in whole pounds.
     * @return self
     */
    public function set_fee( ?int $fee ): self {
        $this->fee_money = null !== $fee ? Money::from_float( (float) $fee, Currency::GBP ) : null;
        $this->fee       = $fee;
        return $this;
    }

    /**
     * Set tournament entry fee as a Money value object.
     *
     * Only GBP currency is supported by the tournament entries persistence schema.
     *
     * @param Money|null $fee_money Monetary fee value object.
     * @return self
     *
     * @throws InvalidArgumentException When non-GBP currency is passed.
     */
    public function set_fee_money( ?Money $fee_money ): self {
        if ( null !== $fee_money && Currency::GBP !== $fee_money->get_currency() ) {
            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new InvalidArgumentException(
                sprintf(
                    'Tournament entry fee currency must be GBP, got %s.',
                    $fee_money->get_currency()->value
                )
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }

        $this->fee_money = $fee_money;
        $this->fee       = null !== $fee_money ? (int) round( $fee_money->get_amount() ) : null;
        return $this;
    }

    /**
     * Get club ID.
     *
     * @return int|null
     */
    public function get_club_id(): ?int {
        return $this->club_id;
    }

    /**
     * Set club ID.
     *
     * @param int|null $club_id Club ID.
     */
    public function set_club_id( ?int $club_id ): void {
        $this->club_id = $club_id;
    }

    /**
     * Get club object.
     *
     * @return object|null
     */
    public function get_club(): ?object {
        return $this->club;
    }

    /**
     * Set club object.
     *
     * @param object|null $club Club object.
     */
    public function set_club( ?object $club ): void {
        $this->club = $club;
    }

    /**
     * Check if the entry is pending (status 0).
     *
     * @return bool
     */
    public function is_pending(): bool {
        return isset( $this->status ) && self::STATUS_PENDING === $this->status;
    }

    /**
     * Check if the entry is confirmed (status 2).
     *
     * @return bool
     */
    public function is_confirmed(): bool {
        return isset( $this->status ) && self::STATUS_CONFIRMED === $this->status;
    }

    /**
     * Check if the entry is withdrawn (status 3).
     *
     * @return bool
     */
    public function is_withdrawn(): bool {
        return isset( $this->status ) && self::STATUS_WITHDRAWN === $this->status;
    }

    /**
     * Mark the entry as withdrawn and record a domain event.
     *
     * @param string|null $reason Reason for withdrawal.
     *
     * @throws DomainException If entry has not been persisted (missing ID) or missing tournament/player ID.
     */
    public function withdraw( ?string $reason = null ): void {
        if ( $this->is_withdrawn() ) {
            return;
        }

        if ( empty( $this->id ) || empty( $this->tournament_id ) || empty( $this->player_id ) ) {
            throw new DomainException( 'Cannot withdraw an incomplete or unpersisted tournament entry without ID, tournament_id, and player_id.' );
        }

        $this->status = self::STATUS_WITHDRAWN;
        $this->record_event(
            new Tournament_Entry_Withdrawn_Event(
                entry_id: $this->id,
                tournament_id: $this->tournament_id,
                player_id: $this->player_id,
                reason: $reason,
            )
        );
    }

    /**
     * Submit the tournament entry and record a domain event.
     *
     * A Tournament_Entry_Submitted_Event is recorded only on a true transition: the first
     * submit() of an entry built with create() (after it has been persisted), or re-submission
     * of a withdrawn entry. Calling submit() on an entry rehydrated in an active status
     * (even with arguments) updates the properties without emitting a duplicate event.
     *
     * @param Money|string|int|float|null $fee     Optional fee to set during submission.
     * @param int|null                    $club_id Optional club ID to set during submission.
     * @param int|null                    $status  Optional explicit active status (0, 1, 2, or 4).
     * @return self
     *
     * @throws DomainException If entry has not been persisted (missing ID) or missing tournament/player ID.
     *                         An InvalidArgumentException is also raised for a non-active explicit status.
     */
    public function submit( Money|string|int|float|null $fee = null, ?int $club_id = null, ?int $status = null ): self {
        if ( empty( $this->id ) || empty( $this->tournament_id ) || empty( $this->player_id ) ) {
            throw new DomainException( 'Cannot submit an incomplete or unpersisted tournament entry without ID, tournament_id, and player_id.' );
        }

        if ( null !== $status ) {
            self::assert_active_status( $status );
        }

        $is_resubmission     = isset( $this->status ) && self::STATUS_WITHDRAWN === $this->status;
        $should_record_event = $this->pending_submission || $is_resubmission;

        if ( null !== $club_id ) {
            $this->set_club_id( $club_id );
        }

        if ( $fee instanceof Money ) {
            $this->set_fee_money( $fee );
        } elseif ( null !== $fee ) {
            $this->hydrate_fee( $fee );
        }

        if ( null !== $status ) {
            $this->set_status( $status );
        } elseif ( $is_resubmission || ! isset( $this->status ) ) {
            $this->set_status( ! empty( $this->club_id ) ? self::STATUS_CONFIRMED : self::STATUS_PENDING );
        }

        if ( $should_record_event ) {
            $this->pending_submission = false;
            $this->record_event(
                new Tournament_Entry_Submitted_Event(
                    entry_id: $this->id,
                    tournament_id: $this->tournament_id,
                    player_id: $this->player_id,
                    club_id: $this->club_id,
                    fee: $this->fee_money,
                )
            );
        }

        return $this;
    }

    /**
     * Assert the status is an active workflow status.
     *
     * @param int $status Status to validate.
     *
     * @throws InvalidArgumentException If status is invalid or STATUS_WITHDRAWN.
     */
    private static function assert_active_status( int $status ): void {
        if ( ! in_array( $status, self::ACTIVE_STATUSES, true ) ) {
            // phpcs:disable WordPress.Security.EscapeOutput.ExceptionNotEscaped
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid tournament entry submission status %d. Expected one of: %s.',
                    $status,
                    implode( ', ', self::ACTIVE_STATUSES )
                )
            );
            // phpcs:enable WordPress.Security.EscapeOutput.ExceptionNotEscaped
        }
    }

    /**
     * Record a domain event.
     *
     * @param Domain_Event_Interface $event Event to record.
     */
    public function record_event( Domain_Event_Interface $event ): void {
        $this->domain_events[] = $event;
    }

    /**
     * Pull all recorded domain events and clear the internal buffer.
     *
     * @return array<int, Domain_Event_Interface>
     */
    public function pull_domain_events(): array {
        $events              = $this->domain_events;
        $this->domain_events = array();
        return $events;
    }

    /**
     * Check whether there are any recorded domain events.
     *
     * @return bool
     */
    public function has_domain_events(): bool {
        return ! empty( $this->domain_events );
    }

    /**
     * Check whether entry has a fee assigned.
     *
     * @return bool
     */
    public function has_fee(): bool {
        return null !== $this->fee_money && $this->fee_money->get_cents() > 0;
    }

    /**
     * Check whether entry is associated with a club.
     *
     * @return bool
     */
    public function has_club(): bool {
        return null !== $this->club_id && $this->club_id > 0;
    }

    /**
     * Check whether entry belongs to a given tournament.
     *
     * @param int $tournament_id Tournament ID.
     *
     * @return bool
     */
    public function is_for_tournament( int $tournament_id ): bool {
        return isset( $this->tournament_id ) && $this->tournament_id === $tournament_id;
    }

    /**
     * Check whether entry belongs to a given player.
     *
     * @param int $player_id Player ID.
     *
     * @return bool
     */
    public function is_for_player( int $player_id ): bool {
        return isset( $this->player_id ) && $this->player_id === $player_id;
    }
}
