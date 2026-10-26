<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Support;

/**
 * How violations of a Layer_Rule are judged.
 */
enum Rule_Mode: string {
    /** Any violation fails; the rule must not have baseline entries. */
    case STRICT = 'strict';

    /**
     * Known violations are loaded from baseline.json; only violations that are not in the baseline
     * (or exceed the recorded count for their key) fail. Stale entries are reported, not failed.
     */
    case RATCHET = 'ratchet';
}
