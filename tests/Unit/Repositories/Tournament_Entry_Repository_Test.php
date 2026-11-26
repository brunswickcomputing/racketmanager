<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Repositories;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Tournament\Currency;
use Racketmanager\Domain\Tournament\Tournament_Entry;
use Racketmanager\Domain\Value_Objects\Money;
use Racketmanager\Repositories\Tournament_Entry_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use wpdb;

#[AllowMockObjectsWithoutExpectations]
class Tournament_Entry_Repository_Test extends TestCase {
    use Manages_Wordpress_Globals;

    private Tournament_Entry_Repository $repository;
    private $wpdb;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
        $this->wpdb = $this->createMock( wpdb::class );
        $this->wpdb->prefix = 'wp_';
        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $GLOBALS['wpdb'] = $this->wpdb;
        $this->repository = new Tournament_Entry_Repository();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_find_by_id_returns_null_for_empty_id(): void {
        $this->assertNull( $this->repository->find_by_id( null ) );
        $this->assertNull( $this->repository->find_by_id( 0 ) );
    }

    public function test_find_by_id_returns_cached_entry(): void {
        $entry = Tournament_Entry::from_object(
            (object) [
                'id'            => 10,
                'tournament_id' => 1,
                'player_id'     => 2,
                'status'        => 0,
                'fee'           => null,
                'club_id'       => null,
            ]
        );
        $GLOBALS['wp_stubs_cache_enabled'] = true;
        $GLOBALS['wp_stubs_cache']['tournament_entries']['10'] = $entry;

        $result = $this->repository->find_by_id( 10 );
        $this->assertEquals( $entry, $result );
    }

    public function test_save_new_entry_inserts_and_sets_id(): void {
        $entry = new Tournament_Entry();
        $entry->set_tournament_id( 5 );
        $entry->set_player_id( 15 );
        $entry->set_status( 1 );
        $entry->set_fee( 15 );

        $this->wpdb->insert_id = 77;
        $this->wpdb->expects( $this->once() )
            ->method( 'insert' )
            ->with(
                'wp_racketmanager_tournament_entries',
                [
                    'tournament_id' => 5,
                    'player_id'     => 15,
                    'status'        => 1,
                    'fee'           => '15.00',
                    'club_id'       => null,
                ],
                [ '%d', '%d', '%d', '%s', '%d' ]
            )
            ->willReturn( 1 );

        $result = $this->repository->save( $entry );
        $this->assertSame( 77, $result );
        $this->assertSame( 77, $entry->get_id() );
    }

    public function test_save_entry_with_fractional_fee_persists_exact_decimal(): void {
        $entry = new Tournament_Entry();
        $entry->set_tournament_id( 5 );
        $entry->set_player_id( 16 );
        $entry->set_status( 2 );
        $entry->set_fee_money( Money::from_string( '15.50', Currency::GBP ) );

        $this->wpdb->insert_id = 78;
        $this->wpdb->expects( $this->once() )
            ->method( 'insert' )
            ->with(
                'wp_racketmanager_tournament_entries',
                [
                    'tournament_id' => 5,
                    'player_id'     => 16,
                    'status'        => 2,
                    'fee'           => '15.50',
                    'club_id'       => null,
                ],
                [ '%d', '%d', '%d', '%s', '%d' ]
            )
            ->willReturn( 1 );

        $result = $this->repository->save( $entry );
        $this->assertSame( 78, $result );
    }

    public function test_save_formats_fee_independently_of_locale_on_insert_and_update(): void {
        $previous = setlocale( LC_NUMERIC, 0 );
        setlocale( LC_NUMERIC, 'de_DE.UTF-8', 'de_DE', 'fr_FR.UTF-8', 'fr_FR' );

        try {
            $expected = [
                'tournament_id' => 5,
                'player_id'     => 17,
                'status'        => 2,
                'fee'           => '15.50',
                'club_id'       => 3,
            ];
            $format   = [ '%d', '%d', '%d', '%s', '%d' ];

            $insert_entry = Tournament_Entry::create( 5, 17, 2, Money::from_string( '15.50', Currency::GBP ), 3 );
            $this->wpdb->insert_id = 79;
            $this->wpdb->expects( $this->once() )
                ->method( 'insert' )
                ->with( 'wp_racketmanager_tournament_entries', $expected, $format )
                ->willReturn( 1 );
            $this->assertSame( 79, $this->repository->save( $insert_entry ) );

            $update_entry = Tournament_Entry::from_object(
                (object) [
                    'id'            => '80',
                    'tournament_id' => '5',
                    'player_id'     => '17',
                    'status'        => '2',
                    'fee'           => '15.50',
                    'club_id'       => '3',
                ]
            );
            $this->wpdb->expects( $this->once() )
                ->method( 'update' )
                ->with( 'wp_racketmanager_tournament_entries', $expected, [ 'id' => 80 ], $format, [ '%d' ] )
                ->willReturn( 1 );
            $this->assertSame( 1, $this->repository->save( $update_entry ) );
        } finally {
            setlocale( LC_NUMERIC, false === $previous ? 'C' : $previous );
        }
    }

    public function test_save_entry_without_fee_uses_integer_format_and_null_value(): void {
        $entry = Tournament_Entry::create( 5, 18, 0 );

        $this->wpdb->insert_id = 81;
        $this->wpdb->expects( $this->once() )
            ->method( 'insert' )
            ->with(
                'wp_racketmanager_tournament_entries',
                [
                    'tournament_id' => 5,
                    'player_id'     => 18,
                    'status'        => 0,
                    'fee'           => null,
                    'club_id'       => null,
                ],
                [ '%d', '%d', '%d', '%d', '%d' ]
            )
            ->willReturn( 1 );

        $this->assertSame( 81, $this->repository->save( $entry ) );
    }

    public function test_delete_removes_entry(): void {
        $this->wpdb->expects( $this->once() )
            ->method( 'delete' )
            ->willReturn( 1 );

        $this->assertTrue( $this->repository->delete( 77 ) );
    }
}
