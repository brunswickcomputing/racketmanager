<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture;

use PHPUnit\Framework\TestCase;
use Racketmanager\Tests\Architecture\Support\Baseline;
use Racketmanager\Tests\Architecture\Support\Baseline_Diff;
use Racketmanager\Tests\Architecture\Support\Layer_Rule;
use Racketmanager\Tests\Architecture\Support\Rule_Evaluator;
use Racketmanager\Tests\Architecture\Support\Source_File;
use Racketmanager\Tests\Architecture\Support\Source_Scanner;
use Racketmanager\Tests\Architecture\Support\Violation;

/**
 * Base class for architecture guardrail tests. Subclasses feed Layer_Rule instances to
 * assert_rule_holds(), which evaluates the rule against the scanned plugin, diffs the result against
 * the ratchet baseline and fails with an actionable message when new debt is found.
 *
 * Set RM_ARCH_UPDATE_BASELINE=1 to rewrite the baseline entries of every evaluated ratchet rule
 * instead of asserting against them (strict rules are always asserted).
 */
abstract class Architecture_Test_Case extends TestCase {
    private const int MAX_LISTED_VIOLATIONS = 40;

    /** @var array<string, int> Stale-entry warnings already printed in this process, keyed by rule id. */
    private static array $reported_stale = [];

    /**
     * Files the rules are evaluated against. Override to scan a different tree (e.g. fixtures).
     *
     * @return array<string, Source_File>
     */
    protected function files(): array {
        return Source_Scanner::scan_plugin();
    }

    /**
     * Baseline file used by this test class. Override to use a different file (e.g. in tests of the engine).
     */
    protected function baseline_path(): string {
        return Baseline::default_path();
    }

    /**
     * Evaluate `$rule`, compare with the baseline and fail on new violations.
     *
     * @param ?array<string, Source_File> $files Optional explicit file set (defaults to files()).
     */
    protected function assert_rule_holds( Layer_Rule $rule, ?array $files = null ): void {
        $violations = new Rule_Evaluator()->evaluate( $rule, $files ?? $this->files() );
        $baseline   = Baseline::load( $this->baseline_path() );

        if ( $rule->is_strict() ) {
            if ( Baseline::update_requested() && $baseline->has_rule( $rule->id ) ) {
                $baseline->without_rule( $rule->id )->write( $this->baseline_path() );
            } elseif ( $baseline->has_rule( $rule->id ) ) {
                $this->fail( sprintf(
                    "Architecture rule `%s` is strict but has %d baseline entries in %s.\nRemove them (strict rules must have none) or change the rule mode to ratchet.",
                    $rule->id,
                    count( $baseline->entries_for( $rule->id ) ),
                    $this->baseline_path()
                ) );
            }

            if ( [] !== $violations ) {
                $this->fail( $this->failure_message( $rule, $violations, null ) );
            }

            $this->addToAssertionCount( 1 );
            return;
        }

        if ( Baseline::update_requested() ) {
            $baseline->with_rule_violations( $rule->id, $violations )->write( $this->baseline_path() );
            $this->addToAssertionCount( 1 );
            return;
        }

        $diff = $baseline->compare( $rule->id, $violations );
        $this->report_stale_entries( $rule, $diff );

        if ( $diff->has_new_violations() ) {
            $this->fail( $this->failure_message( $rule, $diff->new_violations(), $diff ) );
        }

        $this->addToAssertionCount( 1 );
    }

    /**
     * @param array<int,Violation> $violations
     */
    protected function failure_message( Layer_Rule $rule, array $violations, ?Baseline_Diff $diff ): string {
        $lines   = [];
        $lines[] = sprintf(
            'Architecture rule `%s` (%s) has %d %sviolation(s):',
            $rule->id,
            $rule->description,
            count( $violations ),
            $rule->is_strict() ? '' : 'new '
        );

        foreach ( array_slice( $violations, 0, self::MAX_LISTED_VIOLATIONS ) as $violation ) {
            $lines[] = '  - ' . $violation->describe();
        }
        if ( count( $violations ) > self::MAX_LISTED_VIOLATIONS ) {
            $lines[] = sprintf( '  … and %d more', count( $violations ) - self::MAX_LISTED_VIOLATIONS );
        }

        if ( null !== $diff ) {
            $exceeded = array_filter( $diff->new, static fn( array $entry ): bool => $entry['allowed'] > 0 );
            if ( [] !== $exceeded ) {
                $lines[] = '  Keys whose occurrence count now exceeds the baseline (every occurrence is listed above):';
                foreach ( $exceeded as $key => $entry ) {
                    $lines[] = sprintf( '    %s: %d > %d tolerated', $key, $entry['count'], $entry['allowed'] );
                }
            }
            $lines[] = sprintf( '  The baseline covers %d of %d current violation(s) of this rule.', $diff->accepted, $diff->total );
        }

        $lines[] = '';
        $lines[] = 'Remediation: ' . $rule->remediation;
        $lines[] = 'A failing guardrail means new architectural debt was introduced. Fix the code rather than regenerating the baseline';
        $lines[] = sprintf( '(%s=1 vendor/bin/phpunit --testsuite Architecture is only for removing debt or intentional renames).', Baseline::ENV_UPDATE );

        return implode( "\n", $lines );
    }

    private function report_stale_entries( Layer_Rule $rule, Baseline_Diff $diff ): void {
        $dedupe_key = static::class . '|' . $rule->id;
        if ( ! $diff->has_stale_entries() || isset( self::$reported_stale[ $dedupe_key ] ) ) {
            return;
        }
        self::$reported_stale[ $dedupe_key ] = count( $diff->stale );

        $removed = 0;
        foreach ( $diff->stale as $entry ) {
            $removed += $entry['allowed'] - $entry['count'];
        }

        $message = sprintf(
            "\n[architecture] rule `%s`: %d stale baseline entr%s (%d occurrence(s) of debt no longer present). Prune with %s=1 vendor/bin/phpunit --testsuite Architecture.\n",
            $rule->id,
            count( $diff->stale ),
            1 === count( $diff->stale ) ? 'y' : 'ies',
            $removed,
            Baseline::ENV_UPDATE
        );
        foreach ( array_slice( array_keys( $diff->stale ), 0, 10 ) as $key ) {
            $message .= '  - ' . $key . "\n";
        }

        $this->warn( $message );
    }

    /**
     * Emit a non-failing warning (stale baseline entries). Written to STDERR so it is visible in CI
     * logs without affecting the PHPUnit result.
     */
    protected function warn( string $message ): void {
        fwrite( STDERR, $message );
    }
}
