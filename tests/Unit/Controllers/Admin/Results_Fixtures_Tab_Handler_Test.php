<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\Controllers\Results_Fixtures_Tab_Handler;
use Racketmanager\Admin\Presenters\Admin_Date_Presenter;
use Racketmanager\Admin\Presenters\Admin_Results_Match_Presenter;
use Racketmanager\Admin\View_Models\Admin_Results_Match_View_Model;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use ReflectionMethod;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Results_Fixtures_Tab_Handler_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Fixture_Repository_Interface $fixture_repository;
    private League_Repository_Interface $league_repository;
    private Admin_Results_Match_Presenter $match_presenter;
    private Option_Service $option_service;
    private Results_Fixtures_Tab_Handler $handler;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();

        $this->fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
        $this->league_repository  = $this->createMock( League_Repository_Interface::class );
        $this->match_presenter    = new Admin_Results_Match_Presenter(
            new Admin_Date_Presenter(),
            $this->league_repository
        );
        $this->option_service     = $this->createMock( Option_Service::class );

        $this->handler = new Results_Fixtures_Tab_Handler(
            $this->fixture_repository,
            $this->match_presenter,
            $this->option_service
        );
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_get_tab_data_queries_all_six_tabs_with_grouped_options(): void {
        $this->option_service->method( 'get_options' )
            ->willReturnCallback(
                function ( string|false $group = false ) {
                    if ( 'league' === $group ) {
                        return array( 'resultPending' => '3' );
                    }
                    if ( 'tournament' === $group ) {
                        return array( 'resultPending' => '5' );
                    }
                    return array();
                }
            );

        $sample_match = (object) array(
            'id'          => 1,
            'league_id'   => 10,
            'date'        => '2025-05-01',
            'match_title' => 'Sample Match',
            'link'        => 'https://example.test/match/1/',
        );

        $queries = array();
        $this->fixture_repository->method( 'find_all' )
            ->willReturnCallback(
                function ( array $args ) use ( &$queries, $sample_match ) {
                    $queries[] = $args;
                    return array( $sample_match );
                }
            );

        $data = $this->handler->get_tab_data();

        self::assertCount( 6, $queries );

        // 1. Pending league matches.
        self::assertSame( 'outstanding', $queries[0]['time'] );
        self::assertSame( 'league', $queries[0]['competition_type'] );
        self::assertSame( '3', $queries[0]['resultPending'] );

        // 2. Awaiting confirmation matches.
        self::assertSame( 'P', $queries[1]['status'] );
        self::assertArrayNotHasKey( 'resultPending', $queries[1] );

        // 3. Challenged results matches.
        self::assertSame( 'C', $queries[2]['status'] );
        self::assertArrayNotHasKey( 'resultPending', $queries[2] );

        // 4. Approved results matches.
        self::assertSame( 'A', $queries[3]['status'] );
        self::assertArrayNotHasKey( 'resultPending', $queries[3] );

        // 5. Postponed matches.
        self::assertSame( 'D', $queries[4]['status'] );
        self::assertSame( '3', $queries[4]['resultPending'] );

        // 6. Tournament matches.
        self::assertSame( 'outstanding', $queries[5]['time'] );
        self::assertSame( 'tournament', $queries[5]['competition_type'] );
        self::assertSame( '5', $queries[5]['resultPending'] );

        self::assertCount( 1, $data['pending_results_matches'] );
        self::assertInstanceOf( Admin_Results_Match_View_Model::class, $data['pending_results_matches'][0] );
        self::assertSame( 'Sample Match', $data['pending_results_matches'][0]->match_title );

        self::assertCount( 1, $data['awaiting_confirmation_matches'] );
        self::assertCount( 1, $data['challenged_results_matches'] );
        self::assertCount( 1, $data['approved_results_matches'] );
        self::assertCount( 1, $data['postponed_matches'] );
        self::assertCount( 1, $data['tournament_results_matches'] );
    }

    public function test_get_tab_data_with_missing_options_omits_result_pending_and_hydrates_numeric_ids(): void {
        $this->option_service->method( 'get_options' )
            ->willReturn( array() );

        $hydrated_match              = new Fixture();
        $hydrated_match->id          = 42;
        $hydrated_match->match_title = 'Hydrated Match';
        $hydrated_match->link        = 'https://example.test/match/42/';

        $queries = array();
        $this->fixture_repository->method( 'find_all' )
            ->willReturnCallback(
                function ( array $args ) use ( &$queries ) {
                    $queries[] = $args;
                    if ( ( $args['status'] ?? '' ) === 'P' ) {
                        return array( 42 );
                    }
                    return array();
                }
            );

        $this->fixture_repository->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 42 )
            ->willReturn( $hydrated_match );

        $data = $this->handler->get_tab_data();

        self::assertCount( 6, $queries );
        self::assertArrayNotHasKey( 'resultPending', $queries[0] );
        self::assertArrayNotHasKey( 'resultPending', $queries[1] );
        self::assertArrayNotHasKey( 'resultPending', $queries[2] );
        self::assertArrayNotHasKey( 'resultPending', $queries[3] );
        self::assertArrayNotHasKey( 'resultPending', $queries[4] );
        self::assertArrayNotHasKey( 'resultPending', $queries[5] );

        self::assertCount( 1, $data['awaiting_confirmation_matches'] );
        self::assertInstanceOf( Admin_Results_Match_View_Model::class, $data['awaiting_confirmation_matches'][0] );
        self::assertSame( 'Hydrated Match', $data['awaiting_confirmation_matches'][0]->match_title );
    }

    public function test_get_tab_data_hydrates_partial_objects_with_id(): void {
        $this->option_service->method( 'get_options' )
            ->willReturn( array() );

        $hydrated_match              = new Fixture();
        $hydrated_match->id          = 77;
        $hydrated_match->match_title = 'Partially Hydrated Match';
        $hydrated_match->link        = 'https://example.test/match/77/';

        $this->fixture_repository->method( 'find_all' )
            ->willReturnCallback(
                function ( array $args ) {
                    if ( ( $args['status'] ?? '' ) === 'A' ) {
                        return array( (object) array( 'id' => 77 ) );
                    }
                    return array();
                }
            );

        $this->fixture_repository->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 77 )
            ->willReturn( $hydrated_match );

        $data = $this->handler->get_tab_data();

        self::assertCount( 1, $data['approved_results_matches'] );
        self::assertSame( 'Partially Hydrated Match', $data['approved_results_matches'][0]->match_title );
    }

    public function test_hydrate_match_resolves_object_with_id_and_fetches_from_repository(): void {
        $hydrated_match              = new Fixture();
        $hydrated_match->id          = 15;
        $hydrated_match->match_title = 'Fixture 15';

        $this->fixture_repository->expects( self::once() )
            ->method( 'find_by_id' )
            ->with( 15 )
            ->willReturn( $hydrated_match );

        $method = new ReflectionMethod( $this->handler, 'hydrate_match' );
        $result = $method->invoke( $this->handler, (object) array( 'id' => 15 ) );

        self::assertSame( $hydrated_match, $result );
    }

    public function test_hydrate_match_resolves_numeric_id_and_numeric_string(): void {
        $hydrated_match     = new Fixture();
        $hydrated_match->id = 25;

        $this->fixture_repository->expects( self::exactly( 2 ) )
            ->method( 'find_by_id' )
            ->willReturnCallback(
                function ( int $id ) use ( $hydrated_match ) {
                    return 25 === $id ? $hydrated_match : null;
                }
            );

        $method = new ReflectionMethod( $this->handler, 'hydrate_match' );

        $result_int    = $method->invoke( $this->handler, 25 );
        $result_string = $method->invoke( $this->handler, '25' );

        self::assertSame( $hydrated_match, $result_int );
        self::assertSame( $hydrated_match, $result_string );
    }

    public function test_hydrate_match_bypasses_repository_for_pre_hydrated_objects(): void {
        $this->fixture_repository->expects( self::never() )
            ->method( 'find_by_id' );

        $method = new ReflectionMethod( $this->handler, 'hydrate_match' );

        $match_with_link          = (object) array(
            'id'   => 10,
            'link' => 'https://example.test/match/10/',
        );
        $match_with_match_title   = (object) array(
            'id'          => 11,
            'match_title' => 'Title',
        );
        $match_with_fixture_title = (object) array(
            'id'            => 12,
            'fixture_title' => 'Fixture Title',
        );

        self::assertSame( $match_with_link, $method->invoke( $this->handler, $match_with_link ) );
        self::assertSame( $match_with_match_title, $method->invoke( $this->handler, $match_with_match_title ) );
        self::assertSame( $match_with_fixture_title, $method->invoke( $this->handler, $match_with_fixture_title ) );
    }

    public function test_hydrate_match_returns_fallback_for_non_numeric_and_unhydrated_inputs(): void {
        $this->fixture_repository->expects( self::never() )
            ->method( 'find_by_id' );

        $method = new ReflectionMethod( $this->handler, 'hydrate_match' );

        $unhydrated_no_id = (object) array( 'name' => 'no_id' );
        $object_zero_id   = (object) array( 'id' => 0 );

        self::assertSame( $unhydrated_no_id, $method->invoke( $this->handler, $unhydrated_no_id ) );
        self::assertSame( $object_zero_id, $method->invoke( $this->handler, $object_zero_id ) );
        self::assertNull( $method->invoke( $this->handler, null ) );
        self::assertNull( $method->invoke( $this->handler, false ) );
        self::assertNull( $method->invoke( $this->handler, 'invalid-non-numeric' ) );
    }

    public function test_hydrate_match_handles_failed_repository_lookup(): void {
        $this->fixture_repository->expects( self::exactly( 2 ) )
            ->method( 'find_by_id' )
            ->with( 99 )
            ->willReturn( null );

        $method = new ReflectionMethod( $this->handler, 'hydrate_match' );

        $match_obj = (object) array( 'id' => 99 );
        self::assertSame( $match_obj, $method->invoke( $this->handler, $match_obj ) );
        self::assertNull( $method->invoke( $this->handler, 99 ) );
    }
}
