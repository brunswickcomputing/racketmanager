<?php

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Domain\Fixture\Fixture;
use wpdb;

class Fixture_Repository_Save_Test extends TestCase {
    private $wpdb;
    private $original_wpdb;
    private Fixture_Repository $repository;

    protected function setUp(): void {
        parent::setUp();
        $this->wpdb = $this->createMock( wpdb::class );
        $this->wpdb->prefix = 'wp_';
        $this->wpdb->matches = 'wp_racketmanager_matches';

        global $wpdb;
        $this->original_wpdb = $wpdb;
        $wpdb = $this->wpdb;

        $this->repository = new Fixture_Repository();
    }

    protected function tearDown(): void {
        global $wpdb;
        $wpdb = $this->original_wpdb;
        parent::tearDown();
    }

    public function test_save_serializes_comments(): void {
        $fixture_data = (object)[
            'id' => 123,
            'comments' => ['note' => 'test comment']
        ];
        $fixture = new Fixture($fixture_data);

        // Expect update to be called with serialized comments
        $this->wpdb->expects($this->once())
            ->method('update')
            ->with(
                $this->wpdb->matches,
                $this->callback(function($data) {
                    return isset($data['comments']) && is_string($data['comments']) && str_contains($data['comments'], 'test comment');
                }),
                ['id' => 123],
                $this->anything(),
                ['%d']
            )
            ->willReturn(1);

        $this->repository->save($fixture);
    }
}
