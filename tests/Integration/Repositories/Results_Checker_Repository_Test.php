<?php
declare( strict_types=1 );

namespace Racketmanager;

if ( ! function_exists( 'Racketmanager\get_match' ) ) {
    function get_match( $id ) {
        return (object) [
            'home_team' => 10,
            'away_team' => 20,
            'teams'     => [
                'home' => (object) [ 'id' => 10 ],
                'away' => (object) [ 'id' => 20 ],
            ],
        ];
    }
}

if ( ! function_exists( 'Racketmanager\get_player' ) ) {
    function get_player( $id ) {
        return (object) [ 'id' => $id ];
    }
}

if ( ! function_exists( 'Racketmanager\get_rubber' ) ) {
    function get_rubber( $id ) {
        return (object) [ 'id' => $id ];
    }
}

namespace Racketmanager\Tests\Integration\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Results_Checker;
use Racketmanager\Repositories\Results_Checker_Repository;
use stdClass;

if ( ! function_exists( 'get_userdata' ) ) {
    function get_userdata( $id ) {
        return (object) [ 'display_name' => 'Test User' ];
    }
}

if ( ! function_exists( 'get_current_user_id' ) ) {
    function get_current_user_id() {
        return 1;
    }
}

class Results_Checker_Repository_Test extends TestCase {
    private Results_Checker_Repository $repository;
    private $wpdb;

    protected function setUp(): void {
        parent::setUp();
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->repository = new Results_Checker_Repository();
        
        // Ensure table is empty for the test match_id
        $this->wpdb->delete( $this->wpdb->prefix . 'racketmanager_results_checker', [ 'match_id' => 12345 ] );
    }

    protected function tearDown(): void {
        $this->wpdb->delete( $this->wpdb->prefix . 'racketmanager_results_checker', [ 'match_id' => 12345 ] );
        parent::tearDown();
    }

    public function test_save_and_find_by_id(): void {
        $data = new stdClass();
        $data->league_id = 1;
        $data->match_id = 12345;
        $data->team_id = 2;
        $data->player_id = 3;
        $data->rubber_id = 4;
        $data->description = 'Test Error';
        $data->status = 0;
        $data->updated_user = 5;

        $checker = new Results_Checker( $data, false );
        $this->repository->save( $checker );

        $this->assertNotEmpty( $checker->id );

        $found = $this->repository->find_by_id( (int) $checker->id );
        $this->assertNotNull( $found );
        $this->assertEquals( 12345, $found->match_id );
        $this->assertEquals( 'Test Error', $found->description );
    }

    public function test_delete_by_fixture_id(): void {
        $data = new stdClass();
        $data->match_id = 12345;
        $data->league_id = 1;
        $data->team_id = 2;
        $data->player_id = 3;
        $data->rubber_id = 4;
        $data->description = 'Error 1';
        
        $checker1 = new Results_Checker( $data, false );
        $this->repository->save( $checker1 );

        $data->description = 'Error 2';
        $checker2 = new Results_Checker( $data, false );
        $this->repository->save( $checker2 );

        $count = (int) $this->wpdb->get_var( "SELECT count(*) FROM {$this->wpdb->prefix}racketmanager_results_checker WHERE match_id = 12345" );
        $this->assertEquals( 2, $count );

        $this->repository->delete_by_fixture_id( 12345 );

        $count = (int) $this->wpdb->get_var( "SELECT count(*) FROM {$this->wpdb->prefix}racketmanager_results_checker WHERE match_id = 12345" );
        $this->assertEquals( 0, $count );
    }

    public function test_find_by_fixture_id(): void {
        $data = new stdClass();
        $data->match_id = 12345;
        $data->league_id = 1;
        $data->team_id = 2;
        $data->player_id = 3;
        $data->rubber_id = 4;
        $data->description = 'Error 1';
        
        $checker1 = new Results_Checker( $data, false );
        $this->repository->save( $checker1 );

        $checkers = $this->repository->find_by_fixture_id( 12345 );
        $this->assertCount( 1, $checkers );
        $this->assertEquals( 'Error 1', $checkers[0]->description );
    }
}
