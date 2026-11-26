<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

/**
 * Represents the input data required to request or render competition payment details.
 *
 * Encapsulates the competition type, tournament name, player context, optional template selection,
 * billing configuration, and completion state for a competition payment query.
 */
final readonly class Get_Competition_Payment_Query {
    /**
     * Creates a new tournament competition instance.
     *
     * @param string $competition_type The type of competition.
     * @param string $tournament_name The tournament name.
     * @param int    $player_id The player identifier.
     * @param string $template The template name or identifier.
     * @param array  $billing_options Billing configuration options.
     * @param bool   $complete Whether the tournament setup is complete.
     *
     * @return void
     */
    public function __construct(
        public string $competition_type,
        public string $tournament_name,
        public int $player_id,
        public string $template = '',
        public array $billing_options = [],
        public bool $complete = false
    ) {}

    /**
     * Creates an instance from shortcode attributes, query variables, player context, and billing options.
     *
     * @param array|string $atts Shortcode attributes used to determine optional template data.
     * @param array        $query_vars Query variables containing competition and tournament identifiers.
     * @param int          $player_id Player identifier to associate with the instance.
     * @param array        $billing_options Optional billing configuration values.
     * @param bool         $complete Whether the instance represents a completed state.
     *
     * @return self Created instance populated from the provided values.
     */
    public static function from_atts( array|string $atts, array $query_vars, int $player_id, array $billing_options = [], bool $complete = false ): self {
        $atts     = is_array( $atts ) ? $atts : [];
        $template = is_string( $atts['template'] ?? null )
            ? ( preg_replace( '/[^A-Za-z0-9_-]/', '', $atts['template'] ) ?? '' )
            : '';
        $template = $complete ? '' : $template;
        return new self(
            (string) ( $query_vars['competition_type'] ?? '' ),
            (string) ( $query_vars['tournament'] ?? '' ),
            $player_id,
            $template,
            $billing_options,
            $complete
        );
    }
}
