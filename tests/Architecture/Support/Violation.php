<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Support;

/**
 * One occurrence of a rule being broken. The baseline key deliberately excludes the line number so
 * that unrelated edits in a file do not churn the baseline.
 */
final readonly class Violation {
    public function __construct(
        public string $rule_id,
        public string $file,
        public int $line,
        public string $detail,
    ) {
    }

    /**
     * Line-independent key used inside the baseline for this rule: `file|detail`.
     */
    public function key(): string {
        return $this->file . '|' . $this->detail;
    }

    /**
     * Globally unique key: `rule_id|file|detail`.
     */
    public function full_key(): string {
        return $this->rule_id . '|' . $this->key();
    }

    public function describe(): string {
        return sprintf( '%s:%d — %s', $this->file, $this->line, $this->detail );
    }
}
