<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Competition\Queries;

final readonly class Get_Competition_Payment_Query {
    public function __construct(
        public string $competition_type,
        public string $tournament_name,
        public int $player_id,
        public string $template = '',
        public array $billing_options = [],
        public bool $complete = false
    ) {}

    public static function from_atts( array|string $atts, array $query_vars, int $player_id, array $billing_options = [], bool $complete = false ): self {
        $atts = is_array( $atts ) ? $atts : [];
        $template = $complete ? '' : ( $atts['template'] ?? '' );
        return new self(
            (string) ( $query_vars['competition_type'] ?? '' ),
            (string) ( $query_vars['tournament'] ?? '' ),
            $player_id,
            is_string( $template ) ? ( preg_replace( '/[^A-Za-z0-9_-]/', '', $template ) ?: '' ) : '',
            $billing_options,
            $complete
        );
    }
}