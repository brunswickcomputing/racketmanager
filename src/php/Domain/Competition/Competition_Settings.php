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
 * Represents a collection of competition configuration settings.
 *
 * Provides access to raw settings, typed convenience accessors for common
 * competition options, array-style access, counting, iteration, and JSON
 * serialization.
 *
 * Settings are stored as key-value pairs. The with() method returns a new
 * instance with an updated setting, while ArrayAccess methods modify the
 * current instance directly.
 *
 * @implements ArrayAccess<mixed,mixed>
 * @implements IteratorAggregate<mixed,mixed>
 */
final class Competition_Settings implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable {
    /**
     * @var array
     */
    private array $settings;

    /**
     * Initialize settings.
     *
     * @param array $settings Settings values.
     *
     * @return void
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
     * Gets a setting value by key.
     *
     * @param string $key Setting key.
     * @param mixed  $default_value Default value to return when the key is not set.
     *
     * @return mixed
     */
    public function get( string $key, mixed $default_value = null ): mixed {
        return $this->settings[ $key ] ?? $default_value;
    }

    /**
     * Returns a new instance with the specified setting value.
     *
     * @param string $key Setting key.
     * @param mixed  $value Setting value.
     *
     * @return self New instance with the updated settings.
     */
    public function with( string $key, mixed $value ): self {
        $settings         = $this->settings;
        $settings[ $key ] = $value;
        return new self( $settings );
    }

    /**
     * Gets the current mode.
     *
     * @return string
     */
    public function mode(): string {
        return (string) $this->get( 'mode', 'default' );
    }

    /**
     * Gets the entry type.
     *
     * @return string
     */
    public function entry_type(): string {
        return (string) $this->get( 'entry_type', 'team' );
    }

    /**
     * Get the sport value.
     *
     * @return string
     */
    public function sport(): string {
        return (string) $this->get( 'sport', 'tennis' );
    }

    /**
     * Get the available court counts.
     *
     * @return array
     */
    public function num_courts_available(): array {
        return (array) $this->get( 'num_courts_available', [] );
    }

    /**
     * Checks whether a settings offset exists.
     *
     * @param mixed $offset Offset to check.
     *
     * @return bool True if the offset exists, false otherwise.
     */
    public function offsetExists( mixed $offset ): bool {
        return isset( $this->settings[ $offset ] );
    }

    /**
     * ArrayAccess offsetGet.
     *
     * @param mixed $offset Offset to retrieve.
     *
     * @return mixed Value at the offset, or null if not set.
     */
    public function offsetGet( mixed $offset ): mixed {
        return $this->settings[ $offset ] ?? null;
    }

    /**
     * Sets a settings value at the specified offset, or appends it when the offset is null.
     *
     * @param mixed $offset The offset to assign the value to, or null to append.
     * @param mixed $value The value to store.
     *
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
     * Unsets the setting at the specified offset.
     *
     * @param mixed $offset The offset to unset.
     *
     * @return void
     */
    public function offsetUnset( mixed $offset ): void {
        unset( $this->settings[ $offset ] );
    }

    /**
     * Counts the settings.
     *
     * @return int
     */
    public function count(): int {
        return count( $this->settings );
    }

    /**
     * Gets an iterator over the settings.
     *
     * @return Traversable
     */
    public function getIterator(): Traversable {
        return new ArrayIterator( $this->settings );
    }

    /**
     * Serializes the object settings for JSON encoding.
     *
     * @return array The settings data to be serialized.
     */
    public function jsonSerialize(): array {
        return $this->settings;
    }
}
