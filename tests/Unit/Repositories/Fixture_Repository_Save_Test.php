<?php

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Repositories\Fixture_Repository;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Fixture\Fixture_Factory;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

class Fixture_Repository_Save_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private $wpdb;
    private Fixture_Repository $repository;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->wpdb         = $this->createMock( wpdb::class );
        $this->wpdb->prefix = 'wp_';
        $GLOBALS['wpdb']    = $this->wpdb;

        $this->repository = new Fixture_Repository();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_save_serializes_comments(): void {
        $fixture_data = (object)[
            'id' => 123,
            'comments' => ['note' => 'test comment']
        ];
        $fixture = Fixture_Factory::from_object($fixture_data);

        // Expect update to be called with serialized comments
        // The table name is constructed in constructor: wp_ + racketmanager_matches
        $expected_table = 'wp_racketmanager_matches';

        $this->wpdb->expects($this->once())
            ->method('update')
            ->with(
                $expected_table,
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
