<?php

namespace Racketmanager\Tests\Unit\Domain\Fixture\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Fixture\DTOs\Fixture_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Services\Fixture_Detail_Service;

class Fixture_Detail_Service_Test extends TestCase {
    private Fixture_Detail_Service $service;

    protected function setUp(): void {
        parent::setUp();
        $this->service = new Fixture_Detail_Service();
    }

    public function test_enrich_sets_is_knockout_true_when_final_is_present() {
        $fixture = $this->createStub(Fixture::class);
        $fixture->method('get_final')->willReturn('final');
        
        $dto = new Fixture_Details_DTO($fixture);
        $result = $this->service->enrich($dto);

        $this->assertTrue($result->enriched_data['is_knockout']);
    }

    public function test_enrich_sets_is_knockout_false_when_final_is_empty() {
        $fixture = $this->createStub(Fixture::class);
        $fixture->method('get_final')->willReturn(null);
        
        $dto = new Fixture_Details_DTO($fixture);
        $result = $this->service->enrich($dto);

        $this->assertFalse($result->enriched_data['is_knockout']);
    }
}
