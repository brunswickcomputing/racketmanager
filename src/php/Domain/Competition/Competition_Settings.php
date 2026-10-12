<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Competition;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use ArrayIterator;
use Traversable;

/**
 * Value object representing competition settings.
 */
final class Competition_Settings implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable {
	/**
	 * @var array
	 */
	private array $settings;

	/**
	 * Constructor
	 *
	 * @param array $settings
	 */
	public function __construct( array $settings = [] ) {
		$this->settings = $settings;
	}

	/**
	 * Get all settings.
	 *
	 * @return array
	 */
	public function all(): array {
		return $this->settings;
	}

	/**
	 * Get a setting by key.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function get( string $key, mixed $default = null ): mixed {
		return $this->settings[ $key ] ?? $default;
	}

	/**
	 * Set a setting by key, returning a new instance.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return self
	 */
	public function with( string $key, mixed $value ): self {
		$settings = $this->settings;
		$settings[ $key ] = $value;
		return new self( $settings );
	}

	/**
	 * Get competition mode.
	 *
	 * @return string
	 */
	public function mode(): string {
		return (string) $this->get( 'mode', 'default' );
	}

	/**
	 * Get entry type.
	 *
	 * @return string
	 */
	public function entry_type(): string {
		return (string) $this->get( 'entry_type', 'team' );
	}

	/**
	 * Get sport.
	 *
	 * @return string
	 */
	public function sport(): string {
		return (string) $this->get( 'sport', 'tennis' );
	}

	/**
	 * Get number of courts available by club.
	 *
	 * @return array
	 */
	public function num_courts_available(): array {
		return (array) $this->get( 'num_courts_available', [] );
	}

	/**
	 * ArrayAccess offsetExists.
	 *
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists( mixed $offset ): bool {
		return isset( $this->settings[ $offset ] );
	}

	/**
	 * ArrayAccess offsetGet.
	 *
	 * @param mixed $offset
	 * @return mixed
	 */
	public function offsetGet( mixed $offset ): mixed {
		return $this->settings[ $offset ] ?? null;
	}

	/**
	 * ArrayAccess offsetSet.
	 *
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet( mixed $offset, mixed $value ): void {
		if ( is_null( $offset ) ) {
			$this->settings[] = $value;
		} else {
			$this->settings[ $offset ] = $value;
		}
	}

	/**
	 * ArrayAccess offsetUnset.
	 *
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset( mixed $offset ): void {
		unset( $this->settings[ $offset ] );
	}

	/**
	 * Countable count.
	 *
	 * @return int
	 */
	public function count(): int {
		return count( $this->settings );
	}

	/**
	 * IteratorAggregate getIterator.
	 *
	 * @return Traversable
	 */
	public function getIterator(): Traversable {
		return new ArrayIterator( $this->settings );
	}

	/**
	 * JsonSerializable jsonSerialize.
	 *
	 * @return array
	 */
	public function jsonSerialize(): array {
		return $this->settings;
	}
}
