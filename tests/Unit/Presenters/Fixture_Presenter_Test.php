<?php

namespace Racketmanager\Tests\Unit\Presenters;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Fixture\DTOs\Fixture_Date_Update_Read_Model;
use Racketmanager\Application\Fixture\DTOs\Fixture_Result_Read_Model;
use Racketmanager\Application\Fixture\DTOs\Fixture_Switch_Teams_Read_Model;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Domain\DTO\Fixture\Fixture_Date_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Switch_Teams_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response;
use Racketmanager\Domain\DTO\Fixture\Match_Option_Request;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Presenters\Fixture_Presenter;
use ReflectionClass;

class Fixture_Presenter_Test extends TestCase {
    private Fixture_Presenter $presenter;
    private Fixture_Link_Service $link_service;

    protected function setUp(): void {
        parent::setUp();
        $this->link_service = $this->createStub( Fixture_Link_Service::class );
        $this->presenter    = new Fixture_Presenter( $this->link_service );
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

        $request = new Match_Option_Request(
            match_id: 123,
            modal: 'test-modal',
            option: 'test-option'
        );

        $vars = $this->presenter->map_to_match_option_vars(
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
}
