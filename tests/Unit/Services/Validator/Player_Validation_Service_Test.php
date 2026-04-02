<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Validator;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\Competition\Event;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Validator\Player_Validation_Service;
use Racketmanager\Domain\DTO\Club\Club_Player_DTO;
use Racketmanager\Domain\Results_Checker;
use stdClass;

#[AllowMockObjectsWithoutExpectations]
class Player_Validation_Service_Test extends TestCase {
    private $registration_service;
    private $results_checker_repository;
    private $fixture_repository;
    private $service;

    protected function setUp(): void {
        parent::setUp();
        $this->registration_service = $this->createMock( Registration_Service::class );
        $this->results_checker_repository = $this->createMock( Results_Checker_Repository_Interface::class );
        $this->fixture_repository = $this->createMock( Fixture_Repository_Interface::class );
        $this->service = new Player_Validation_Service(
            $this->registration_service,
            $this->results_checker_repository,
            $this->fixture_repository
        );
    }

    public function test_apply_dummy_players_for_share(): void {
        $dummy_players = [
            'home' => [
                'share' => [
                    'male' => (object)[ 'roster_id' => 101 ],
                    'female' => (object)[ 'roster_id' => 102 ],
                ],
            ],
            'away' => [
                'share' => [
                    'male' => (object)[ 'roster_id' => 201 ],
                    'female' => (object)[ 'roster_id' => 202 ],
                ],
            ],
        ];

        $players = [
            'home' => [ '1' => 0, '2' => 0 ],
            'away' => [ '1' => 0, '2' => 0 ],
        ];

        // Test MD
        $result = $this->service->apply_dummy_players( 'MD', 'share', $players, $dummy_players );
        $this->assertEquals( 101, $result['home']['1'] );
        $this->assertEquals( 101, $result['home']['2'] );
        $this->assertEquals( 201, $result['away']['1'] );
        $this->assertEquals( 201, $result['away']['2'] );

        // Test WD
        $result = $this->service->apply_dummy_players( 'WD', 'share', $players, $dummy_players );
        $this->assertEquals( 102, $result['home']['1'] );
        $this->assertEquals( 202, $result['away']['1'] );

        // Test XD
        $result = $this->service->apply_dummy_players( 'XD', 'share', $players, $dummy_players );
        $this->assertEquals( 101, $result['home']['1'] );
        $this->assertEquals( 102, $result['home']['2'] );
        $this->assertEquals( 201, $result['away']['1'] );
        $this->assertEquals( 202, $result['away']['2'] );
    }

    public function test_apply_dummy_players_for_walkover_player1(): void {
        $dummy_players = [
            'home' => [
                'walkover' => [
                    'male' => (object)[ 'roster_id' => 101 ],
                    'female' => (object)[ 'roster_id' => 102 ],
                ],
            ],
            'away' => [
                'noplayer' => [
                    'male' => (object)[ 'roster_id' => 201 ],
                    'female' => (object)[ 'roster_id' => 202 ],
                ],
            ],
        ];

        $players = [
            'home' => [ '1' => 0, '2' => 0 ],
            'away' => [ '1' => 0, '2' => 0 ],
        ];

        // MD
        $result = $this->service->apply_dummy_players( 'MD', 'walkover_player1', $players, $dummy_players );
        $this->assertEquals( 101, $result['home']['1'] );
        $this->assertEquals( 101, $result['home']['2'] );
        $this->assertEquals( 201, $result['away']['1'] );
        $this->assertEquals( 201, $result['away']['2'] );

        // WD
        $result = $this->service->apply_dummy_players( 'WD', 'walkover_player1', $players, $dummy_players );
        $this->assertEquals( 102, $result['home']['1'] );
        $this->assertEquals( 102, $result['home']['2'] );
        $this->assertEquals( 202, $result['away']['1'] );
        $this->assertEquals( 202, $result['away']['2'] );

        // XD
        $result = $this->service->apply_dummy_players( 'XD', 'walkover_player1', $players, $dummy_players );
        $this->assertEquals( 101, $result['home']['1'] );
        $this->assertEquals( 102, $result['home']['2'] );
        $this->assertEquals( 201, $result['away']['1'] );
        $this->assertEquals( 202, $result['away']['2'] );

        // Partial filling
        $players_partial = [
            'home' => [ '1' => 5, '2' => 0 ],
            'away' => [ '1' => 0, '2' => 0 ],
        ];
        $result = $this->service->apply_dummy_players( 'MD', 'walkover_player1', $players_partial, $dummy_players );
        $this->assertEquals( 5, $result['home']['1'] );
        $this->assertEquals( 101, $result['home']['2'] );
    }

