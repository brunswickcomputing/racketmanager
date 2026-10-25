<?php
/**
 * Tournament_Entry_Request_DTO API: Tournament_Entry_Request_DTO class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Domain/DTO
 */

namespace Racketmanager\Domain\DTO\Tournament;

use stdClass;

/**
 * Class to implement the Tournament Entry Request Data Transfer Object
 */
class Tournament_Entry_Request_DTO {
    public int $tournament_id;
    public int $player_id;
    public int $club_id;
    public string $season;
    public string $email;
    public string $phone;
    public string $lta_number;
    public float $competition_fee;
    public float $paid_amt;
    public float $total_cost;
    public string $comments;
    public bool $acceptance;

    /** @var array<int, \stdClass> Map of [event_id] => event_details */
    public array $entries;

    /** @var int[] Events available but not selected */
    public array $missed_event_ids;

    /**
     * Tournament_Entry_Request_DTO constructor.
     *
     * @param array $data
     * @param int $tournament_id
     * @param int $player_id
     * @param int $club_id
     * @param string $season
     * @param string $email
     * @param string $phone
     * @param string $lta_number
     * @param float $competition_fee
     * @param float $paid_amt
     * @param float $total_cost
     * @param string $comments
     * @param bool $acceptance
     * @param array $entries
     * @param array $missed_event_ids
     */
    public function __construct(
        array $data = [],
        int $tournament_id = 0,
        int $player_id = 0,
        int $club_id = 0,
        string $season = '',
        string $email = '',
        string $phone = '',
        string $lta_number = '',
        float $competition_fee = 0.0,
        float $paid_amt = 0.0,
        float $total_cost = 0.0,
        string $comments = '',
        bool $acceptance = false,
        array $entries = [],
        array $missed_event_ids = []
    ) {
        if ( ! empty( $data ) ) {
            // 1. Primary Identity & Contact
            // Note: Snake-first precedence (tournament_id before tournamentId) is deliberately preserved
            // because existing adapter tests specifically test conflicting tournament_id and tournamentId.
            // Non-empty checks ensure blank aliases (such as empty strings from serialized inputs) do not shadow valid values.
            $this->tournament_id = absint( self::parse_int_fallback( $data['tournament_id'] ?? null, $data['tournamentId'] ?? null, $data['tournament'] ?? null ) ?? $tournament_id );
            $this->player_id     = absint( self::parse_int_fallback( $data['playerId'] ?? null, $data['player_id'] ?? null ) ?? $player_id );
            $this->club_id       = absint( self::parse_int_fallback( $data['clubId'] ?? null, $data['club_id'] ?? null ) ?? $club_id );
            $this->season        = sanitize_text_field( $data['season'] ?? $season );
            $this->email         = sanitize_email( $data['contactemail'] ?? ( $data['email'] ?? $email ) );
            $this->phone         = sanitize_text_field( $data['contactno'] ?? ( $data['phone'] ?? $phone ) );
            $this->lta_number    = sanitize_text_field( $data['btm'] ?? ( $data['lta_number'] ?? $lta_number ) );

            // 2. Financials
            $this->competition_fee = (float) ( $data['competitionFee'] ?? ( $data['competition_fee'] ?? $competition_fee ) );
            $this->total_cost      = (float) ( $data['priceCostTotal'] ?? ( $data['total_cost'] ?? ( $data['totalCost'] ?? $total_cost ) ) );
            $this->paid_amt        = (float) ( $data['pricePaidTotal'] ?? ( $data['paid_amt'] ?? ( $data['paidAmt'] ?? $paid_amt ) ) );
            $this->comments        = sanitize_textarea_field( $data['commentDetails'] ?? ( $data['comments'] ?? $comments ) );
            $this->acceptance      = isset( $data['acceptance'] ) ? (bool) $data['acceptance'] : $acceptance;

            // 3. Selection State
            $all_possible       = $this->explode_to_ints( $data['tournamentEvents'] ?? '' );
            $selected_event_ids = array_map( 'absint', (array) ( $data['event'] ?? [] ) );

            $this->missed_event_ids = ! empty( $all_possible ) ? array_values( array_diff( $all_possible, $selected_event_ids ) ) : $missed_event_ids;

            // 4. Process Selected Entries
            $this->entries = ! empty( $selected_event_ids ) ? $this->parse_entries( $data, $selected_event_ids ) : $entries;
        } else {
            $this->tournament_id   = $tournament_id;
            $this->player_id       = $player_id;
            $this->club_id         = $club_id;
            $this->season          = $season;
            $this->email           = $email;
            $this->phone           = $phone;
            $this->lta_number      = $lta_number;
            $this->competition_fee = $competition_fee;
            $this->paid_amt        = $paid_amt;
            $this->total_cost      = $total_cost;
            $this->comments        = $comments;
            $this->acceptance      = $acceptance;
            $this->entries          = $entries;
            $this->missed_event_ids = $missed_event_ids;
        }
    }

    private function explode_to_ints( string $csv ): array {
        return array_filter( array_map( 'absint', explode( ',', $csv ) ) );
    }

    private function parse_entries( array $data, array $selected_ids ): array {
        $items = [];

        foreach ( $selected_ids as $event_id ) {
            $entry      = new stdClass();
            $entry->fee = (float) ( $data['eventFee'][ $event_id ] ?? 0.00 );

            // Partner Logic (Optional for singles, required for doubles)
            $entry->partner_name = sanitize_text_field( $data['partner'][ $event_id ] ?? '' );
            $entry->partner_id   = absint( $data['partnerId'][ $event_id ] ?? 0 );

            $items[ $event_id ] = $entry;
        }

        return $items;
    }

    /**
     * Return the integer value of the first non-null and non-empty string argument.
     *
     * @param mixed ...$values
     * @return int|null
     */
    private static function parse_int_fallback( mixed ...$values ): ?int {
        foreach ( $values as $value ) {
            if ( null !== $value && '' !== $value ) {
                return intval( $value );
            }
        }

        return null;
    }
}
