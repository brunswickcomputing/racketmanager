<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use Racketmanager\Domain\Club;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Event_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Team_Repository_Interface;
use Racketmanager\Services\Competition\Competition_Season_Service;

/**
 * Builds a real Competition_Season_Service for handler tests, backed by stub repositories.
 *
 * Use inside a PHPUnit TestCase.
 */
trait Creates_Competition_Season_Service {
    /**
     * @param array<int, string> $venue_shortcodes Venue club shortcodes keyed by club id.
     */
    private function competition_season_service( array $venue_shortcodes = [] ): Competition_Season_Service {
        $club_repository = $this->createStub( Club_Repository_Interface::class );
        $club_repository->method( 'find_by_id' )->willReturnCallback(
            static function ( null|int|string $id ) use ( $venue_shortcodes ): ?Club {
                if ( ! isset( $venue_shortcodes[ (int) $id ] ) ) {
                    return null;
                }

                return Club_Test_Factory::create( id: (int) $id, shortcode: $venue_shortcodes[ (int) $id ] );
            }
        );

        return new Competition_Season_Service(
            $this->createStub( League_Team_Repository_Interface::class ),
            $club_repository,
            $this->createStub( Event_Repository_Interface::class )
        );
    }
}
