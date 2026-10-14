<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Matches\Queries;

/**
 * Query for the [dailymatches] listing.
 *
 * All values are normalised on construction so that downstream finders only ever
 * receive a valid ISO date, a lower-case competition type and a safe template key.
 */
final readonly class Get_Daily_Matches_Query {
    public string $match_date;
    public string $competition_type;
    public string $template;

    /**
     * @param string $match_date       Requested date (Y-m-d). Invalid or empty values fall back to $today.
     * @param string $competition_type Competition type (league, cup, tournament).
     * @param string $template         Template suffix ("daily" renders matches-daily).
     * @param string $today            Fallback date (Y-m-d) used when no valid date is supplied.
     */
    public function __construct(
        string $match_date,
        string $competition_type = 'league',
        string $template = 'daily',
        string $today = ''
    ) {
        $fallback               = Matches_Query_Normaliser::is_valid_date( $today ) ? $today : gmdate( 'Y-m-d' );
        $this->match_date       = Matches_Query_Normaliser::is_valid_date( $match_date ) ? $match_date : $fallback;
        $this->competition_type = Matches_Query_Normaliser::competition_type( $competition_type );
        $this->template         = Matches_Query_Normaliser::template( $template );
    }
}
