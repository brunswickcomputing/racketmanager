<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Tournament;

use stdClass;

/**
 * Data Transfer Object for hydrating a Tournament entity from raw data.
 */
readonly class Tournament_Hydration_DTO {
	/**
	 * @param int|null $id
	 * @param string $name
	 * @param int|null $competition_id
	 * @param string|null $season
	 * @param int|null $num_courts
	 * @param string|null $start_time
	 * @param string $date_end
	 * @param string|null $date_closing
	 * @param string|null $date_withdrawal
	 * @param string|null $date_open
	 * @param string|null $date_start
	 * @param int|null $venue
	 * @param string $venue_name
	 * @param array|string|null $order_of_play
	 * @param string|null $time_increment
	 * @param string|null $competition_code
	 * @param string|null $grade
	 * @param int|null $num_entries
	 * @param string $type
	 * @param float|null $payments
	 * @param null|object|array $information
	 */
	public function __construct(
		public ?int $id = null,
		public string $name = '',
		public ?int $competition_id = null,
		public ?string $season = null,
		public ?int $num_courts = null,
		public ?string $start_time = null,
		public string $date_end = '',
		public ?string $date_closing = null,
		public ?string $date_withdrawal = null,
		public ?string $date_open = null,
		public ?string $date_start = null,
		public ?int $venue = null,
		public string $venue_name = '',
		public array|string|null $order_of_play = null,
		public ?string $time_increment = null,
		public ?string $competition_code = null,
		public ?string $grade = null,
		public ?int $num_entries = null,
		public string $type = '',
		public ?float $payments = null,
		public null|object|array $information = null
	) {
	}

	/**
	 * Create from a database row or generic object.
	 *
	 * @param object $data
	 * @return self
	 */
	public static function from_object( object $data ): self {
		$information = $data->information ?? null;
		if ( is_string( $information ) ) {
			if ( str_starts_with( $information, 'a:' ) || str_starts_with( $information, 'O:' ) || str_starts_with( $information, 's:' ) ) {
				$unserialized = @unserialize( $information );
			} else {
				$unserialized = false;
			}
			if ( is_array( $unserialized ) || is_object( $unserialized ) ) {
				$information = $unserialized;
			} else {
				$decoded     = json_decode( $information, false );
				$information = ( null !== $decoded && false !== $decoded ) ? $decoded : $information;
			}
		}

		$order_of_play = $data->order_of_play ?? ( $data->orderofplay ?? null );
		if ( is_string( $order_of_play ) ) {
			if ( str_starts_with( $order_of_play, 'a:' ) || str_starts_with( $order_of_play, 'O:' ) || str_starts_with( $order_of_play, 's:' ) ) {
				$unserialized = @unserialize( $order_of_play );
				if ( is_array( $unserialized ) ) {
					$order_of_play = $unserialized;
				}
			}
		}

		return new self(
			id: isset( $data->id ) ? (int) $data->id : null,
			name: (string) ( $data->name ?? '' ),
			competition_id: isset( $data->competition_id ) ? (int) $data->competition_id : null,
			season: isset( $data->season ) ? (string) $data->season : null,
			num_courts: isset( $data->num_courts ) ? (int) $data->num_courts : ( isset( $data->numcourts ) ? (int) $data->numcourts : null ),
			start_time: isset( $data->start_time ) ? (string) $data->start_time : ( isset( $data->starttime ) ? (string) $data->starttime : null ),
			date_end: (string) ( $data->date_end ?? ( $data->date ?? '' ) ),
			date_closing: isset( $data->date_closing ) ? (string) $data->date_closing : null,
			date_withdrawal: isset( $data->date_withdrawal ) ? (string) $data->date_withdrawal : null,
			date_open: isset( $data->date_open ) ? (string) $data->date_open : null,
			date_start: isset( $data->date_start ) ? (string) $data->date_start : null,
			venue: isset( $data->venue ) ? (int) $data->venue : null,
			venue_name: (string) ( $data->venue_name ?? '' ),
			order_of_play: $order_of_play,
			time_increment: isset( $data->time_increment ) ? (string) $data->time_increment : ( isset( $data->timeincrement ) ? (string) $data->timeincrement : null ),
			competition_code: isset( $data->competition_code ) ? (string) $data->competition_code : null,
			grade: isset( $data->grade ) ? (string) $data->grade : null,
			num_entries: isset( $data->num_entries ) ? (int) $data->num_entries : null,
			type: (string) ( $data->type ?? '' ),
			payments: isset( $data->payments ) ? (float) $data->payments : null,
			information: is_array( $information ) ? (object) $information : ( is_object( $information ) ? $information : null )
		);
	}

	/**
	 * Create from an associative array.
	 *
	 * @param array $data
	 * @return self
	 */
	public static function from_array( array $data ): self {
		return self::from_object( (object) $data );
	}

	/**
	 * Convert DTO to a standard object compatible with legacy entity hydration.
	 *
	 * @return stdClass
	 */
	public function to_std_class(): stdClass {
		$obj                   = new stdClass();
		$obj->id               = $this->id;
		$obj->name             = $this->name;
		$obj->competition_id   = $this->competition_id;
		$obj->season           = $this->season;
		$obj->num_courts       = $this->num_courts;
		$obj->numcourts        = $this->num_courts;
		$obj->start_time       = $this->start_time;
		$obj->starttime        = $this->start_time;
		$obj->date_end         = $this->date_end;
		$obj->date             = $this->date_end;
		$obj->date_closing     = $this->date_closing;
		$obj->date_withdrawal  = $this->date_withdrawal;
		$obj->date_open        = $this->date_open;
		$obj->date_start       = $this->date_start;
		$obj->venue            = $this->venue;
		$obj->venue_name       = $this->venue_name;
		$obj->order_of_play    = $this->order_of_play;
		$obj->orderofplay      = $this->order_of_play;
		$obj->time_increment   = $this->time_increment;
		$obj->timeincrement    = $this->time_increment;
		$obj->competition_code = $this->competition_code;
		$obj->grade            = $this->grade;
		$obj->num_entries      = $this->num_entries;
		$obj->type             = $this->type;
		$obj->payments         = $this->payments;
		$obj->information      = $this->information;

		return $obj;
	}
}
