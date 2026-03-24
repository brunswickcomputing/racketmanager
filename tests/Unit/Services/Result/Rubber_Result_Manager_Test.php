<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Result;

if ( ! function_exists( 'Racketmanager\get_rubber' ) ) {
    eval( 'namespace Racketmanager { function get_rubber($id) { return isset($GLOBALS["wp_stubs_rubbers"][$id]) ? $GLOBALS["wp_stubs_rubbers"][$id] : null; } }' );
}
if ( ! function_exists( 'Racketmanager\Domain\maybe_unserialize' ) ) {
    eval( 'namespace Racketmanager\Domain { function maybe_unserialize($data) { return $data; } }' );
}

use Racketmanager\Services\Validator\Player_Validation_Service;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture;
use Racketmanager\Domain\League;
use Racketmanager\Domain\Rubber;
use Racketmanager\Domain\DTO\Rubber\Rubber_Update_Request;
use Racketmanager\Services\Result\Rubber_Result_Manager;
use Racketmanager\Services\Validator\Score_Validation_Service;
use Racketmanager\Services\League_Service;
use stdClass;

class Rubber_Result_Manager_Test extends TestCase {
    private $score_validator;
    private $league_service;
    private $player_validator;
    private $manager;

    protected function setUp(): void {
        parent::setUp();
        $this->score_validator = $this->createMock(Score_Validation_Service::class);
        $this->league_service  = $this->createMock(League_Service::class);
        $this->rubber_repository = $this->createMock(\Racketmanager\Repositories\Rubber_Repository::class);
        $this->player_validator = $this->createMock(Player_Validation_Service::class);
        $this->manager = new Rubber_Result_Manager(
            $this->score_validator,
            $this->league_service,
            $this->rubber_repository,
            $this->player_validator
        );
    }

    public function test_handle_rubber_update_validates_and_updates_rubber(): void {
        $fixture_data = new stdClass();
        $fixture_data->id = 123;
        $fixture_data->league_id = 456;
        $fixture_data->home_team = 1;
        $fixture_data->away_team = 2;
        $fixture = new Fixture($fixture_data);

        $league = $this->createMock(League::class);
        $league->method('get_id')->willReturn(456);
        $league->method('get_point_rule')->willReturn(['match_result' => 'sets']);
        $league->num_sets_to_win = 2;
        $league->num_sets = 3;
        $this->league_service->method('get_league')->with(456)->willReturn($league);

        $rubber_mock = $this->createMock(Rubber::class);
        $rubber_mock->method('get_id')->willReturn(10);
        $rubber_mock->method('calculate_result')->willReturn((object)[
            'home' => 2.0,
            'away' => 0.0,
            'winner' => 1,
            'loser' => 2
        ]);

        $request = new Rubber_Update_Request(
            rubber_id: 10,
            rubber_type: 'S',
            rubber_number: 1,
            players: ['home' => [1 => 10], 'away' => [1 => 20]],
            sets: [],
            rubber_status: 'none'
        );

        $this->score_validator->method('get_error')->willReturn(false);
        $this->score_validator->method('get_sets')->willReturn([]);
        $this->score_validator->method('get_stats')->willReturn([]);
        $this->score_validator->method('get_points')->willReturn([]);

        $this->player_validator->method('apply_dummy_players')->willReturn($request->players);

        $GLOBALS['wp_stubs_rubbers'][10] = $rubber_mock;

        $this->rubber_repository->expects($this->once())->method('save')->with($rubber_mock);
        $rubber_mock->expects($this->once())->method('set_players');

        $result = $this->manager->handle_rubber_update($fixture, $request);

        $this->assertEquals(10, $result->rubber_id);
        $this->assertEquals(2.0, $result->home_points);
        $this->assertEquals(0.0, $result->away_points);
        $this->assertEquals(1, $result->winner_id);

        unset($GLOBALS['wp_stubs_rubbers'][10]);
    }
}
