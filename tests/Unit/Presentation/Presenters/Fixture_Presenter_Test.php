<?php

namespace Racketmanager\Tests\Unit\Presentation\Presenters;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Fixture\DTOs\Fixture_Date_Update_Read_Model;
use Racketmanager\Application\Fixture\DTOs\Fixture_Result_Read_Model;
use Racketmanager\Application\Fixture\DTOs\Fixture_Switch_Teams_Read_Model;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Date_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Switch_Teams_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response;
use Racketmanager\Infrastructure\Wordpress\Request\Fixture\Fixture_Option_Request;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Presentation\Presenters\Fixture_Presenter;
use Racketmanager\Tests\Helpers\Creates_Team_Match_Row_Presenter;
use ReflectionClass;

class Fixture_Presenter_Test extends TestCase {
    use Creates_Team_Match_Row_Presenter;

    private Fixture_Presenter $presenter;
    private Fixture_Link_Service $link_service;
    private Option_Gateway_Interface $option_gateway;

    protected function setUp(): void {
        parent::setUp();
        $this->link_service   = $this->createStub( Fixture_Link_Service::class );
        $this->option_gateway = $this->createStub( Option_Gateway_Interface::class );
        $this->presenter      = new Fixture_Presenter( $this->link_service, $this->option_gateway, team_match_row_presenter: $this->create_team_match_row_presenter() );
    }

    public function test_map_to_result_read_model(): void {
        $fixture = $this->createStub(Fixture::class);
        $fixture->method('get_home_points')->willReturn('5');
        $fixture->method('get_away_points')->willReturn('3');
        $fixture->method('get_winner_id')->willReturn(10);

        $response = $this->createStub(Fixture_Update_Response::class);
        // We'll let get_update_message return "Result saved" by default

        $request = new Fixture_Result_Update_Request(
            fixture_id: 1,
            sets: [1 => ['home' => 6, 'away' => 2]]
        );

        $read_model = $this->presenter->map_to_result_read_model($fixture, $response, $request);

        $this->assertInstanceOf(Fixture_Result_Read_Model::class, $read_model);
        $this->assertEquals('Result saved', $read_model->msg);
        $this->assertEquals(5, $read_model->home_points);
        $this->assertEquals(3, $read_model->away_points);
        $this->assertEquals(10, $read_model->winner_id);
        $this->assertEquals($request->sets, $read_model->sets);
    }

    #[DataProvider('resolve_match_status_provider')]
    public function test_resolve_match_status(bool $is_walkover, ?string $walkover, bool $is_retired, ?string $retired, bool $is_shared, ?string $expected): void {
        $fixture = $this->createStub(Fixture::class);
        $fixture->method('is_walkover')->willReturn($is_walkover);
        $fixture->method('get_walkover')->willReturn($walkover);
        $fixture->method('is_retired')->willReturn($is_retired);
        $fixture->method('get_retired')->willReturn($retired);
        $fixture->method('is_shared')->willReturn($is_shared);

        $reflection = new ReflectionClass(Fixture_Presenter::class);
        $method = $reflection->getMethod('resolve_match_status');
        $method->setAccessible(true);

        $result = $method->invoke($this->presenter, $fixture);
        $this->assertEquals($expected, $result);
    }

    public static function resolve_match_status_provider(): array {
        return [
            'walkover home' => [true, 'home', false, null, false, 'walkover_player1'],
            'walkover away' => [true, 'away', false, null, false, 'walkover_player2'],
            'retired home'  => [false, null, true, 'home', false, 'retired_player1'],
            'retired away'  => [false, null, true, 'away', false, 'retired_player2'],
            'shared'        => [false, null, false, null, true, 'share'],
            'none'          => [false, null, false, null, false, null],
        ];
    }

    public function test_map_to_match_option_vars(): void {
        $fixture     = $this->createStub( Fixture::class );
        $league      = $this->createStub( \Racketmanager\Domain\Competition\League::class );
        $event       = $this->createStub( \Racketmanager\Domain\Competition\Event::class );
        $competition = $this->createStub( \Racketmanager\Domain\Competition\Competition::class );

        $dto = new Fixture_Details_DTO(
            fixture: $fixture,
            league: $league,
            event: $event,
            competition: $competition
        );

        $request = new Fixture_Option_Request(
            match_id: 123,
            modal: 'test-modal',
            option: 'test-option'
        );

        $vars = $this->presenter->map_to_fixture_option_vars(
            $dto,
            $request,
            'Test Title',
            'Test Button',
            'testAction'
        );

        $this->assertIsArray($vars);
        $this->assertSame($dto, $vars['dto']);
        $this->assertSame($fixture, $vars['match']);
        $this->assertEquals('Test Title', $vars['title']);
        $this->assertEquals('test-modal', $vars['modal']);
        $this->assertEquals('test-option', $vars['option']);
        $this->assertEquals('testAction', $vars['action']);
        $this->assertEquals('Test Button', $vars['button']);
        $this->assertArrayHasKey('date_input_type', $vars);
        $this->assertArrayHasKey('date_input', $vars);
    }

