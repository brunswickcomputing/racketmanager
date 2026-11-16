<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition;

/**
 * Value object representing a collection of competition seasons.
 */
final class Season_Collection {
	/**
	 * @var array
	 */
	private array $seasons;

	/**
	 * Constructor
	 *
	 * @param array $seasons Seasons array.
	 */
	public function __construct( array $seasons ) {
		$this->seasons = $seasons;
	}

	/**
	 * Get all seasons.
	 *
	 * @return array
	 */
	public function all(): array {
		return $this->seasons;
	}

	/**
	 * Get a season by name.
	 *
	 * @param string|int $name Season name.
	 * @return array|null
	 */
	public function get( string|int $name ): ?array {
		return $this->seasons[ $name ] ?? ( $this->seasons[ (string) $name ] ?? null );
	}

	/**
	 * Get the latest season.
	 *
	 * @return array|null
	 */
	public function latest(): ?array {
		if ( empty( $this->seasons ) ) {
			return null;
		}
		$tmp = $this->seasons;
		return end( $tmp );
	}

	/**
	 * Get seasons in reverse order.
	 *
	 * @return array
	 */
	public function reverse(): array {
		return array_reverse( $this->seasons );
	}

	/**
	 * Check if a season exists.
	 *
	 * @param string|int $name Season name.
	 * @return bool
	 */
	public function has( string|int $name ): bool {
		return isset( $this->seasons[ $name ] ) || isset( $this->seasons[ (string) $name ] );
	}

	/**
	 * Get count of seasons.
	 *
	 * @return int
	 */
	public function count(): int {
		return count( $this->seasons );
	}
}
