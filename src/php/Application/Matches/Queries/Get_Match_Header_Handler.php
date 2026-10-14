<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Matches\Queries;

use Racketmanager\Application\Matches\DTOs\Match_Header_Result;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use function __;

/**
 * Handles the [match-header] query.
 */
class Get_Match_Header_Handler {
    public function __construct( private readonly Fixture_Detail_Service $fixture_detail_service ) {}

    public function handle( Get_Match_Header_Query $query ): Match_Header_Result {
        if ( 0 === $query->match_id ) {
            return Match_Header_Result::error( __( 'Match id not found', 'racketmanager' ) );
        }

        $details = $this->fixture_detail_service->get_fixture_with_details( $query->match_id );
        if ( ! $details ) {
            return Match_Header_Result::error( __( 'Match not found', 'racketmanager' ) );
        }

        return new Match_Header_Result(
            details: $details,
            edit_mode: $query->edit,
            template: $query->template
        );
    }
}
