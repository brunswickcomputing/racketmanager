<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\DTOs;

final readonly class Tournament_Matches_Result {
    /**
     * @param array<int|string, mixed> $fixture_dates
     * @param array<int|string, mixed> $tournament_fixtures
     */
    public function __construct(
        public ?object $tournament = null,
        public array $fixture_dates = [],
        public ?string $fixture_date = null,
        public array $tournament_fixtures = [],
        public string $tab = 'matches',
        public string $template = '',
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
