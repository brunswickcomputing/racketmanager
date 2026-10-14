<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Persistence;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Infrastructure\Persistence\Legacy_Fixture_Listing_Finder;
use Racketmanager\RacketManager;

class Legacy_Fixture_Listing_Finder_Test extends TestCase {

    public function test_find_by_match_date_passes_typed_arguments_and_filters_non_fixtures(): void {
        $fixture = $this->createStub( Fixture::class );
        $app     = $this->createMock( RacketManager::class );
        $app->expects( $this->once() )
            ->method( 'get_matches' )
            ->with( [ 'match_date' => '2024-06-15', 'competition_type' => 'league' ] )
            ->willReturn( [ null, $fixture, (object) [ 'id' => 1 ] ] );

        $result = ( new Legacy_Fixture_Listing_Finder( $app ) )->find_by_match_date( '2024-06-15', Competition_Type::LEAGUE );

        $this->assertSame( [ $fixture ], $result );
    }

    public function test_find_latest_results_maps_criteria_to_legacy_arguments(): void {
        $app = $this->createMock( RacketManager::class );
        $app->expects( $this->once() )
            ->method( 'get_matches' )
            ->with( [
                'days'             => 7,
                'competition_type' => 'cup',
                'time'             => 'latest',
                'history'          => 7,
                'club'             => 3,
                'competition_id'   => false,
                'age_group'        => 'junior',
            ] )
            ->willReturn( [] );

        $result = ( new Legacy_Fixture_Listing_Finder( $app ) )->find_latest_results( 7, Competition_Type::CUP, 3, null, 'junior' );

        $this->assertSame( [], $result );
    }

    public function test_find_latest_results_zero_days_disables_history_window(): void {
        $app = $this->createMock( RacketManager::class );
        $app->expects( $this->once() )
            ->method( 'get_matches' )
            ->with( $this->callback( static fn( array $args ): bool => false === $args['history'] && false === $args['club'] ) )
            ->willReturn( 0 );

        $this->assertSame( [], ( new Legacy_Fixture_Listing_Finder( $app ) )->find_latest_results( 0, Competition_Type::LEAGUE ) );
    }
}
