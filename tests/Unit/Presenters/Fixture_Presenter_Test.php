<?php

namespace Racketmanager\Tests\Unit\Presenters;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Fixture\DTOs\Fixture_Result_Read_Model;
use Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Presenters\Fixture_Presenter;

class Fixture_Presenter_Test extends TestCase {
    private Fixture_Presenter $presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->presenter = new Fixture_Presenter();
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
}
