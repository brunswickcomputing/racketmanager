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
     */
    public function __construct( array $data ) {
        // 1. Primary Identity & Contact
        $this->tournament_id = absint( $data['tournament_id'] ?? 0 );
        $this->player_id     = absint( $data['playerId'] ?? 0 );
        $this->club_id       = absint( $data['clubId'] ?? 0 );
        $this->season        = sanitize_text_field( $data['season'] ?? '' );
        $this->email         = sanitize_email( $data['contactemail'] ?? '' );
        $this->phone         = sanitize_text_field( $data['contactno'] ?? '' );
        $this->lta_number    = sanitize_text_field( $data['btm'] ?? '' );

        // 2. Financials
        $this->competition_fee = (float) ( $data['competitionFee'] ?? 0.00 );
        $this->total_cost      = (float) ( $data['priceCostTotal'] ?? 0.00 );
        $this->paid_amt        = (float) ( $data['pricePaidTotal'] ?? 0.00 );
        $this->comments        = sanitize_textarea_field( $data['commentDetails'] ?? '' );
        $this->acceptance      = isset( $data['acceptance'] );

        // 3. Selection State
        $all_possible       = $this->explode_to_ints( $data['tournamentEvents'] ?? '' );
        $selected_event_ids = array_map( 'absint', (array) ( $data['event'] ?? [] ) );

        $this->missed_event_ids = array_values( array_diff( $all_possible, $selected_event_ids ) );

        // 4. Process Selected Entries
        $this->entries = $this->parse_entries( $data, $selected_event_ids );
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
}
