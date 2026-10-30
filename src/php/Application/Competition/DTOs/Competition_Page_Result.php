<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\DTOs;

use Racketmanager\Domain\Competition\Competition;

/**
 * Result DTO for Get_Competition_Handler.
 */
final readonly class Competition_Page_Result {
    public function __construct(
        public ?Competition $competition = null,
        public array $competition_season = [],
        public string $tab = 'overview',
        public array $seasons = [],
        public array $menu_options = [],
        public bool $is_singular = false,
        public int|string|null $post_id = null,
        public string $pagename = '',
        public string $competition_seo_name = '',
        public string $template = 'competition',
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
