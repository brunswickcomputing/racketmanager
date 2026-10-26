<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Support;

use JsonException;
use RuntimeException;

/**
 * The ratchet baseline: known violations per rule, persisted as sorted JSON.
 *
 * File format (`tests/Architecture/baseline.json`):
 *
 *     {
 *       "all.strict_types": {
 *         "src/php/Admin/Admin.php|missing declare(strict_types=1)": 1
 *       },
 *       "services.no_wpdb": {
 *         "src/php/Services/Team_Service.php|$wpdb": 7
 *       }
 *     }
 *
 * Keys are line-independent (`file|detail`, see Violation::key()); the value is how many occurrences
 * of that key are tolerated. A rule passes while no key exceeds its tolerated count, so removing
 * debt never fails the suite and adding debt always does.
 */
final class Baseline {
    public const string ENV_UPDATE = 'RM_ARCH_UPDATE_BASELINE';

    /**
     * @param array<string, array<string, int>> $entries rule_id => [ key => tolerated count ]
     */
    public function __construct( private array $entries = [] ) {
        $this->entries = self::normalise( $entries );
    }

    public static function default_path(): string {
        return dirname( __DIR__ ) . '/baseline.json';
    }

    /**
     * Whether the current process was asked to regenerate the baseline.
     */
    public static function update_requested(): bool {
        $value = getenv( self::ENV_UPDATE );
        if ( false === $value || '' === $value ) {
            return false;
        }

        return ! in_array( strtolower( $value ), [ '0', 'false', 'no', 'off' ], true );
    }

    /**
     * Load a baseline file; a missing file yields an empty baseline.
     */
    public static function load( ?string $path = null ): self {
        $path = $path ?? self::default_path();
        if ( ! is_file( $path ) ) {
            return new self();
        }

        $json = file_get_contents( $path );
        if ( false === $json || '' === trim( $json ) ) {
            return new self();
        }

        try {
            $decoded = json_decode( $json, true, 8, JSON_THROW_ON_ERROR );
        } catch ( JsonException $exception ) {
            throw new RuntimeException( sprintf( 'Architecture baseline %s is not valid JSON: %s', $path, $exception->getMessage() ), 0, $exception );
        }
        if ( ! is_array( $decoded ) ) {
            throw new RuntimeException( sprintf( 'Architecture baseline %s must decode to an object.', $path ) );
        }

        return new self( $decoded );
    }

    /**
     * @return array<int,string>
     */
    public function rule_ids(): array {
        return array_keys( $this->entries );
    }

    public function has_rule( string $rule_id ): bool {
        return isset( $this->entries[ $rule_id ] ) && [] !== $this->entries[ $rule_id ];
    }

    /**
     * @return array<string, int> key => tolerated count
     */
    public function entries_for( string $rule_id ): array {
        return $this->entries[ $rule_id ] ?? [];
    }

    public function count_for( string $rule_id ): int {
        return array_sum( $this->entries_for( $rule_id ) );
    }

    /**
     * Compare current violations of one rule with the tolerated entries.
     *
     * @param array<int,Violation> $violations
     */
    public function compare( string $rule_id, array $violations ): Baseline_Diff {
        $allowed = $this->entries_for( $rule_id );

        /** @var array<string, array<int,Violation>> $grouped */
        $grouped = [];
        foreach ( $violations as $violation ) {
            $grouped[ $violation->key() ][] = $violation;
        }

        $new      = [];
        $stale    = [];
        $accepted = 0;
        foreach ( $grouped as $key => $group ) {
            $count     = count( $group );
            $tolerated = $allowed[ $key ] ?? 0;
            if ( $count > $tolerated ) {
                $new[ $key ] = [ 'count' => $count, 'allowed' => $tolerated, 'violations' => $group ];
                $accepted   += $tolerated;
            } else {
                $accepted += $count;
                if ( $count < $tolerated ) {
                    $stale[ $key ] = [ 'allowed' => $tolerated, 'count' => $count ];
                }
            }
        }
        foreach ( $allowed as $key => $tolerated ) {
            if ( ! isset( $grouped[ $key ] ) ) {
                $stale[ $key ] = [ 'allowed' => $tolerated, 'count' => 0 ];
            }
        }
        ksort( $new, SORT_STRING );
        ksort( $stale, SORT_STRING );

        return new Baseline_Diff( $rule_id, $new, $stale, $accepted, count( $violations ) );
    }

    /**
     * Return a copy whose entries for `$rule_id` are exactly the given violations (an empty list
     * removes the rule from the baseline).
     *
     * @param array<int,Violation> $violations
     */
    public function with_rule_violations( string $rule_id, array $violations ): self {
        $entries = $this->entries;
        unset( $entries[ $rule_id ] );

        $counts = [];
        foreach ( $violations as $violation ) {
            $counts[ $violation->key() ] = ( $counts[ $violation->key() ] ?? 0 ) + 1;
        }
        if ( [] !== $counts ) {
            $entries[ $rule_id ] = $counts;
        }

        return new self( $entries );
    }

    /**
     * Return a copy without any entries for `$rule_id`.
     */
    public function without_rule( string $rule_id ): self {
        return $this->with_rule_violations( $rule_id, [] );
    }

    /**
     * @return array<string, array<string, int>> Sorted entries.
     */
    public function to_array(): array {
        return $this->entries;
    }

    public function to_json(): string {
        if ( [] === $this->entries ) {
            return "{}\n";
        }

        try {
            return json_encode( $this->entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR ) . "\n";
        } catch ( JsonException $exception ) {
            throw new RuntimeException( 'Architecture baseline could not be serialised: ' . $exception->getMessage(), 0, $exception );
        }
    }

    /**
     * Write the baseline deterministically (sorted keys, stable formatting, trailing newline).
     */
    public function write( ?string $path = null ): void {
        $path = $path ?? self::default_path();
        if ( false === file_put_contents( $path, $this->to_json(), LOCK_EX ) ) {
            throw new RuntimeException( sprintf( 'Could not write architecture baseline to %s', $path ) );
        }
    }

    /**
     * Whether the file at `$path` matches the canonical serialisation of this baseline.
     */
    public function is_sorted_on_disk( ?string $path = null ): bool {
        $path = $path ?? self::default_path();

        return is_file( $path ) && file_get_contents( $path ) === $this->to_json();
    }

    /**
     * Sort rules and keys and coerce counts to positive integers; drop empty rules.
     *
     * @param array<mixed> $entries
     *
     * @return array<string, array<string, int>>
     */
    private static function normalise( array $entries ): array {
        $normalised = [];
        foreach ( $entries as $rule_id => $keys ) {
            if ( ! is_string( $rule_id ) || ! is_array( $keys ) ) {
                continue;
            }
            $rule_entries = [];
            foreach ( $keys as $key => $count ) {
                // Accept the compact list form ["file|detail", …] as well as the counted map form.
                if ( is_int( $key ) && is_string( $count ) ) {
                    $rule_entries[ $count ] = ( $rule_entries[ $count ] ?? 0 ) + 1;
                    continue;
                }
                if ( is_string( $key ) && is_numeric( $count ) && (int) $count > 0 ) {
                    $rule_entries[ $key ] = (int) $count;
                }
            }
            if ( [] !== $rule_entries ) {
                ksort( $rule_entries, SORT_STRING );
                $normalised[ $rule_id ] = $rule_entries;
            }
        }
        ksort( $normalised, SORT_STRING );

        return $normalised;
    }
}