    public function test_map_to_match_card_vars_with_valid_date(): void {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'get_date' )->willReturn( '2026-10-18 19:30:00' );
        $league = $this->createStub( \Racketmanager\Domain\Competition\League::class );
        $event  = $this->createStub( \Racketmanager\Domain\Competition\Event::class );
        $comp   = $this->createStub( \Racketmanager\Domain\Competition\Competition::class );

        $dto  = new Fixture_Details_DTO( $fixture, $league, $event, $comp );
        $vars = $this->presenter->map_to_match_card_vars( $dto, $fixture, '<sponsor>' );

        $this->assertSame( '2026-10-18', $vars['date_display'] );
        $this->assertSame( $fixture, $vars['match'] );
        $this->assertSame( '<sponsor>', $vars['sponsor_html'] );
        $this->assertSame( $dto, $vars['dto'] );
    }

    public function test_map_to_match_card_vars_with_null_and_zero_date(): void {
        $fixture_null = $this->createStub( Fixture::class );
        $fixture_null->method( 'get_date' )->willReturn( null );

        $dto_null  = new Fixture_Details_DTO( $fixture_null, $this->createStub( \Racketmanager\Domain\Competition\League::class ), $this->createStub( \Racketmanager\Domain\Competition\Event::class ), $this->createStub( \Racketmanager\Domain\Competition\Competition::class ) );
        $vars_null = $this->presenter->map_to_match_card_vars( $dto_null );
        $this->assertSame( '', $vars_null['date_display'] );

        $fixture_zero = $this->createStub( Fixture::class );
        $fixture_zero->method( 'get_date' )->willReturn( '0000-00-00 00:00:00' );
        $dto_zero  = new Fixture_Details_DTO( $fixture_zero, $this->createStub( \Racketmanager\Domain\Competition\League::class ), $this->createStub( \Racketmanager\Domain\Competition\Event::class ), $this->createStub( \Racketmanager\Domain\Competition\Competition::class ) );
        $vars_zero = $this->presenter->map_to_match_card_vars( $dto_zero );
        $this->assertSame( '', $vars_zero['date_display'] );
    }

    public function test_map_to_fixture_option_vars_with_and_without_start_time(): void {
        $fixture_no_time = $this->createStub( Fixture::class );
        $fixture_no_time->method( 'get_date' )->willReturn( '2026-10-18 00:00:00' );
        $fixture_no_time->method( 'get_start_time' )->willReturn( null );

        $dto_no_time  = new Fixture_Details_DTO( $fixture_no_time, $this->createStub( \Racketmanager\Domain\Competition\League::class ), $this->createStub( \Racketmanager\Domain\Competition\Event::class ), $this->createStub( \Racketmanager\Domain\Competition\Competition::class ) );
        $request      = new Fixture_Option_Request( 1, 'modal', 'schedule_match' );
        $vars_no_time = $this->presenter->map_to_fixture_option_vars( $dto_no_time, $request, 'Title', 'Button', 'action' );

        $this->assertSame( 'date', $vars_no_time['date_input_type'] );
        $this->assertSame( '2026-10-18', $vars_no_time['date_input'] );

        $fixture_time = $this->createStub( Fixture::class );
        $fixture_time->method( 'get_date' )->willReturn( '2026-10-18 19:30:00' );
        $fixture_time->method( 'get_start_time' )->willReturn( '19:30' );

        $dto_time  = new Fixture_Details_DTO( $fixture_time, $this->createStub( \Racketmanager\Domain\Competition\League::class ), $this->createStub( \Racketmanager\Domain\Competition\Event::class ), $this->createStub( \Racketmanager\Domain\Competition\Competition::class ) );
        $vars_time = $this->presenter->map_to_fixture_option_vars( $dto_time, $request, 'Title', 'Button', 'action' );

        $this->assertSame( 'datetime-local', $vars_time['date_input_type'] );
        $this->assertSame( '2026-10-18 19:30:00', $vars_time['date_input'] );

        $fixture_null = $this->createStub( Fixture::class );
        $fixture_null->method( 'get_date' )->willReturn( null );
        $dto_null  = new Fixture_Details_DTO( $fixture_null, $this->createStub( \Racketmanager\Domain\Competition\League::class ), $this->createStub( \Racketmanager\Domain\Competition\Event::class ), $this->createStub( \Racketmanager\Domain\Competition\Competition::class ) );
        $vars_null = $this->presenter->map_to_fixture_option_vars( $dto_null, $request, 'Title', 'Button', 'action' );

        $this->assertSame( 'date', $vars_null['date_input_type'] );
        $this->assertSame( '', $vars_null['date_input'] );
    }

    public function test_map_to_date_update_read_model(): void {
        $request = new Fixture_Date_Update_Request(
            match_id: 123,
            schedule_date: '2023-10-10',
            modal: 'test-modal'
        );

        $read_model = $this->presenter->map_to_date_update_read_model($request, 'Tue 10 Oct');

        $this->assertInstanceOf(Fixture_Date_Update_Read_Model::class, $read_model);
        $this->assertEquals('Match schedule updated', $read_model->msg);
        $this->assertEquals(123, $read_model->match_id);
        $this->assertEquals('2023-10-10', $read_model->schedule_date);
        $this->assertEquals('Tue 10 Oct', $read_model->schedule_date_formated);
        $this->assertEquals('test-modal', $read_model->modal);
    }

    public function test_map_to_switch_teams_read_model(): void {
        $fixture = $this->createStub(Fixture::class);
        $fixture->method('get_id')->willReturn(123);
        
        $league      = $this->createStub( \Racketmanager\Domain\Competition\League::class );
        $event       = $this->createStub( \Racketmanager\Domain\Competition\Event::class );
        $competition = $this->createStub( \Racketmanager\Domain\Competition\Competition::class );
        
        $details = new Fixture_Details_DTO(
            fixture: $fixture,
            league: $league,
            event: $event,
            competition: $competition
        );

        $this->link_service->method('get_fixture_link')->willReturn('https://example.com/match/123');

        $request = new Fixture_Switch_Teams_Request(
            match_id: 123,
            modal: 'test-modal'
        );

        $read_model = $this->presenter->map_to_switch_teams_read_model($fixture, $request, $details);

        $this->assertInstanceOf(Fixture_Switch_Teams_Read_Model::class, $read_model);
        $this->assertEquals('Home and away teams switched', $read_model->msg);
        $this->assertEquals(123, $read_model->match_id);
        $this->assertEquals('https://example.com/match/123', $read_model->link);
        $this->assertEquals('test-modal', $read_model->modal);
    }

    public function test_present_header_uses_option_gateway_for_date_and_time_format(): void {
        $fixture = $this->createStub( Fixture::class );
        $fixture->method( 'get_id' )->willReturn( 42 );
        $fixture->method( 'get_date' )->willReturn( '2026-10-14 14:30:00' );
        $fixture->method( 'get_season' )->willReturn( '2026' );
        $fixture->method( 'is_pending' )->willReturn( false );
        $fixture->method( 'get_home_points' )->willReturn( '4' );
        $fixture->method( 'get_away_points' )->willReturn( '2' );

        $league = $this->createStub( \Racketmanager\Domain\Competition\League::class );
        $league->title = 'Division 1';
        $event  = $this->createStub( \Racketmanager\Domain\Competition\Event::class );
        $event->name = 'Men Singles';
        $comp   = $this->createStub( \Racketmanager\Domain\Competition\Competition::class );
        $comp->method( 'get_type' )->willReturn( 'league' );

        $details              = new \Racketmanager\Application\Fixture\DTOs\Fixture_Details_DTO( $fixture );
        $details->league      = $league;
        $details->event       = $event;
        $details->competition = $comp;

        $this->option_gateway->method( 'get_option' )->willReturnCallback(
            function( string $name, mixed $default = null ) {
                if ( 'date_format' === $name ) {
                    return 'd/m/Y';
                }
                if ( 'time_format' === $name ) {
                    return 'H:i';
                }
                return $default;
            }
        );

        $header = $this->presenter->map_to_header_read_model( $details );

        $this->assertSame( 42, $header->id );
        $this->assertSame( '14/10/2026', $header->formatted_date );
        $this->assertSame( '14:30', $header->match_time );
    }

    public function test_map_to_header_read_model_with_real_hydrated_timed_and_midnight_fixtures(): void {
        $this->option_gateway->method( 'get_option' )->willReturnCallback(
            fn( string $name, mixed $default = null ) => 'time_format' === $name ? 'H:i' : ( 'date_format' === $name ? 'd/m/Y' : $default )
        );

        // Real fixture with scheduled time (19:30)
        $data_timed = (object) [
            'id'     => 101,
            'date'   => '2026-10-14 19:30:00',
            'season' => '2026',
            'status' => 0,
        ];
        $fixture_timed = Fixture_Factory::from_object( $data_timed );
        $header_timed  = $this->presenter->map_to_header_read_model( new \Racketmanager\Application\Fixture\DTOs\Fixture_Details_DTO( $fixture_timed ) );

        $this->assertSame( '19:30', $header_timed->start_time_display );
        $this->assertSame( '19:30', $header_timed->match_time );
        $this->assertTrue( $header_timed->is_pending );

        // Real fixture with midnight time (00:00:00) => start_time_display should be empty
        $data_midnight = (object) [
            'id'     => 102,
            'date'   => '2026-10-14 00:00:00',
            'season' => '2026',
            'status' => 0,
        ];
        $fixture_midnight = Fixture_Factory::from_object( $data_midnight );
        $header_midnight  = $this->presenter->map_to_header_read_model( new \Racketmanager\Application\Fixture\DTOs\Fixture_Details_DTO( $fixture_midnight ) );

        $this->assertSame( '', $header_midnight->start_time_display );
        $this->assertSame( '00:00', $header_midnight->match_time );

        // Real fixture with date-only ('2026-10-14') => start_time_display should be empty
        $data_date_only = (object) [
            'id'     => 103,
            'date'   => '2026-10-14',
            'season' => '2026',
            'status' => 0,
        ];
        $fixture_date_only = Fixture_Factory::from_object( $data_date_only );
        $header_date_only  = $this->presenter->map_to_header_read_model( new \Racketmanager\Application\Fixture\DTOs\Fixture_Details_DTO( $fixture_date_only ) );

        $this->assertSame( '', $header_date_only->start_time_display );
    }

    public function test_map_to_fixture_option_vars_with_real_hydrated_timed_and_midnight_fixtures(): void {
        $request     = new Fixture_Option_Request( 1, 'modal', 'schedule_match' );
        $league      = $this->createStub( \Racketmanager\Domain\Competition\League::class );
        $event       = $this->createStub( \Racketmanager\Domain\Competition\Event::class );
        $competition = $this->createStub( \Racketmanager\Domain\Competition\Competition::class );

        // Timed fixture -> datetime-local input type
        $fixture_timed = Fixture_Factory::from_object( (object) [
            'id'   => 1,
            'date' => '2026-10-18 19:30:00',
        ] );
        $vars_timed = $this->presenter->map_to_fixture_option_vars(
            new Fixture_Details_DTO( $fixture_timed, $league, $event, $competition ),
            $request,
            'Schedule Match',
            'Save',
            'schedule_action'
        );

        $this->assertSame( 'datetime-local', $vars_timed['date_input_type'] );
        $this->assertSame( '2026-10-18 19:30:00', $vars_timed['date_input'] );

        // Midnight fixture -> date input type
        $fixture_midnight = Fixture_Factory::from_object( (object) [
            'id'   => 2,
            'date' => '2026-10-18 00:00:00',
        ] );
        $vars_midnight = $this->presenter->map_to_fixture_option_vars(
            new Fixture_Details_DTO( $fixture_midnight, $league, $event, $competition ),
            $request,
            'Schedule Match',
            'Save',
            'schedule_action'
        );

        $this->assertSame( 'date', $vars_midnight['date_input_type'] );
        $this->assertSame( '2026-10-18', $vars_midnight['date_input'] );

        // Date-only fixture -> date input type
        $fixture_date_only = Fixture_Factory::from_object( (object) [
            'id'   => 3,
            'date' => '2026-10-18',
        ] );
        $vars_date_only = $this->presenter->map_to_fixture_option_vars(
            new Fixture_Details_DTO( $fixture_date_only, $league, $event, $competition ),
            $request,
            'Schedule Match',
            'Save',
            'schedule_action'
        );

        $this->assertSame( 'date', $vars_date_only['date_input_type'] );
        $this->assertSame( '2026-10-18', $vars_date_only['date_input'] );
    }
}
