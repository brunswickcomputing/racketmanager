<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Controllers\Admin;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Admin\Controllers\Tournament_Setup_Admin_Controller;
use Racketmanager\Admin\View_Models\Tournament_Setup_Page_View_Model;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Domain\DTO\Tournament\Tournament_Details_DTO;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Club\Club;
use stdClass;
use WP_Error;

require_once __DIR__ . '/../../../wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final
class Tournament_Setup_Admin_Controller_Test extends TestCase {

    private $tournament_service;
    private $action_guard;
    private $controller;

    protected function setUp(): void {
        parent::setUp();
        $this->tournament_service = $this->createMock( Tournament_Service::class );
        $this->action_guard = $this->createMock( Action_Guard_Interface::class );
        $this->controller = new Tournament_Setup_Admin_Controller(
            $this->tournament_service,
            $this->action_guard
        );
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    protected function tearDown(): void {
        unset( $_SERVER['REQUEST_METHOD'] );
        parent::tearDown();
    }

    private function create_tournament_details(
        int $tournament_id = 123,
        string $season = '2023',
        array $match_dates = [],
        string $name = 'Test Tournament',
        string $date_end = '2023-12-31'
    ): Tournament_Details_DTO {
        $reflectionTournament = new \ReflectionClass( Tournament::class );
        $tournament = $reflectionTournament->newInstanceWithoutConstructor();
        $tournament->id = $tournament_id;
        $tournament->name = $name;
        $tournament->season = $season;
        $tournament->finals = [];
        $tournament->date_end = $date_end;

        $competition = $this->createMock( Competition::class );
        $competition->method( 'get_season_by_name' )->willReturn( $match_dates ? [ 'match_dates' => $match_dates ] : [] );

        $reflectionClub = new \ReflectionClass( Club::class );
        $club = $reflectionClub->newInstanceWithoutConstructor();

        return new Tournament_Details_DTO( $tournament, $competition, $club );
    }

    public function test_setup_page_get_returns_view_model(): void {
        $tournament_id = 123;
        $query = [ 'tournament' => (string) $tournament_id ];
        $post = [];

        $details = $this->create_tournament_details(
            tournament_id: $tournament_id,
            match_dates: [ 1 => '2023-12-25' ]
        );

        $this->tournament_service->expects( self::once() )
            ->method( 'get_tournament_with_details' )
            ->with( $tournament_id )
            ->willReturn( $details );

        $result = $this->controller->setup_page( $query, $post );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertInstanceOf( Tournament_Setup_Page_View_Model::class, $result['view_model'] );
        self::assertSame( '2023', $result['view_model']->season );
        self::assertSame( [ 1 => '2023-12-25' ], $result['view_model']->match_dates );
    }

    public function test_setup_page_get_with_updated_flag(): void {
        $tournament_id = 123;
        $query = [ 'tournament' => (string) $tournament_id, 'updated' => '1' ];
        $post = [];

        $this->tournament_service->method( 'get_tournament_with_details' )
            ->willReturn( $this->create_tournament_details( $tournament_id ) );

        $result = $this->controller->setup_page( $query, $post );

        self::assertSame( 'Tournament round dates updated', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_setup_page_post_round_dates_success_redirects(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $tournament_id = 123;
        $query = [];
        $post = [
            'tournament_id' => (string) $tournament_id,
            'action' => 'save',
            'season' => '2023',
            'rounds' => [
                0 => [ 'key' => 'final', 'num_matches' => 1, 'round' => 1, 'match_date' => '2023-12-31' ]
            ],
            'racketmanager_nonce' => 'valid_nonce'
        ];

        $this->tournament_service->expects( self::once() )
            ->method( 'set_round_dates_for_tournament' )
            ->willReturn( true );

        $this->tournament_service->method( 'get_tournament_with_details' )
            ->willReturn( $this->create_tournament_details( $tournament_id ) );

        $result = $this->controller->setup_page( $query, $post );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'updated=1', $result['redirect'] );
    }

    public function test_setup_page_post_generate_ratings_success_redirects(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $tournament_id = 123;
        $query = [];
        $post = [
            'tournament_id' => (string) $tournament_id,
            'rank' => 'calculate',
            'racketmanager_nonce' => 'valid_nonce'
        ];

        $this->tournament_service->expects( self::once() )
            ->method( 'calculate_player_team_rating_for_tournament' )
            ->with( $tournament_id )
            ->willReturn( true );

        $this->tournament_service->method( 'get_tournament_with_details' )
            ->willReturn( $this->create_tournament_details( $tournament_id ) );

        $result = $this->controller->setup_page( $query, $post );

        self::assertArrayHasKey( 'redirect', $result );
        self::assertStringContainsString( 'ratings_set=1', $result['redirect'] );
        self::assertSame( 'Tournament ratings set', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_setup_page_get_with_ratings_set_flag(): void {
        $tournament_id = 123;
        $query = [ 'tournament' => (string) $tournament_id, 'ratings_set' => '1' ];
        $post = [];

        $this->tournament_service->method( 'get_tournament_with_details' )
            ->willReturn( $this->create_tournament_details( $tournament_id ) );

        $result = $this->controller->setup_page( $query, $post );

        self::assertSame( 'Tournament ratings set', $result['message'] );
        self::assertFalse( $result['message_type'] );
    }

    public function test_setup_page_get_with_ratings_not_set_flag(): void {
        $tournament_id = 123;
        $query = [ 'tournament' => (string) $tournament_id, 'ratings_set' => '0' ];
        $post = [];

        $this->tournament_service->method( 'get_tournament_with_details' )
            ->willReturn( $this->create_tournament_details( $tournament_id ) );

        $result = $this->controller->setup_page( $query, $post );

        self::assertSame( 'No ratings to set', $result['message'] );
        self::assertSame( 'warning', $result['message_type'] );
    }

    public function test_setup_page_post_round_dates_error_re_renders(): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $tournament_id = 123;
        $query = [];
        $post = [
            'tournament_id' => (string) $tournament_id,
            'action' => 'save',
            'season' => '2023',
            'racketmanager_nonce' => 'valid_nonce'
        ];

        $wp_error = new WP_Error( 'invalid_date', 'Invalid date' );
        $this->tournament_service->expects( self::once() )
            ->method( 'set_round_dates_for_tournament' )
            ->willReturn( $wp_error );

        $this->tournament_service->method( 'get_tournament_with_details' )
            ->willReturn( $this->create_tournament_details( $tournament_id ) );

        $result = $this->controller->setup_page( $query, $post );

        self::assertArrayHasKey( 'view_model', $result );
        self::assertSame( 'Error setting tournament round dates', $result['message'] );
        self::assertTrue( $result['message_type'] );
        self::assertTrue( $result['view_model']->validator->error );
    }

    public function test_setup_page_throws_exception_if_tournament_not_found(): void {
        $tournament_id = 999;
        $query = [ 'tournament' => (string) $tournament_id ];
        $post = [];

        $this->tournament_service->expects( self::once() )
            ->method( 'get_tournament_with_details' )
            ->with( $tournament_id )
            ->willThrowException( new Tournament_Not_Found_Exception( 'Not found' ) );

        $this->expectException( Tournament_Not_Found_Exception::class );
        $this->controller->setup_page( $query, $post );
    }
}
