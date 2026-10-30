<?php
declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Application\Tournament\Queries;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Tournament\Queries\Get_Order_Of_Play_Handler;
use Racketmanager\Application\Tournament\Queries\Get_Order_Of_Play_Query;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\DTO\Team\Team_Details_DTO;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Application\Ports\Option_Gateway_Interface;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Tournament_Shortcode_Adapter;
use Racketmanager\Presentation\Presenters\Order_Of_Play_Presenter;
use Racketmanager\Presentation\View_Models\Order_Of_Play_View_Model;
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
    private $option_gateway;
    private $order_of_play_presenter;
    private $handler;

    protected function setUp(): void
    {
        $this->tournament_service     = $this->createStub(Tournament_Service::class);
        $this->competition_repository = $this->createStub(Competition_Repository::class);
        $this->fixture_repository     = $this->createStub(Fixture_Repository::class);
        $this->competition_service    = $this->createStub(Competition_Service::class);
        $this->team_service           = $this->createStub(Team_Service::class);
        $this->option_gateway         = $this->createStub(Option_Gateway_Interface::class);
        $this->option_gateway->method('get_option')->willReturnCallback(
            function (string $name, mixed $default = false) {
                return 'time_format' === $name ? 'H:i' : $default;
            }
        );

        $this->order_of_play_presenter = new Order_Of_Play_Presenter(
            $this->fixture_repository,
            $this->competition_service,
            $this->team_service,
            $this->option_gateway
        );

        $this->handler = new Get_Order_Of_Play_Handler(
            $this->tournament_service,
            $this->competition_repository
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
        $this->competition_repository->method('find_by_id')->willReturnCallback(function ($id) {
            self::assertSame(123, $id);
            return null;
        });
        $query = Get_Order_Of_Play_Query::from_atts(['competition_id' => 123]);
        $result = $this->handler->handle($query);
        $this->assertTrue($result->is_error());
    }

    public function test_handle_returns_error_if_season_missing_for_competition(): void
    {
        $comp = $this->createStub(Competition::class);
        $this->competition_repository->method('find_by_id')->willReturnCallback(function ($id) use ($comp) {
            self::assertSame(123, $id);
            return $comp;
        });
        $query = Get_Order_Of_Play_Query::from_atts(['competition_id' => 123]);
        $result = $this->handler->handle($query);
        $this->assertTrue($result->is_error());
    }

    public function test_handle_returns_error_if_season_not_found_for_competition(): void
    {
        $comp = $this->createStub(Competition::class);
        $comp->method('get_season_by_name')->willReturnCallback(function ($season) {
            self::assertSame('2026', $season);
            return null;
        });
        $this->competition_repository->method('find_by_id')->willReturnCallback(function ($id) use ($comp) {
            self::assertSame(123, $id);
            return $comp;
        });
        $query = Get_Order_Of_Play_Query::from_atts(['competition_id' => 123, 'season' => '2026']);
        $result = $this->handler->handle($query);
        $this->assertTrue($result->is_error());
    }

    public function test_handle_success_with_tournament_id(): void
    {
        $tournament = $this->createStub('Racketmanager\Domain\Tournament\Tournament');
        $tournament->method('get_order_of_play')->willReturn([
            ['court' => '1', 'matches' => ['10', '11']]
        ]);
        $this->tournament_service->method('get_tournament')->willReturnCallback(function ($id) use ($tournament) {
            self::assertSame(1, $id);
            return $tournament;
        });
        
        $fixture = $this->createStub(Fixture::class);
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
        $adapter = new Tournament_Shortcode_Adapter( order_of_play_presenter: $this->order_of_play_presenter );
        $vm = $adapter->present_order_of_play($result);
        $this->assertArrayHasKey('order_of_play', $vm->vars);
        $order_of_play = $vm->vars['order_of_play'];
        $this->assertInstanceOf(Order_Of_Play_View_Model::class, $order_of_play);
        $this->assertContains('10:00', $order_of_play->times);
        $this->assertSame('10:00', $order_of_play->match_time['10:00']['1']->time);
    }

    public function test_handle_and_render_templates_with_placeholder_and_empty_slots(): void
    {
        $tournament = $this->createStub('Racketmanager\Domain\Tournament\Tournament');
        $tournament->method('get_order_of_play')->willReturn([
            ['court' => 'Court 1', 'fixtures' => ['101', '102']],
            ['court' => 'Court 2', 'fixtures' => ['103']],
        ]);
        $this->tournament_service->method('get_tournament')->willReturn($tournament);

        $fixture1 = $this->createStub(Fixture::class);
        $fixture1->method('get_league_id')->willReturn(null);
        $fixture1->method('get_date')->willReturn('2026-10-14 10:00:00');
        $fixture1->method('get_location')->willReturn('Court 1');
        $fixture1->method('get_home_team')->willReturn('1_semi_1');
        $fixture1->method('get_away_team')->willReturn('Westcliff 1');

        $fixture2 = $this->createStub(Fixture::class);
        $fixture2->method('get_league_id')->willReturn(null);
        $fixture2->method('get_date')->willReturn('2026-10-14 12:00:00');
        $fixture2->method('get_location')->willReturn('Court 1');
        $fixture2->method('get_home_team')->willReturn('Player A');
        $fixture2->method('get_away_team')->willReturn('Player B');

        $fixture3 = $this->createStub(Fixture::class);
        $fixture3->method('get_league_id')->willReturn(null);
        $fixture3->method('get_date')->willReturn('2026-10-14 10:00:00');
        $fixture3->method('get_location')->willReturn('Court 2');
        $fixture3->method('get_home_team')->willReturn('Player C');
        $fixture3->method('get_away_team')->willReturn('Player D');

        $this->fixture_repository->method('find_by_id')->willReturnCallback(function (int $id) use ($fixture1, $fixture2, $fixture3) {
            return match ($id) {
                101 => $fixture1,
                102 => $fixture2,
                103 => $fixture3,
                default => null,
            };
        });

        $this->team_service->method('derive_team_details')->willReturnCallback(function (string $val) {
            $team = $this->createStub(Team::class);
            if ('1_semi_1' === $val) {
                $team->method('get_name')->willReturn('Winner Semi 1');
            } else {
                $team->method('get_name')->willReturn($val);
            }
            return new Team_Details_DTO($team);
        });

        $query = Get_Order_Of_Play_Query::from_atts(['id' => 1]);
        $result = $this->handler->handle($query);

        $this->assertFalse($result->is_error());
        $adapter = new Tournament_Shortcode_Adapter( order_of_play_presenter: $this->order_of_play_presenter );
        $vm = $adapter->present_order_of_play($result);
        $order_of_play = $vm->vars['order_of_play'];
        $this->assertInstanceOf(Order_Of_Play_View_Model::class, $order_of_play);

        $output = $this->render_order_of_play($order_of_play);

        $this->assertStringContainsString('Order of play', $output);
        $this->assertStringContainsString('Court 1', $output);
        $this->assertStringContainsString('Court 2', $output);
        $this->assertStringContainsString('Winner Semi 1', $output);
        $this->assertStringContainsString('is_pending', $output);
        $this->assertStringContainsString('Westcliff 1', $output);
    }

    public function test_handle_and_render_templates_no_order_of_play(): void
    {
        $tournament = $this->createStub('Racketmanager\Domain\Tournament\Tournament');
        $tournament->method('get_order_of_play')->willReturn([]);
        $this->tournament_service->method('get_tournament')->willReturn($tournament);

        $query = Get_Order_Of_Play_Query::from_atts(['id' => 1]);
        $result = $this->handler->handle($query);

        $this->assertFalse($result->is_error());
        $adapter = new Tournament_Shortcode_Adapter( order_of_play_presenter: $this->order_of_play_presenter );
        $vm = $adapter->present_order_of_play($result);
        $order_of_play = $vm->vars['order_of_play'];
        $this->assertInstanceOf(Order_Of_Play_View_Model::class, $order_of_play);
        $this->assertEmpty($order_of_play->courts);

        $output = $this->render_order_of_play($order_of_play);

        $this->assertStringContainsString('No order of play', $output);
    }

    private function render_order_of_play(Order_Of_Play_View_Model $order_of_play): string
    {
        $had_previous_order = array_key_exists('order_of_play', $GLOBALS);
        $previous_order = $GLOBALS['order_of_play'] ?? null;
        $buffer_level = ob_get_level();
        $GLOBALS['order_of_play'] = $order_of_play;
        ob_start();

        try {
            include dirname(__DIR__, 5) . '/templates/orderofplay.php';
            return (string) ob_get_clean();
        } finally {
            while (ob_get_level() > $buffer_level) {
                ob_end_clean();
            }
            if ($had_previous_order) {
                $GLOBALS['order_of_play'] = $previous_order;
            } else {
                unset($GLOBALS['order_of_play']);
            }
        }
    }
}
