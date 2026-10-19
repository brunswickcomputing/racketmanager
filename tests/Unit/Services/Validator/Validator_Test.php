<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Validator;

use PHPUnit\Framework\TestCase;
use Racketmanager\Services\Validator\Validator;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;

class Validator_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_set_errors_keys_err_statuses_by_err_flds_index(): void {
        $validator = new Validator();

        // 1. Direct push (does not push to err_statuses)
        $validator->telephone( '' );

        // 2. set_errors with 404
        $validator->set_errors( 'clubId', 'Club not found', 404 );

        // 3. set_errors with 400
        $validator->set_errors( 'event', 'Invalid event', 400 );

        $this->assertTrue( $validator->error );
        $this->assertSame( [ 'contactno', 'clubId', 'event' ], $validator->err_flds );
        $this->assertSame( [
            'Telephone number required',
            'Club not found',
            'Invalid event',
        ], $validator->err_msgs );

        // err_statuses should be keyed by the index of err_flds
        $this->assertSame( [
            1 => 404,
            2 => 400,
        ], $validator->err_statuses );
        $this->assertArrayNotHasKey( 0, $validator->err_statuses );
        $this->assertSame( 400, $validator->status );
    }

    public function test_set_errors_initial_call_sets_status_at_index_zero(): void {
        $validator = new Validator();
        $validator->set_errors( 'contactno', 'Player not found', 404 );

        $this->assertTrue( $validator->error );
        $this->assertSame( [ 'contactno' ], $validator->err_flds );
        $this->assertSame( [ 'Player not found' ], $validator->err_msgs );
        $this->assertSame( [ 0 => 404 ], $validator->err_statuses );
        $this->assertSame( 404, $validator->status );
    }

    public function test_get_details_returns_status_and_errors(): void {
        $validator = new Validator();
        $validator->set_errors( 'field1', 'Error 1', 422 );

        $details = $validator->get_details();

        $this->assertTrue( $details->error );
        $this->assertSame( 422, $details->status );
        $this->assertSame( [ 'field1' ], $details->err_flds );
        $this->assertSame( [ 'Error 1' ], $details->err_msgs );
    }
}
