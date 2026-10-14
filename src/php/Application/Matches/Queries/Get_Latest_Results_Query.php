<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Matches\Queries;

/**
 * Query for the [latest_results] listing.
 *
 * Club and competition may be identified either by id (shortcode attributes) or by
 * slug/name (pretty-permalink query vars); the handler resolves names to ids.
 */
final readonly class Get_Latest_Results_Query {
    public int $days;
    public string $competition_type;
    public ?int $club_id;
    public ?string $club_shortcode;
    public ?int $competition_id;
    public ?string $competition_name;
    public ?string $age_group;
    public bool $has_invalid_filter;
    public int $header_level;
    public string $template;

    public function __construct(
        int $days = 7,
        string $competition_type = 'league',
        ?int $club_id = null,
        ?string $club_shortcode = null,
        ?int $competition_id = null,
        ?string $competition_name = null,
        ?string $age_group = null,
        int $header_level = 1,
        string $template = 'results'
    ) {
        $this->days             = max( 0, $days );
        $this->competition_type = Matches_Query_Normaliser::competition_type( $competition_type );
        $this->club_id          = ( null !== $club_id && $club_id > 0 ) ? $club_id : null;
        $this->club_shortcode   = ( null !== $club_shortcode && '' !== trim( $club_shortcode ) ) ? trim( $club_shortcode ) : null;
        $this->competition_id   = ( null !== $competition_id && $competition_id > 0 ) ? $competition_id : null;
        $this->competition_name = ( null !== $competition_name && '' !== trim( $competition_name ) ) ? trim( $competition_name ) : null;

        $age_group = null === $age_group ? '' : trim( $age_group );
        if ( '' === $age_group ) {
            $this->age_group          = null;
            $this->has_invalid_filter = false;
        } elseif ( Matches_Query_Normaliser::is_valid_age_group( $age_group ) ) {
            $this->age_group          = $age_group;
            $this->has_invalid_filter = false;
        } else {
            $this->age_group          = null;
            $this->has_invalid_filter = true;
        }

        $this->header_level = Matches_Query_Normaliser::header_level( $header_level );
        $this->template     = Matches_Query_Normaliser::template( $template );
    }
}
