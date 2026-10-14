<?php
declare(strict_types=1);

namespace Tests\Unit\Application\Tournament\Queries;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Tournament\Queries\Get_Order_Of_Play_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Order_Of_Play_Query;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;

class Get_Order_Of_Play_Handler_Test extends TestCase
{
    private $tournament_service;
    private $competition_repository;
    private $fixture_repository;
    private $competition_service;
    private $team_service;
    private $handler;

    protected function setUp(): void
    {
        $this->tournament_service     = $this->createMock(Tournament_Service::class);
        $this->competition_repository = $this->createMock(Competition_Repository::class);
        $this->fixture_repository     = $this->createMock(Fixture_Repository::class);
        $this->competition_service    = $this->createMock(Competition_Service::class);
        $this->team_service           = $this->createMock(Team_Service::class);

        $this->handler = new Get_Order_Of_Play_Handler(
            $this->tournament_service,
            $this->competition_repository,
            $this->fixture_repository,
            $this->competition_service,
            $this->team_service
        );
    }

    protected function tearDown(): void
    {
    }

    public function test_handle_returns_error_if_no_tournament_or_competition(): void
    {
        $query = Get_Order_Of_Play_Query::from_atts([]);
        $result = $this->handler->handle($query);
        $this->assertTrue($result->is_error());
    }

    public function test_handle_returns_error_if_competition_not_found(): void
    {
        $this->competition_repository->method('find_by_id')->with(123)->willReturn(null);
        $query = Get_Order_Of_Play_Query::from_atts(['competition_id' => 123]);
        $result = $this->handler->handle($query);
        $this->assertTrue($result->is_error());
    }

    public function test_handle_returns_error_if_season_missing_for_competition(): void
    {
        $comp = $this->createMock(Competition::class);
        $this->competition_repository->method('find_by_id')->with(123)->willReturn($comp);
        $query = Get_Order_Of_Play_Query::from_atts(['competition_id' => 123]);
        $result = $this->handler->handle($query);
        $this->assertTrue($result->is_error());
    }

    public function test_handle_returns_error_if_season_not_found_for_competition(): void
    {
        $comp = $this->createMock(Competition::class);
        $comp->method('get_season_by_name')->with('2026')->willReturn(null);
        $this->competition_repository->method('find_by_id')->with(123)->willReturn($comp);
        $query = Get_Order_Of_Play_Query::from_atts(['competition_id' => 123, 'season' => '2026']);
        $result = $this->handler->handle($query);
        $this->assertTrue($result->is_error());
    }

    public function test_handle_success_with_tournament_id(): void
    {
        $tournament = $this->createMock('Racketmanager\Domain\Tournament');
        $tournament->method('get_order_of_play')->willReturn([
            ['court' => '1', 'matches' => ['10', '11']]
        ]);
        $this->tournament_service->method('get_tournament')->with(1)->willReturn($tournament);
        
        $fixture = $this->createMock(Fixture::class);
        $fixture->method('get_league_id')->willReturn(null);
        $fixture->method('get_date')->willReturn('2026-10-14 10:00:00');
        $fixture->method('get_location')->willReturn('Court 1');
        $fixture->method('get_winner_id')->willReturn(null);
        $fixture->method('get_home_team')->willReturn('Home Team');
        $fixture->method('get_away_team')->willReturn('Away Team');
        
        $this->fixture_repository->method('find_by_id')->willReturn($fixture);
        
        $query = Get_Order_Of_Play_Query::from_atts(['id' => 1]);
        $result = $this->handler->handle($query);
        
        $this->assertFalse($result->is_error());
        $this->assertArrayHasKey('order_of_play', $result->vars);
    }
}
