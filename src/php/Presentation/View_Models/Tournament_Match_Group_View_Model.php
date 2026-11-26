<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View model representing a group of tournament fixtures.
 */
final readonly class Tournament_Match_Group_View_Model {
    /**
     * Creates a new instance with the specified key, header, and optional fixtures.
     *
     * @param string $key The unique key associated with the instance.
     * @param string $header The header value associated with the instance.
     * @param array  $fixtures Optional fixture data.
     *
     * @return void
     */
    public function __construct(
        public string $key,
        public string $header,
        public array $fixtures = []
    ) {}
}
