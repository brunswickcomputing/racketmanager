<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Immutable view model for club link in player headers and lists.
 */
final readonly class Club_Link_View_Model {
    public function __construct(
        public string $club_name,
        public string $club_url = '',
        public string $club_display_name = ''
    ) {}
}
