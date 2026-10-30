<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Season\Season;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Season_Repository;
use Racketmanager\Services\Season_Service;
use WP_Error;

require_once __DIR__ . '/../../wp-stubs.php';

final class Season_Service_Test extends TestCase {

    public function test_empty_season_error_has_field_code_and_message(): void {
        $previous_plugin = $GLOBALS['racketmanager'] ?? null;
        $GLOBALS['racketmanager'] = $this->createStub( RacketManager::class );

        try {
            $service = new Season_Service(
                $GLOBALS['racketmanager'],
                $this->createStub( Season_Repository::class )
            );

            $result = $service->create_season( null );

            self::assertInstanceOf( WP_Error::class, $result );
            self::assertSame( 'seasonName', $result->get_error_code() );
            self::assertSame( array( 'Season must be specified' ), $result->get_error_messages() );
        } finally {
            if ( null === $previous_plugin ) {
                unset( $GLOBALS['racketmanager'] );
            } else {
                $GLOBALS['racketmanager'] = $previous_plugin;
            }
        }
    }

    public function test_create_season_saves_season_built_by_factory_with_name(): void {
        $previous_plugin = $GLOBALS['racketmanager'] ?? null;
        $GLOBALS['racketmanager'] = $this->createStub( RacketManager::class );

        try {
            $repository = $this->createMock( Season_Repository::class );
            $repository->method( 'find_by_id' )->willReturn( null );
            $repository->expects( self::once() )
                ->method( 'save' )
                ->with( self::callback( static fn( $season ): bool => $season instanceof Season && '2031' === $season->get_name() && null === $season->get_id() ) )
                ->willReturn( true );

            $service = new Season_Service( $GLOBALS['racketmanager'], $repository );

            self::assertTrue( $service->create_season( '2031' ) );
        } finally {
            if ( null === $previous_plugin ) {
                unset( $GLOBALS['racketmanager'] );
            } else {
                $GLOBALS['racketmanager'] = $previous_plugin;
            }
        }
    }
}