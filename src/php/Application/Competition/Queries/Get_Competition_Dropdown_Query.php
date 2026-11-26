<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

/**
 * Represents a query object for resolving competition dropdown configuration.
 *
 * Encapsulates the selected competition identifier and optional template name,
 * including creation from shortcode or block attributes with normalised values.
 */
final readonly class Get_Competition_Dropdown_Query {
    /**
     * Creates a new instance with the competition identifier and template.
     *
     * @param int|null $competition_id The competition identifier, or null when not set.
     * @param string   $template The template value.
     *
     * @return void
     */
    public function __construct(
        public ?int $competition_id,
        public string $template = ''
    ) {}

    /**
     * Creates an instance from shortcode or block attributes.
     *
     * Normalises the provided attributes, extracting a positive numeric ID when available
     * and sanitising the template value to allow only letters, numbers, underscores, and hyphens.
     *
     * @param array|string $atts Attributes used to build the instance.
     *
     * @return self Instance populated with the normalized attribute values.
     */
    public static function from_atts( array|string $atts ): self {
        $atts     = is_array( $atts ) ? $atts : [];
        $id       = $atts['id'] ?? null;
        $template = is_string( $atts['template'] ?? null )
            ? ( preg_replace( '/[^A-Za-z0-9_-]/', '', $atts['template'] ) ?? '' )
            : '';

        return new self(
            is_scalar( $id ) && is_numeric( $id ) && (int) $id > 0 ? (int) $id : null,
            $template
        );
    }
}
