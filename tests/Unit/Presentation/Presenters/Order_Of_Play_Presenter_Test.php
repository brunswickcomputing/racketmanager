<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Presentation\Presenters\Order_Of_Play_Presenter;
use Racketmanager\Presentation\View_Models\Order_Of_Play_View_Model;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Team_Service;

class Order_Of_Play_Presenter_Test extends TestCase {

    private $fixture_repository;
    private $competition_service;
    private $league_repository;
    private $team_service;
    private $option_gateway;
    private Order_Of_Play_Presenter $presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->fixture_repository  = $this->createStub( Fixture_Repository_Interface::class );
        $this->competition_service = $this->createStub( Competition_Service::class );
        $this->league_repository   = $this->createStub( League_Repository_Interface::class );
        $this->competition_service->method( 'get_league_repository' )->willReturn( $this->league_repository );
        $this->team_service        = $this->createStub( Team_Service::class );
        $this->option_gateway      = $this->createStub( Option_Gateway_Interface::class );
        $this->option_gateway->method( 'get_option' )->willReturnCallback(
            fn( string $key, mixed $default = false ) => 'time_format' === $key ? 'H:i' : $default
        );

        $this->presenter = new Order_Of_Play_Presenter(
            $this->fixture_repository,
            $this->competition_service,
            $this->team_service,
            $this->option_gateway
        );
    }

    public function test_present_empty_finals_order_returns_empty_view_model_with_col_width_12(): void {
        $vm = $this->presenter->present( [] );

        $this->assertInstanceOf( Order_Of_Play_View_Model::class, $vm );
        $this->assertEmpty( $vm->courts );
        $this->assertEmpty( $vm->times );
        $this->assertSame( 12, $vm->col_width );
        $this->assertFalse( $vm->is_expanded );
        $this->assertEmpty( $vm->match_time );
    }

    public function test_present_with_numeric_teams_and_league(): void {
        $league = $this->createStub( League::class );
        $league->method( 'get_name' )->willReturn( 'Championship League' );
        $this->league_repository->method( 'find_by_id' )->willReturn( $league );

        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'get_league_id' )->willReturn( 10 );
        $fixture->method( 'get_home_team' )->willReturn( '101' );
        $fixture->method( 'get_away_team' )->willReturn( '102' );
        $fixture->method( 'get_date' )->willReturn( '2026-10-14 14:30:00' );
        $fixture->method( 'get_location' )->willReturn( 'Court 1' );
        $fixture->method( 'get_winner_id' )->willReturn( 101 );

        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );

        $team1 = $this->createStub( Team::class );
        $team1->method( 'get_name' )->willReturn( 'Westcliff 1' );
        $team2 = $this->createStub( Team::class );
        $team2->method( 'get_name' )->willReturn( 'Leigh 1' );

        $this->team_service->method( 'get_team_details' )->willReturnCallback(
            fn( int $id ) => match ( $id ) {
                101     => new Team_Details_DTO( $team1 ),
                102     => new Team_Details_DTO( $team2 ),
                default => null,
            }
        );

        $finals_order = [
            [
                'court'   => 'Court 1',
                'matches' => [ '500' ],
            ],
        ];

        $vm = $this->presenter->present( $finals_order );

        $this->assertSame( 12, $vm->col_width );
        $this->assertFalse( $vm->is_expanded );
        $this->assertContains( '14:30', $vm->times );

        $match_vm = $vm->match_time['14:30']['Court 1'];
        $this->assertSame( 500, $match_vm->id );
        $this->assertSame( 'Championship League', $match_vm->league_title );
        $this->assertSame( 'Westcliff 1', $match_vm->home_match_title );
        $this->assertSame( 'Leigh 1', $match_vm->away_match_title );
        $this->assertFalse( $match_vm->is_home_pending );
        $this->assertFalse( $match_vm->is_away_pending );
        $this->assertSame( 101, $match_vm->winner );
    }

    public function test_present_with_placeholder_teams_and_is_home_pending(): void {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'get_league_id' )->willReturn( null );
        $fixture->method( 'get_home_team' )->willReturn( '1_semi_1' );
        $fixture->method( 'get_away_team' )->willReturn( '102' );
        $fixture->method( 'get_date' )->willReturn( '2026-10-14 10:00:00' );

        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );

        $team_placeholder = $this->createStub( Team::class );
        $team_placeholder->method( 'get_name' )->willReturn( 'Winner Semi 1' );
        $team_numeric = $this->createStub( Team::class );
        $team_numeric->method( 'get_name' )->willReturn( 'Confirmed Away' );

        $this->team_service->method( 'derive_team_details' )->willReturn( new Team_Details_DTO( $team_placeholder ) );
        $this->team_service->method( 'get_team_details' )->willReturn( new Team_Details_DTO( $team_numeric ) );

        $finals_order = [
            [
                'court'   => 'Court 1',
                'matches' => [ '601' ],
            ],
        ];

        $vm = $this->presenter->present( $finals_order );
        $match_vm = $vm->match_time['10:00']['Court 1'];

        $this->assertTrue( $match_vm->is_home_pending );
        $this->assertFalse( $match_vm->is_away_pending );
        $this->assertSame( 'Winner Semi 1', $match_vm->home_match_title );
        $this->assertSame( 'Confirmed Away', $match_vm->away_match_title );
    }

    public function test_present_skips_missing_fixtures(): void {
        $this->fixture_repository->method( 'find_by_id' )->willReturn( null );

        $finals_order = [
            [
                'court'   => 'Court 1',
                'matches' => [ '999' ],
            ],
        ];

        $vm = $this->presenter->present( $finals_order );
        $this->assertEmpty( $vm->times );
        $this->assertEmpty( $vm->match_time );
    }

    public function test_present_two_courts_different_times_fills_empty_slots_and_ksorts(): void {
        $fixture1 = $this->createStub( Fixture::class );
        $fixture1->method( 'get_date' )->willReturn( '2026-10-14 10:00:00' );
        $fixture1->method( 'get_home_team' )->willReturn( 'Team 1' );
        $fixture1->method( 'get_away_team' )->willReturn( 'Team 2' );

        $fixture2 = $this->createStub( Fixture::class );
        $fixture2->method( 'get_date' )->willReturn( '2026-10-14 12:00:00' );
        $fixture2->method( 'get_home_team' )->willReturn( 'Team 3' );
        $fixture2->method( 'get_away_team' )->willReturn( 'Team 4' );

        $this->fixture_repository->method( 'find_by_id' )->willReturnCallback(
            fn( int $id ) => match ( $id ) {
                1 => $fixture1,
                2 => $fixture2,
                default => null,
            }
        );

        $finals_order = [
            [
                'court'   => 'Court 1',
                'matches' => [ '1' ],
            ],
            [
                'court'   => 'Court 2',
                'matches' => [ '2' ],
            ],
        ];

        $vm = $this->presenter->present( $finals_order );

        $this->assertSame( [ '10:00', '12:00' ], $vm->times );
        $this->assertSame( 6, $vm->col_width );
        $this->assertFalse( $vm->is_expanded );

        // Check empty slots are filled
        $this->assertArrayHasKey( '12:00', $vm->courts['Court 1'] );
        $this->assertEmpty( $vm->courts['Court 1']['12:00'] );
        $this->assertArrayHasKey( '10:00', $vm->courts['Court 2'] );
        $this->assertEmpty( $vm->courts['Court 2']['10:00'] );
    }

    public function test_present_uses_custom_time_format(): void {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'get_date' )->willReturn( '2026-10-14 14:00:00' );
        $fixture->method( 'get_home_team' )->willReturn( 'A' );
        $fixture->method( 'get_away_team' )->willReturn( 'B' );

        $this->fixture_repository->method( 'find_by_id' )->willReturn( $fixture );

        $finals_order = [
            [
                'court'   => 'Court 1',
                'matches' => [ '1' ],
            ],
        ];

        $vm = $this->presenter->present( $finals_order, 'g:i a' );
        $this->assertContains( '2:00 pm', $vm->times );
    }

    public function test_present_sorts_custom_time_labels_chronologically(): void {
        $ten_am = $this->createStub( Fixture::class );
        $ten_am->method( 'get_date' )->willReturn( '2026-10-14 10:00:00' );
        $ten_am->method( 'get_home_team' )->willReturn( 'Team 1' );
        $ten_am->method( 'get_away_team' )->willReturn( 'Team 2' );

        $nine_am = $this->createStub( Fixture::class );
        $nine_am->method( 'get_date' )->willReturn( '2026-10-14 09:00:00' );
        $nine_am->method( 'get_home_team' )->willReturn( 'Team 3' );
        $nine_am->method( 'get_away_team' )->willReturn( 'Team 4' );

        $this->fixture_repository->method( 'find_by_id' )->willReturnCallback(
            fn( int $id ) => match ( $id ) {
                10     => $ten_am,
                9      => $nine_am,
                default => null,
            }
        );

        $vm = $this->presenter->present(
            [
                [
                    'court'   => 'Court 1',
                    'matches' => [ '10', '9' ],
                ],
            ],
            'g:i a'
        );

        $this->assertSame( [ '9:00 am', '10:00 am' ], $vm->times );
        $this->assertSame( [ '9:00 am', '10:00 am' ], array_keys( $vm->courts['Court 1'] ) );
        $this->assertSame( 9, $vm->courts['Court 1']['9:00 am'][0]->id );
        $this->assertSame( 10, $vm->courts['Court 1']['10:00 am'][0]->id );
    }

    public function test_col_width_and_is_expanded_calculation_for_six_courts(): void {
        $finals_order = [];
        for ( $i = 1; $i <= 6; $i++ ) {
            $finals_order[] = [
                'court'   => 'Court ' . $i,
                'matches' => [],
            ];
        }

        $vm = $this->presenter->present( $finals_order );
        $this->assertSame( 2, $vm->col_width );
        $this->assertTrue( $vm->is_expanded );
    }
}
