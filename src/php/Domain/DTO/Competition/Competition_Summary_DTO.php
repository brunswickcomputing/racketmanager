<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\DTO\Competition;

use Racketmanager\Domain\Competition\Competition_Type;
use function is_array;
use function is_object;

/**
 * Data Transfer Object representing competition summary information.
 */
readonly class Competition_Summary_DTO {
	public int $id;
	public string $name;
	public ?string $age_group;
	public string $type;
	public int $season_count;
	public int $event_count;

	public function __construct( object|array $data ) {
		if ( is_array( $data ) ) {
			$data = (object) $data;
		}

		$this->id           = (int) ( $data->id ?? 0 );
		$this->name         = (string) ( $data->name ?? '' );
		$this->age_group    = isset( $data->age_group ) && '' !== $data->age_group ? (string) $data->age_group : null;
		$this->type         = is_object( $data->type ?? null ) && $data->type instanceof Competition_Type
			? $data->type->value
			: (string) ( $data->type ?? '' );
		$this->season_count = (int) ( $data->season_count ?? 0 );
		$this->event_count  = (int) ( $data->event_count ?? 0 );
	}

	public function get_id(): int {
		return $this->id;
	}

	public function get_name(): string {
		return $this->name;
	}

	public function get_type(): string {
		return $this->type;
	}

	public function get_age_group(): ?string {
		return $this->age_group;
	}

	public function get_season_count(): int {
		return $this->season_count;
	}

	public function get_event_count(): int {
		return $this->event_count;
	}
}
