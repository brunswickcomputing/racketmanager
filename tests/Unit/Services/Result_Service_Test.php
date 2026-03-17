<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Fixture;
use Racketmanager\Domain\Result;
use Racketmanager\Domain\Championship;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Services\Result_Service;
use Racketmanager\Services\Championship_Manager;
use ReflectionClass;

require_once __DIR__ . '/../../wp-stubs.php';

final class Result_Service_Test extends TestCase {
    private $fixture_repository;
    private $result_service;

    protected function setUp(): void {
        $this->fixture_repository = $this->createMock(Fixture_Repository::class);
        $this->result_service = new Result_Service($this->fixture_repository);
    }

    public function test_apply_to_fixture_saves_fixture(): void {
        $fixture = $this->createMock(Fixture::class);
        $result  = $this->createMock(Result::class);

        $fixture->expects($this->once())
                ->method('set_result')
                ->with($result);

        $this->fixture_repository->expects($this->once())
                                 ->method('save')
                                 ->with($fixture);

        $this->result_service->apply_to_fixture($fixture, $result);
    }

    public function test_get_round_for_fixture_returns_correct_round(): void {
        $fixture = $this->createMock(Fixture::class);
        $fixture->method('get_final')->willReturn('final2');

        $championship = $this->createMock(Championship::class);
        $championship->method('get_finals_by_key')->willReturn([
            'final2' => ['round' => 2]
        ]);

        $reflection = new ReflectionClass(Result_Service::class);
        $method = $reflection->getMethod('get_round_for_fixture');
        $method->setAccessible(true);

        $result = $method->invoke($this->result_service, $fixture, $championship);
        $this->assertSame(2, $result);
    }

    public function test_get_round_for_fixture_returns_null_for_no_final(): void {
        $fixture = $this->createMock(Fixture::class);
        $fixture->method('get_final')->willReturn(null);

        $championship = $this->createMock(Championship::class);

        $reflection = new ReflectionClass(Result_Service::class);
        $method = $reflection->getMethod('get_round_for_fixture');
        $method->setAccessible(true);

        $result = $method->invoke($this->result_service, $fixture, $championship);
        $this->assertNull($result);
    }
}
