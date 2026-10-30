<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\DTOs;

final readonly class Tournament_Result {
    /**
     * @param array<string, mixed> $seasons
     * @param array<int|string, mixed> $menu_options
     */
    public function __construct(
        public ?object $tournament = null,
        public ?object $competition = null,
        public ?string $tab = null,
        public array $seasons = [],
        public array $menu_options = [],
        public ?object $tournament_details = null,
        public string $template = '',
        public int|string $post_id = '',
        public ?string $redirect_url = null,
        public ?string $error_message = null
    ) {}

    public static function error( string $message ): self {
        return new self( error_message: $message );
    }

    public static function redirect( string $url ): self {
        return new self( redirect_url: $url );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }

    public function is_redirect(): bool {
        return null !== $this->redirect_url;
    }
}
