<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Racketmanager_Match;
use Racketmanager\Repositories\Interfaces\Result_Repository_Interface;
use Racketmanager\Services\Container\Simple_Container;
use ReflectionClass;
use ReflectionMethod;
use stdClass;

class Racketmanager_Match_Result_Delegate_Test extends TestCase {
    private $original_racketmanager;
    private $repository;

    protected function setUp(): void {
        parent::setUp();
        global $racketmanager;
        $this->original_racketmanager = $racketmanager ?? null;
        $this->repository             = $this->createMock( Result_Repository_Interface::class );
        $container                    = new Simple_Container();
        $container->set( 'result_repository', fn() => $this->repository );
        $racketmanager            = new stdClass();
        $racketmanager->container = $container;
        unset( $GLOBALS['wp_stubs_matches'][300] );
    }

    protected function tearDown(): void {
        global $racketmanager;
        $racketmanager = $this->original_racketmanager;
        unset( $GLOBALS['wp_stubs_matches'][300] );
        parent::tearDown();
    }

    private function make_match(): Racketmanager_Match {
        $match = ( new ReflectionClass( Racketmanager_Match::class ) )->newInstanceWithoutConstructor();
        $match->id = 321;
        return $match;
    }

    public function test_update_result_tie_delegates_to_repository(): void {
        $match = $this->make_match();
        $this->prepare_second_leg( $match, 2.0, 1.0, 2.0, 0.0 );

        $this->repository->expects( $this->once() )
            ->method( 'update_result_tie' )
            ->with( 321, 4.0, 1.0, 10, 20 );

        $match->update_result_tie();
        $this->assertEquals( 10, $match->winner_id_tie );
    }

    private function prepare_second_leg( Racketmanager_Match $match, float $home, float $away, float $linked_home, float $linked_away ): void {
        $match->home_team    = '10';
        $match->away_team    = '20';
        $match->leg          = 2;
        $match->linked_match = 300;
        $match->home_points  = $home;
        $match->away_points  = $away;
        $linked              = new stdClass();
        $linked->winner_id   = 10;
        $linked->home_points = $linked_home;
        $linked->away_points = $linked_away;
        $GLOBALS['wp_stubs_matches'][300] = $linked;
    }

    public function test_update_result_tie_draw_uses_minus_one(): void {
        $match = $this->make_match();
        $this->prepare_second_leg( $match, 2.0, 1.0, 1.0, 2.0 );

        $this->repository->expects( $this->once() )
            ->method( 'update_result_tie' )
            ->with( 321, 3.0, 3.0, -1, -1 );

        $match->update_result_tie();
    }

    public function test_update_result_tie_skipped_for_first_leg(): void {
        $match      = $this->make_match();
        $match->leg = 1;
        $this->repository->expects( $this->never() )->method( 'update_result_tie' );
        $match->update_result_tie();
    }

    public function test_delete_results_report_delegates_to_repository(): void {
        $this->repository->expects( $this->once() )
            ->method( 'delete_results_report' )
            ->with( 321 );

        ( new ReflectionMethod( Racketmanager_Match::class, 'delete_results_report' ) )->invoke( $this->make_match() );
    }

    public function test_update_result_database_delegates_to_repository(): void {
        $match = $this->getMockBuilder( Racketmanager_Match::class )
            ->disableOriginalConstructor()
            ->onlyMethods( [ 'set_status_flags' ] )
            ->getMock();
        $match->id          = 321;
        $match->home_points = 3.0;
        $match->away_points = 1.0;
        $match->winner_id   = 10;
        $match->loser_id    = 20;
        $match->custom      = array( 'sets' => array() );
        $match->confirmed   = 'P';
        $match->status      = 0;

        $this->repository->expects( $this->once() )
            ->method( 'update_result' )
            ->with(
                321,
                array(
                    'home_points' => 3.0,
                    'away_points' => 1.0,
                    'winner_id'   => 10,
                    'loser_id'    => 20,
                    'custom'      => array( 'sets' => array() ),
                    'confirmed'   => 'P',
                    'status'      => 0,
                ),
                'home',
                $this->anything()
            );
        $this->repository->expects( $this->never() )->method( 'save_results_report' );

        ( new ReflectionMethod( Racketmanager_Match::class, 'update_result_database' ) )->invoke( $match, 'home' );
        $this->assertNotNull( $match->home_captain ?? null );
    }
}