    public function test_apply_dummy_players_for_walkover_player2(): void {
        $dummy_players = [
            'home' => [
                'noplayer' => [
                    'male' => (object)[ 'roster_id' => 101 ],
                    'female' => (object)[ 'roster_id' => 102 ],
                ],
            ],
            'away' => [
                'walkover' => [
                    'male' => (object)[ 'roster_id' => 201 ],
                    'female' => (object)[ 'roster_id' => 202 ],
                ],
            ],
        ];

        $players = [
            'home' => [ '1' => 0, '2' => 0 ],
            'away' => [ '1' => 0, '2' => 0 ],
        ];

        // MD
        $result = $this->service->apply_dummy_players( 'MD', 'walkover_player2', $players, $dummy_players );
        $this->assertEquals( 101, $result['home']['1'] );
        $this->assertEquals( 101, $result['home']['2'] );
        $this->assertEquals( 201, $result['away']['1'] );
        $this->assertEquals( 201, $result['away']['2'] );

        // WD
        $result = $this->service->apply_dummy_players( 'WD', 'walkover_player2', $players, $dummy_players );
        $this->assertEquals( 102, $result['home']['1'] );
        $this->assertEquals( 102, $result['home']['2'] );
        $this->assertEquals( 202, $result['away']['1'] );
        $this->assertEquals( 202, $result['away']['2'] );

        // XD
        $result = $this->service->apply_dummy_players( 'XD', 'walkover_player2', $players, $dummy_players );
        $this->assertEquals( 101, $result['home']['1'] );
        $this->assertEquals( 102, $result['home']['2'] );
        $this->assertEquals( 201, $result['away']['1'] );
        $this->assertEquals( 202, $result['away']['2'] );

        // Partial filling
        $players_partial = [
            'home' => [ '1' => 0, '2' => 0 ],
            'away' => [ '1' => 6, '2' => 0 ],
        ];
        $result = $this->service->apply_dummy_players( 'MD', 'walkover_player2', $players_partial, $dummy_players );
        $this->assertEquals( 6, $result['away']['1'] );
        $this->assertEquals( 201, $result['away']['2'] );
    }

    public function test_apply_dummy_players_unknown_match_type(): void {
        $dummy_players = [
            'home' => [
                'share' => [
                    'unknown' => (object)[ 'roster_id' => 999 ],
                ],
            ],
            'away' => [
                'share' => [
                    'unknown' => (object)[ 'roster_id' => 888 ],
                ],
            ],
        ];

        $players = [
            'home' => [ '1' => 0, '2' => 0 ],
            'away' => [ '1' => 0, '2' => 0 ],
        ];

        $result = $this->service->apply_dummy_players( 'UNKNOWN', 'share', $players, $dummy_players );
        $this->assertEquals( 999, $result['home']['1'] );
        $this->assertEquals( 999, $result['home']['2'] );
        $this->assertEquals( 888, $result['away']['1'] );
        $this->assertEquals( 888, $result['away']['2'] );
    }

    public function test_run_fixture_checks_deletes_old_checks(): void {
        $fixture = $this->createMock( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 123 );
        $league = $this->createMock( League::class );
        
        $this->results_checker_repository->expects( $this->once() )
            ->method( 'delete_by_fixture_id' )
            ->with( 123 );

        $this->service->run_fixture_checks( $fixture, $league, [] );
    }

    public function test_run_rubber_player_checks_wtn_order_violation(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = 1000;
        $fixture_data->away_team = 2000;
        $fixture_data->season = '2026';
        $fixture = new Fixture( $fixture_data );

        $league = $this->createMock( League::class );
        $event = $this->createMock( Event::class );
        $competition = $this->createMock( Competition::class );
        $event->competition = $competition;
        $league->event = $event;
        $league->method( 'get_id' )->willReturn( 456 );

        $player1 = $this->getMockBuilder( Club_Player_DTO::class )
                        ->disableOriginalConstructor()
                        ->getMock();
        $player1->id = 5;
        $player1->wtn = [ 'S' => 25.0 ];
        $player1->registration_id = 500;

        $player2 = $this->getMockBuilder( Club_Player_DTO::class )
                        ->disableOriginalConstructor()
                        ->getMock();
        $player2->id = 6;
        $player2->wtn = [ 'S' => 20.0 ];
        $player2->registration_id = 600;

        $options = [ 'checks' => [ 'wtn_check' => true ] ];
        
        $competition->rules = [ 'wtn_check' => true ];
        $competition->method( 'get_season_by_name' )->willReturn( [] );
        $event->method( 'get_season_by_name' )->willReturn( [] );
        $event->method( 'get_type' )->willReturn( 'LS' ); // League Singles

        // Setup WTN for first rubber (25.0)
        $this->registration_service->expects($this->exactly(2))
            ->method('get_registration')
            ->willReturnMap([
                [5, $player1],
                [6, $player2]
            ]);

        $rubber_update1 = [
            'id' => 10,
            'rubber_number' => 1,
            'players' => [ 'home' => [ '1' => 5 ], 'away' => [] ]
        ];

        $rubber_update2 = [
            'id' => 11,
            'rubber_number' => 2,
            'players' => [ 'home' => [ '1' => 6 ], 'away' => [] ]
        ];

        $this->results_checker_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $this->callback( function ( $check ) {
                return $check->match_id === 123 && 
                       $check->player_id === 6 && 
                       strpos( $check->description, 'Players out of order' ) !== false;
            } ) );

