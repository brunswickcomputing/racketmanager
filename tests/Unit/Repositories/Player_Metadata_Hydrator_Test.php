<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Repositories\Player_Metadata_Hydrator;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;

require_once dirname( __DIR__, 2 ) . '/wp-stubs.php';

final class Player_Metadata_Hydrator_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Player_Metadata_Hydrator $hydrator;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->hydrator = new Player_Metadata_Hydrator();
    }

    protected function tearDown(): void {
        unset(
            $GLOBALS['wp_stubs_userdata'],
            $GLOBALS['wp_stubs_users'],
            $GLOBALS['wp_stubs_user_meta'],
        );
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_hydrate_populates_basic_meta(): void {
        $data     = new stdClass();
        $data->ID = 10;

        $GLOBALS['wp_stubs_user_meta'][10] = [
            'first_name'         => [ 'Alice' ],
            'last_name'          => [ 'Smith' ],
            'gender'             => [ 'F' ],
            'racketmanager_type' => [ 'standard' ],
            'btm'                => [ '12345678' ],
            'year_of_birth'      => [ '1995' ],
            'contactno'          => [ '01234567890' ],
        ];

        $this->hydrator->hydrate( $data );

        $this->assertSame( 'Alice', $data->firstname );
        $this->assertSame( 'Smith', $data->surname );
        $this->assertSame( 'F', $data->gender );
        $this->assertSame( 'standard', $data->type );
        $this->assertSame( '12345678', $data->btm );
        $this->assertSame( '1995', $data->year_of_birth );
        $this->assertSame( '01234567890', $data->contactno );
        $this->assertSame( 'standard', $data->system_record );
    }

    public function test_hydrate_normalizes_empty_year_of_birth_and_contact_number(): void {
        $data     = new stdClass();
        $data->ID = 11;

        $GLOBALS['wp_stubs_user_meta'][11] = [
            'first_name'    => [ 'Bob' ],
            'last_name'     => [ 'Jones' ],
            'gender'        => [ 'M' ],
            'year_of_birth' => [ '' ],
            'contactno'     => [ '' ],
        ];

        $this->hydrator->hydrate( $data );

        $this->assertNull( $data->year_of_birth );
        $this->assertNull( $data->contactno );
    }

    public function test_hydrate_removal_meta_when_not_removed(): void {
        $data     = new stdClass();
        $data->ID = 12;

        $GLOBALS['wp_stubs_user_meta'][12] = [];

        $this->hydrator->hydrate( $data );

        $this->assertNull( $data->removed_date );
        $this->assertNull( $data->removed_user );
    }

    public function test_hydrate_removal_meta_when_removed_with_user(): void {
        $data     = new stdClass();
        $data->ID = 13;

        $GLOBALS['wp_stubs_user_meta'][13] = [
            'remove_date' => [ '2026-03-01' ],
            'remove_user' => [ 'admin_user' ],
        ];

        $this->hydrator->hydrate( $data );

        $this->assertSame( '2026-03-01', $data->removed_date );
        $this->assertSame( 'admin_user', $data->removed_user );
    }

    public function test_hydrate_removal_meta_when_removed_without_user(): void {
        $data     = new stdClass();
        $data->ID = 14;

        $GLOBALS['wp_stubs_user_meta'][14] = [
            'remove_date' => [ '2026-03-01' ],
            'remove_user' => [ '' ],
        ];

        $this->hydrator->hydrate( $data );

        $this->assertSame( '2026-03-01', $data->removed_date );
        $this->assertNull( $data->removed_user );
    }

    public function test_hydrate_locked_meta_when_not_locked(): void {
        $data     = new stdClass();
        $data->ID = 15;

        $GLOBALS['wp_stubs_user_meta'][15] = [
            'locked' => [ '0' ],
        ];

        $this->hydrator->hydrate( $data );

        $this->assertFalse( $data->locked );
        $this->assertFalse( property_exists( $data, 'locked_date' ) );
        $this->assertFalse( property_exists( $data, 'locked_user' ) );
    }

    public function test_hydrate_locked_meta_resolves_locked_user_display_name(): void {
        $data     = new stdClass();
        $data->ID = 16;

        $GLOBALS['wp_stubs_user_meta'][16] = [
            'locked'      => [ '1' ],
            'locked_date' => [ '2026-01-15' ],
            'locked_user' => [ '99' ],
        ];

        $GLOBALS['wp_stubs_userdata'][99] = (object) [
            'ID'           => 99,
            'display_name' => 'Locking Admin',
        ];

        $this->hydrator->hydrate( $data );

        $this->assertTrue( $data->locked );
        $this->assertSame( '2026-01-15', $data->locked_date );
        $this->assertSame( '99', $data->locked_user );
        $this->assertSame( 'Locking Admin', $data->locked_user_name );
    }

    public function test_hydrate_locked_meta_when_locked_user_empty(): void {
        $data     = new stdClass();
        $data->ID = 17;

        $GLOBALS['wp_stubs_user_meta'][17] = [
            'locked'      => [ '1' ],
            'locked_date' => [ '2026-01-15' ],
            'locked_user' => [ '' ],
        ];

        $this->hydrator->hydrate( $data );

        $this->assertTrue( $data->locked );
        $this->assertSame( '2026-01-15', $data->locked_date );
        $this->assertSame( '', $data->locked_user );
        $this->assertFalse( property_exists( $data, 'locked_user_name' ) );
    }

    public function test_hydrate_locked_meta_when_locked_user_not_found(): void {
        $data     = new stdClass();
        $data->ID = 18;

        $GLOBALS['wp_stubs_users_not_found'][9999] = true;
        $GLOBALS['wp_stubs_user_meta'][18] = [
            'locked'      => [ '1' ],
            'locked_date' => [ '2026-01-15' ],
            'locked_user' => [ '9999' ],
        ];

        $this->hydrator->hydrate( $data );

        $this->assertTrue( $data->locked );
        $this->assertSame( '', $data->locked_user_name );
    }

    public function test_hydrate_populates_wtn_ratings_and_opt_ins(): void {
        $data     = new stdClass();
        $data->ID = 19;

        $GLOBALS['wp_stubs_user_meta'][19] = [
            'wtn_S'                 => [ '18.5' ],
            'wtn_D'                 => [ '22.1' ],
            'racketmanager_opt_in'  => [ [ 'newsletter', 'sms' ] ],
        ];

        $this->hydrator->hydrate( $data );

        $this->assertIsArray( $data->wtn );
        $this->assertSame( '18.5', $data->wtn['S'] );
        $this->assertSame( '22.1', $data->wtn['D'] );
        $this->assertSame( [ 'newsletter', 'sms' ], $data->opt_ins );
    }

    public function test_individual_hydration_methods_can_be_invoked_directly(): void {
        $data     = new stdClass();
        $data->ID = 20;

        $GLOBALS['wp_stubs_user_meta'][20] = [
            'first_name'  => [ 'Direct' ],
            'last_name'   => [ 'Call' ],
            'gender'      => [ 'M' ],
            'remove_date' => [ '2026-04-01' ],
            'remove_user' => [ 'mod' ],
            'locked'      => [ '0' ],
            'wtn_S'       => [ '15.0' ],
            'wtn_D'       => [ '16.0' ],
        ];

        $this->hydrator->hydrate_basic_meta( $data );
        $this->assertSame( 'Direct', $data->firstname );
        $this->assertSame( 'Call', $data->surname );

        $this->hydrator->hydrate_removal_meta( $data );
        $this->assertSame( '2026-04-01', $data->removed_date );
        $this->assertSame( 'mod', $data->removed_user );

        $this->hydrator->hydrate_locked_meta( $data );
        $this->assertFalse( $data->locked );

        $this->hydrator->hydrate_wtn_meta( $data );
        $this->assertSame( '15.0', $data->wtn['S'] );
    }
}
