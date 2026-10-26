<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Support;

/**
 * Result of comparing a rule's current violations with its baseline entries.
 */
final readonly class Baseline_Diff {
    /**
     * @param string                                                                        $rule_id
     * @param array<string, array{count: int, allowed: int, violations: array<int,Violation>}> $new      Keys whose
     *        occurrences exceed what the baseline allows (allowed is 0 for keys absent from the baseline).
     * @param array<string, array{allowed: int, count: int}>                                $stale    Keys where the
     *        baseline allows more occurrences than currently exist (debt that has been removed).
     * @param int                                                                           $accepted Number of current
     *        violations covered by the baseline.
     * @param int                                                                           $total    Number of current violations.
     */
    public function __construct(
        public string $rule_id,
        public array $new,
        public array $stale,
        public int $accepted,
        public int $total,
    ) {
    }

    public function has_new_violations(): bool {
        return [] !== $this->new;
    }

    public function has_stale_entries(): bool {
        return [] !== $this->stale;
    }

    /**
     * @return array<int,Violation>
     */
    public function new_violations(): array {
        $violations = [];
        foreach ( $this->new as $entry ) {
            foreach ( $entry['violations'] as $violation ) {
                $violations[] = $violation;
            }
        }

        return $violations;
    }

    public function new_violation_count(): int {
        return count( $this->new_violations() );
    }
}
