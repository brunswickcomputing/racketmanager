<?php
declare( strict_types=1 );

namespace Racketmanager\Application\League\DTOs;

/**
 * Result DTO returned by Get_League_Crosstable_Handler.
 */
final readonly class League_Crosstable_Result {
    /**
     * @param object|null $league
     * @param array<int, object> $teams Row teams (ranked)
     * @param array<int, object> $columns Column teams
     * @param array<int, array<int, string>> $cells Score matrix [row_id][col_id]
     */
    public function __construct(
        public ?object $league = null,
        public array $teams = [],
        public array $columns = [],
        public array $cells = [],
        public string $mode = '',
        public ?string $season = null,
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
