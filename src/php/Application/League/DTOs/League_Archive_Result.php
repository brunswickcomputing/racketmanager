<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\DTOs;

/**
 * Result DTO returned by Get_League_Archive_Handler.
 */
final readonly class League_Archive_Result {
    /**
     * @param object|null $league
     * @param object|null $event
     * @param array<int|string, mixed> $seasons
     * @param array<int, object> $leagues
     */
    public function __construct(
        public ?object $league = null,
        public ?object $event = null,
        public array $seasons = [],
        public array $leagues = [],
        public string $tab = 'standings',
        public string $template = '',
        public string $standings = '',
        public string $crosstable = '',
        public string $matches = '',
        public string $teams = '',
        public string $matches_template_type = '',
        public int|string|null $post_id = null,
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
