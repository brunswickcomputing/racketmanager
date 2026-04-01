<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Integration\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Results_Report;
use Racketmanager\Repositories\Results_Report_Repository;
use stdClass;

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data ) {
        return json_encode( $data );
    }
}

class Results_Report_Repository_Test extends TestCase {
    private Results_Report_Repository $repository;
    private $wpdb;

    protected function setUp(): void {
        parent::setUp();
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->repository = new Results_Report_Repository();
        
        // Ensure table is empty for the test match_id
        $this->wpdb->delete( $this->wpdb->prefix . 'racketmanager_results_report', [ 'match_id' => 12345 ] );
    }

    protected function tearDown(): void {
        $this->wpdb->delete( $this->wpdb->prefix . 'racketmanager_results_report', [ 'match_id' => 12345 ] );
        parent::tearDown();
    }

    public function test_save_and_find_by_id(): void {
        $data = new stdClass();
        $data->match_id = 12345;
        $data->data = (object)[ 'test' => 'report' ];

        $report = new Results_Report( $data, false );
        $this->repository->save( $report );

        $this->assertNotEmpty( $report->id );

        $found = $this->repository->find_by_id( (int) $report->id );
        $this->assertNotNull( $found );
        $this->assertEquals( 12345, $found->match_id );
        $this->assertEquals( 'report', $found->data->test );
    }

    public function test_delete_by_fixture_id(): void {
        $data = new stdClass();
        $data->match_id = 12345;
        $data->data = (object)[ 'test' => 'report' ];
        
        $report = new Results_Report( $data, false );
        $this->repository->save( $report );

        $count = (int) $this->wpdb->get_var( "SELECT count(*) FROM {$this->wpdb->prefix}racketmanager_results_report WHERE match_id = 12345" );
        $this->assertEquals( 1, $count );

        $this->repository->delete_by_fixture_id( 12345 );

        $count = (int) $this->wpdb->get_var( "SELECT count(*) FROM {$this->wpdb->prefix}racketmanager_results_report WHERE match_id = 12345" );
        $this->assertEquals( 0, $count );
    }

    public function test_find_by_fixture_id(): void {
        $data = new stdClass();
        $data->match_id = 12345;
        $data->data = (object)[ 'test' => 'report 1' ];
        
        $report = new Results_Report( $data, false );
        $this->repository->save( $report );

        $found = $this->repository->find_by_fixture_id( 12345 );
        $this->assertNotNull( $found );
        $this->assertEquals( 'report 1', $found->data->test );
    }
}
