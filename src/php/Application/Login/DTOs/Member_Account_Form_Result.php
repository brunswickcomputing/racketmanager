<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Login\DTOs;

/**
 * Result DTO returned by Get_Member_Account_Form_Handler.
 */
final readonly class Member_Account_Form_Result {
    /**
     * @param array<string, mixed> $vars
     */
    public function __construct(
        public array $vars = [],
        public ?string $raw_html = null,
        public ?string $template = 'form-member-account',
        public ?string $template_type = 'form'
    ) {}

    public static function html( string $html ): self {
        return new self( raw_html: $html, template: null, template_type: null );
    }

    public static function template( string $template, array $vars = [], ?string $template_type = 'form' ): self {
        return new self( vars: $vars, template: $template, template_type: $template_type );
    }

    public function is_raw_html(): bool {
        return null !== $this->raw_html;
    }

    public function is_error(): bool {
        return false;
    }
}
