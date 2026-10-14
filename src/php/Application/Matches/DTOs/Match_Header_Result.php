<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Matches\DTOs;

use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;

/**
 * Read model returned by the [match-header] query handler.
 */
final readonly class Match_Header_Result {
    public function __construct(
        public ?Fixture_Details_DTO $details = null,
        public ?string $error_message = null,
        public mixed $edit_mode = false,
        public string $template = ''
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }
}
