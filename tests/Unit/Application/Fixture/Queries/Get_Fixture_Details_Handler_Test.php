<?php

namespace Racketmanager\Tests\Unit\Application\Fixture\Queries;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Fixture\DTOs\Fixture_Details_DTO;
use Racketmanager\Application\Fixture\Queries\Get_Fixture_Details_Handler;
use Racketmanager\Application\Fixture\Queries\Get_Fixture_Details_Query;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Services\Fixture_Detail_Service;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;

class Get_Fixture_Details_Handler_Test extends TestCase {
    private $repository;
    private $detail_service;
    private Get_Fixture_Details_Handler $handler;

    protected function setUp(): void {
        parent::setUp();
        $this->repository = $this->createMock(Fixture_Repository_Interface::class);
        $this->detail_service = $this->createStub(Fixture_Detail_Service::class);
        $this->handler = new Get_Fixture_Details_Handler($this->repository, $this->detail_service);
    }

    public function test_handle_returns_null_if_fixture_not_found_by_id() {
        $query = new Get_Fixture_Details_Query(123);
        $this->repository->expects($this->once())
            ->method('find_by_id')
            ->with(123)
            ->willReturn(null);

        $result = $this->handler->handle($query);
        $this->assertNull($result);
    }

    public function test_handle_returns_null_if_fixture_not_found_by_slug() {
        $slug_criteria = ['league_id' => 1];
        $query = new Get_Fixture_Details_Query(null, $slug_criteria);
        
        $this->repository->expects($this->once())
            ->method('find_one_by_slug_criteria')
            ->with($slug_criteria)
            ->willReturn(null);

        $result = $this->handler->handle($query);
        $this->assertNull($result);
    }

    public function test_handle_success_with_id() {
        $this->detail_service = $this->createMock(Fixture_Detail_Service::class);
        $this->handler = new Get_Fixture_Details_Handler($this->repository, $this->detail_service);

        $fixture = $this->createStub(Fixture::class);
        $query = new Get_Fixture_Details_Query(123, [], 456, 789);

        $this->repository->expects($this->once())
            ->method('find_by_id')
            ->with(123)
            ->willReturn($fixture);

        $dto = new Fixture_Details_DTO($fixture, 456, 789);
        $this->detail_service->expects($this->once())
            ->method('enrich')
            ->with($this->callback(function($arg) use ($fixture) {
                return $arg instanceof Fixture_Details_DTO && $arg->fixture === $fixture;
            }))
            ->willReturn($dto);

        $result = $this->handler->handle($query);

        $this->assertSame($dto, $result);
        $this->assertEquals(456, $result->focus_player_id);
        $this->assertEquals(789, $result->focus_team_id);
    }
}
