<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Event\DTOs;

/**
 * Result DTO for Get_Event_Handler.
 */
final readonly class Event_Page_Result {
    public function __construct(
        public ?object $event = null,
        public array $seasons = [],
        public string $curr_season = '',
        public string $tab = 'standings',
        public array $menu_options = [],
        public bool $is_singular = false,
        public string $competition_type = '',
        public string $event_seo_name = '',
        public int|string|null $post_id = null,
        public ?string $pagename = null,
        public string $template = 'event',
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }
}
