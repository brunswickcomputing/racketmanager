<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * Generic, logic-less view model produced by shortcode query handlers.
 *
 * Either carries a template + variables to render, or an error to show as an alert.
 */
final readonly class Shortcode_View_Model {
    /**
     * @param string       $template       Template file name without extension.
     * @param array        $vars           Variables exposed to the template.
     * @param false|string $template_type  Template group ('club', 'match', ...); false for the templates root.
     * @param string|null  $error_message  Error message; when set the alert is rendered instead of the template.
     * @param string|null  $error_variant  Alert variant ('modal' renders alert-modal).
     * @param string|null  $raw_html       Raw HTML string if template rendering is bypassed.
     */
    public function __construct(
        public string $template = '',
        public array $vars = [],
        public false|string $template_type = false,
        public ?string $error_message = null,
        public ?string $error_variant = null,
        public ?string $raw_html = null
    ) {}

    public static function error( string $message, ?string $variant = null ): self {
        return new self( error_message: $message, error_variant: $variant );
    }

    public static function for_template( string $template, array $vars = [], false|string $template_type = false ): self {
        return new self( template: $template, vars: $vars, template_type: $template_type );
    }

    public static function for_error( string $message, ?string $variant = null ): self {
        return self::error( $message, $variant );
    }

    public static function for_html( string $html ): self {
        return new self( raw_html: $html );
    }

    public function is_error(): bool {
        return null !== $this->error_message;
    }

    public function is_raw_html(): bool {
        return null !== $this->raw_html;
    }
}
