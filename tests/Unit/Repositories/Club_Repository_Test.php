<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

final class Club_Repository_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_find_by_id_returns_hydrated_club_entity(): void {
        $wpdb = $this->createStub( wpdb::class );
        $wpdb->prefix = 'wp_';
        $wpdb->method( 'prepare' )->willReturn( '`id` = 10' );
        $wpdb->method( 'get_row' )->willReturn( (object) [
            'id'         => 10,
            'name'       => 'Leigh Road LTC',
            'shortcode'  => 'LRLTC',
            'type'       => 'affiliated',
            'website'    => 'https://leighroad.example',
            'contactno'  => '01702 000000',
            'founded'    => 1925,
            'facilities' => 'Clubhouse, floodlights',
            'address'    => 'Leigh Road, SS9',
        ] );

        $GLOBALS['wpdb'] = $wpdb;

        $repository = new Club_Repository();
        $club       = $repository->find_by_id( 10 );

        $this->assertInstanceOf( Club::class, $club );
        $this->assertSame( 10, $club->id );
        $this->assertSame( 'Leigh Road LTC', $club->name );
        $this->assertSame( 'LRLTC', $club->shortcode );
        $this->assertSame( 1925, $club->founded );
    }

    public function test_find_all_returns_array_of_hydrated_clubs(): void {
        $wpdb = $this->createStub( wpdb::class );
        $wpdb->prefix = 'wp_';
        $wpdb->method( 'prepare' )->willReturnCallback( function ( $query, ...$args ) {
            return empty( $args ) ? $query : vsprintf( str_replace( '%s', "'%s'", $query ), $args );
        } );
        $wpdb->method( 'get_results' )->willReturn( [
            (object) [ 'id' => 1, 'name' => 'Club 1', 'founded' => 1950 ],
            (object) [ 'id' => 2, 'name' => 'Club 2', 'founded' => 1960 ],
        ] );
        $wpdb->method( 'get_row' )->willReturnCallback( function ( $query ) {
            if ( str_contains( (string) $query, '2' ) ) {
                return (object) [ 'id' => 2, 'name' => 'Club 2', 'shortcode' => 'C2', 'type' => 'affiliated', 'founded' => 1960 ];
            }
            return (object) [ 'id' => 1, 'name' => 'Club 1', 'shortcode' => 'C1', 'type' => 'affiliated', 'founded' => 1950 ];
        } );

        $GLOBALS['wpdb'] = $wpdb;

        $repository = new Club_Repository();
        $clubs      = $repository->find_all();

        $this->assertIsArray( $clubs );
        $this->assertCount( 2, $clubs );
        $this->assertInstanceOf( Club::class, $clubs[0] );
        $this->assertInstanceOf( Club::class, $clubs[1] );
        $this->assertSame( 'Club 1', $clubs[0]->name );
        $this->assertSame( 'Club 2', $clubs[1]->name );
    }
}
