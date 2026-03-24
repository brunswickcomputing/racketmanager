<?php
declare( strict_types=1 );

namespace Racketmanager\Domain\Enums\Fixture;

/**
 * Enumeration of possible outcomes for a fixture result update.
 */
enum Fixture_Update_Status: string {
    case SAVED = 'saved';
    case PROGRESSED = 'progressed';
    case TABLE_UPDATED = 'table_updated';
}