        $this->service->run_fixture_checks( $fixture, $league, [ $rubber_update1, $rubber_update2 ], $options );
    }

    public function test_age_limit_check_violation(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = 1000;
        $fixture_data->season = '2026';
        $fixture = new Fixture( $fixture_data );

        $league = $this->createMock( League::class );
        $event = $this->createMock( Event::class );
        $competition = $this->createMock( Competition::class );
        $event->competition = $competition;
        $league->event = $event;
        $event->age_limit = '45'; // Over 45s
        $event->age_offset = '0';
        $competition->rules = [ 'ageLimitCheck' => true ];
        $options = [ 'checks' => [ 'ageLimitCheck' => true ] ];

        $player = (object)[
            'id' => 5,
            'age' => 40,
            'gender' => 'M',
            'year_of_birth' => 1986,
            'wtn' => [ 'S' => 20.0 ]
        ];
        $rubber = [
            'id' => 10,
            'players' => [ 'home' => [ '1' => $player ], 'away' => [] ]
        ];

        // We expect a save call because 40 < 45
        $this->results_checker_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $this->callback( function ( $check ) {
                return $check->player_id === 5 && strpos( $check->description, 'age' ) !== false;
            } ) );

        $this->service->run_fixture_checks( $fixture, $league, [ $rubber ], $options );
    }

    public function test_same_day_play_check(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = 1000;
        $fixture_data->season = '2026';
        $fixture_data->match_day = 5;
        $fixture = new Fixture( $fixture_data );

        $league = $this->createMock( League::class );
        $league->method('get_id')->willReturn(456);
        $event = $this->createMock( Event::class );
        $competition = $this->createMock( Competition::class );
        $event->competition = $competition;
        $league->event = $event;

        $player = (object)[
            'id' => 5,
            'registration_id' => 500,
            'wtn' => [ 'S' => 20.0 ]
        ];
        $rubber = [
            'id' => 10,
            'players' => [ 'home' => [ '1' => $player ], 'away' => [] ]
        ];

        $this->fixture_repository->method( 'count_player_matches_on_same_day' )
            ->with( '2026', 5, 456, 500 )
            ->willReturn( 1 );

        $this->results_checker_repository->expects( $this->once() )
            ->method( 'save' )
            ->with( $this->callback( function ( $check ) {
                return $check->player_id === 5 && strpos( $check->description, 'same match day' ) !== false;
            } ) );

        $this->service->run_fixture_checks( $fixture, $league, [ $rubber ], [] );
    }

    public function test_result_timeout_check(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = 1000;
        $fixture_data->date = '2026-03-20 10:00:00';
        $fixture_data->date_result_entered = '2026-03-22 10:00:00'; // 48 hours later
        $fixture = new Fixture( $fixture_data );

        $league = $this->createMock( League::class );
        $event = $this->createMock( Event::class );
        $competition = $this->createMock( Competition::class );
        $competition->type = 'league';
        $competition->rules = [ 'resultTimeout' => true ];
        $event->competition = $competition;
        $league->event = $event;

        $options = [
            'league' => [ 'resultTimeout' => 24 ] // 24 hours timeout
        ];

        $this->results_checker_repository->expects( $this->atLeastOnce() )
            ->method( 'save' )
            ->with( $this->callback( function ( $check ) {
                return $check->match_id === 123 && 
                       strpos( $check->description, 'Result entered' ) !== false;
            } ) );

        $this->service->run_fixture_checks( $fixture, $league, [], $options );
    }
}
