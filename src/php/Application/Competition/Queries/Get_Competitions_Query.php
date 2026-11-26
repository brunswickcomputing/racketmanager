<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

/**
 * Immutable query object containing normalised criteria for retrieving competitions.
 *
 * Represents optional competition filters such as type, age group, and associated user ID.
 * Values may be provided directly through construction or resolved from shortcode attributes,
 * query variables, and request data using the factory method.
 */
final readonly class Get_Competitions_Query {
    /**
     * Initialises a new instance with optional type, age group, and user identifier values.
     *
     * @param string|null $type The type value, or null if unspecified.
     * @param string|null $age_group The age group value, or null if unspecified.
     * @param int|null    $user_id The user identifier, or null if unspecified.
     *
     * @return void
     */
    public function __construct(
        public ?string $type,
        public ?string $age_group = null,
        public ?int $user_id = null
    ) {}

    /**
     * Creates an instance from shortcode attributes, query variables, request data, and an optional user ID.
     *
     * @param array|string $atts Shortcode attributes containing optional type and age group values.
     * @param array        $query_vars Query variables used as fallback values for type and age group.
     * @param array        $get Request values used to resolve and sanitize the competition type when applicable.
     * @param int|null     $user_id Optional user ID to associate with the instance. Non-positive values are ignored.
     *
     * @return self Instance populated with normalized type, age group, and user ID values.
     */
    public static function from_atts( array|string $atts, array $query_vars = [], array $get = [], ?int $user_id = null ): self {
        $atts      = is_array( $atts ) ? $atts : [];
        $type      = $atts['type'] ?? null;
        $age_group = $atts['age_group'] ?? null;
        if ( ! $type ) {
            if ( isset( $get['competition_type'] ) && ! empty( $get['type'] ) ) {
                $type = htmlspecialchars( wp_strip_all_tags( wp_unslash( (string) $get['type'] ) ) );
            } else {
                $type = $query_vars['type'] ?? null;
            }
        }
        if ( isset( $query_vars['age_group'] ) ) {
            $age_group = $query_vars['age_group'];
        }

        return new self(
            is_scalar( $type ) && $type ? (string) $type : null,
            is_scalar( $age_group ) && $age_group ? (string) $age_group : null,
            $user_id && $user_id > 0 ? $user_id : null
        );
    }
}
