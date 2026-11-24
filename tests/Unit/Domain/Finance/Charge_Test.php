<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Finance;

use Error;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Finance\Charge_Hydration_DTO;
use Racketmanager\Domain\Enums\Charge_Status;
use Racketmanager\Domain\Finance\Charge;
use Racketmanager\Domain\Finance\Charge_Factory;
use Racketmanager\Domain\Tournament\Currency;
use Racketmanager\Domain\Value_Objects\Money;
use Racketmanager\Repositories\Charge_Repository;
use Racketmanager\Tests\Helpers\Manages_Wordpress_Globals;
use stdClass;
use wpdb;

final class Charge_Test extends TestCase {
    use Manages_Wordpress_Globals;

    protected function setUp(): void {
        parent::setUp();
        $this->backup_globals();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    public function test_default_state_initializes_cleanly_without_uninitialized_property_errors(): void {
        $charge = new Charge();

        self::assertNull( $charge->id );
        self::assertNull( $charge->season );
        self::assertNull( $charge->competition_id );
        self::assertNull( $charge->competition );
        self::assertSame( 'draft', $charge->status );
        self::assertNull( $charge->date );
        self::assertNull( $charge->fee_competition );
        self::assertNull( $charge->fee_event );
        self::assertNull( $charge->total );

        self::assertNull( $charge->get_id() );
        self::assertNull( $charge->get_season() );
        self::assertNull( $charge->get_competition_id() );
        self::assertNull( $charge->get_competition() );
        self::assertSame( 'draft', $charge->get_status() );
        self::assertSame( Charge_Status::DRAFT, $charge->get_status_enum() );
        self::assertTrue( $charge->is_draft() );
        self::assertFalse( $charge->is_final() );
        self::assertNull( $charge->get_date() );
        self::assertNull( $charge->get_fee_competition() );
        self::assertNull( $charge->get_fee_event() );
        self::assertNull( $charge->get_total() );
        self::assertNull( $charge->get_competition_fee_money() );
        self::assertNull( $charge->get_event_fee_money() );
        self::assertNull( $charge->get_total_money() );
    }

    public function test_asymmetric_visibility_allows_reading_all_properties(): void {
        $dto = new Charge_Hydration_DTO(
            id: 101,
            competition_id: 5,
            season: '2026',
            status: 'final',
            date: '2026-06-01',
            fee_competition: 50.0,
            fee_event: 25.0,
            total: 75.0,
        );
        $charge = new Charge( $dto );
        $competition = (object) array( 'id' => 5, 'name' => 'Summer League' );
        $charge->set_competition( $competition );

        self::assertSame( 101, $charge->id );
        self::assertSame( '2026', $charge->season );
        self::assertSame( 5, $charge->competition_id );
        self::assertSame( $competition, $charge->competition );
        self::assertSame( 'final', $charge->status );
        self::assertSame( '2026-06-01', $charge->date );
        self::assertSame( 50.0, $charge->fee_competition );
        self::assertSame( 25.0, $charge->fee_event );
        self::assertSame( 75.0, $charge->total );
    }

    #[DataProvider( 'charge_property_mutations' )]
    public function test_asymmetric_visibility_forbids_external_mutation( string $property, mixed $value ): void {
        $charge = new Charge();

        $this->expectException( Error::class );
        $this->expectExceptionMessage( sprintf( 'Cannot modify private(set) property %s::$%s', Charge::class, $property ) );

        $charge->{$property} = $value;
    }

    /**
     * @return array<string, array{0: string, 1: mixed}>
     */
    public static function charge_property_mutations(): array {
        return array(
            'id'              => array( 'id', 1 ),
            'season'          => array( 'season', '2026' ),
            'competition_id'  => array( 'competition_id', 2 ),
            'competition'     => array( 'competition', new stdClass() ),
            'status'          => array( 'status', 'final' ),
            'date'            => array( 'date', '2026-01-01' ),
            'fee_competition' => array( 'fee_competition', 20.0 ),
            'fee_event'       => array( 'fee_event', 10.0 ),
            'total'           => array( 'total', 30.0 ),
        );
    }

    public function test_typed_getters_and_setters(): void {
        $charge = new Charge();

        $charge->set_id( 42 );
        self::assertSame( 42, $charge->get_id() );

        $charge->set_competition_id( 99 );
        self::assertSame( 99, $charge->get_competition_id() );

        $competition = (object) array( 'id' => 99 );
        $charge->set_competition( $competition );
        self::assertSame( $competition, $charge->get_competition() );

        $charge->set_season( '2026/2027' );
        self::assertSame( '2026/2027', $charge->get_season() );

        $charge->set_date( '2026-09-01' );
        self::assertSame( '2026-09-01', $charge->get_date() );

        $charge->set_fee_competition( 35.50 );
        self::assertSame( 35.50, $charge->get_fee_competition() );

        $charge->set_fee_event( 12.25 );
        self::assertSame( 12.25, $charge->get_fee_event() );

        $charge->set_total( 47.75 );
        self::assertSame( 47.75, $charge->get_total() );

        $charge->set_competition( null );
        self::assertNull( $charge->get_competition() );

        $charge->set_fee_competition( null );
        self::assertNull( $charge->get_fee_competition() );

        $charge->set_fee_event( '' );
        self::assertNull( $charge->get_fee_event() );

        $charge->set_total( null );
        self::assertNull( $charge->get_total() );
    }

    public function test_status_transitions_and_enum_integration(): void {
        $charge = new Charge();

        $charge->set_status( Charge_Status::FINAL );
        self::assertSame( 'final', $charge->get_status() );
        self::assertSame( Charge_Status::FINAL, $charge->get_status_enum() );
        self::assertTrue( $charge->is_final() );
        self::assertFalse( $charge->is_draft() );

        $charge->set_status( 'draft' );
        self::assertSame( 'draft', $charge->get_status() );
        self::assertSame( Charge_Status::DRAFT, $charge->get_status_enum() );
        self::assertTrue( $charge->is_draft() );
        self::assertFalse( $charge->is_final() );

        $charge->set_status_enum( Charge_Status::FINAL );
        self::assertSame( 'final', $charge->get_status() );
        self::assertSame( Charge_Status::FINAL, $charge->get_status_enum() );

        $charge->set_status( 'unknown_state' );
        self::assertSame( 'unknown_state', $charge->get_status() );
        self::assertNull( $charge->get_status_enum() );
        self::assertFalse( $charge->is_draft() );
        self::assertFalse( $charge->is_final() );
    }

    public function test_money_and_currency_calculations(): void {
        $charge = new Charge();
        $charge->set_fee_competition( 50.0 );
        $charge->set_fee_event( 25.0 );

        $comp_money = $charge->get_competition_fee_money();
        self::assertNotNull( $comp_money );
        self::assertSame( 5000, $comp_money->get_cents() );
        self::assertSame( '£50.00', $comp_money->format() );

        $event_money = $charge->get_event_fee_money( Currency::EUR );
        self::assertNotNull( $event_money );
        self::assertSame( 2500, $event_money->get_cents() );
        self::assertSame( '€25.00', $event_money->format() );

        $total_money = $charge->get_total_money();
        self::assertNotNull( $total_money );
        self::assertSame( 7500, $total_money->get_cents() );
        self::assertSame( '£75.00', $total_money->format() );

        $charge->set_fee_competition( Money::from_cents( 3000, Currency::GBP ) );
        self::assertSame( 30.0, $charge->get_fee_competition() );

        $charge->set_fee_event( Money::from_cents( 1500, Currency::GBP ) );
        self::assertSame( 15.0, $charge->get_fee_event() );

        // Only competition fee set
        $charge->set_fee_event( null );
        $partial_comp = $charge->get_total_money();
        self::assertNotNull( $partial_comp );
        self::assertSame( 3000, $partial_comp->get_cents() );

        // Only event fee set
        $charge->set_fee_competition( null );
        $charge->set_fee_event( 15.0 );
        $partial_event = $charge->get_total_money();
        self::assertNotNull( $partial_event );
        self::assertSame( 1500, $partial_event->get_cents() );
    }

    public function test_fee_normalization_and_invariants(): void {
        $charge = new Charge();

        $charge->set_fee_competition( '40.00' );
        self::assertSame( 40.0, $charge->get_fee_competition() );

        $charge->set_fee_competition( 0 );
        self::assertSame( 0.0, $charge->get_fee_competition() );

        $charge->set_fee_event( '15.5' );
        self::assertSame( 15.5, $charge->get_fee_event() );

        $charge->set_total( '55.50' );
        self::assertSame( 55.5, $charge->get_total() );
    }

    #[DataProvider( 'negative_fee_values' )]
    public function test_negative_competition_fee_throws_invalid_argument_exception( mixed $negative_value ): void {
        $charge = new Charge();

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Competition fee must be non-negative.' );

        $charge->set_fee_competition( $negative_value );
    }

    #[DataProvider( 'negative_fee_values' )]
    public function test_negative_event_fee_throws_invalid_argument_exception( mixed $negative_value ): void {
        $charge = new Charge();

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Event fee must be non-negative.' );

        $charge->set_fee_event( $negative_value );
    }

    #[DataProvider( 'negative_fee_values' )]
    public function test_negative_total_fee_throws_invalid_argument_exception( mixed $negative_value ): void {
        $charge = new Charge();

        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Total fee must be non-negative.' );

        $charge->set_total( $negative_value );
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function negative_fee_values(): array {
        return array(
            'negative float'  => array( -0.01 ),
            'negative int'    => array( -10 ),
            'negative string' => array( '-5.50' ),
            'negative money'  => array( Money::from_cents( -100, Currency::GBP ) ),
        );
    }

    public function test_factory_and_dto_round_trip(): void {
        $raw = (object) array(
            'id'              => '12',
            'competition_id'  => '3',
            'season'          => '2026',
            'status'          => 'final',
            'date'            => '2026-05-15',
            'fee_competition' => '45.00',
            'fee_event'       => '15.00',
            'total'           => '60.00',
        );

        $from_object = Charge::from_object( $raw );
        self::assertSame( 12, $from_object->get_id() );
        self::assertSame( 3, $from_object->get_competition_id() );
        self::assertSame( '2026', $from_object->get_season() );
        self::assertSame( 'final', $from_object->get_status() );
        self::assertSame( '2026-05-15', $from_object->get_date() );
        self::assertSame( 45.0, $from_object->get_fee_competition() );
        self::assertSame( 15.0, $from_object->get_fee_event() );
        self::assertSame( 60.0, $from_object->get_total() );

        $raw_array = array(
            'id'              => 12,
            'competition_id'  => 3,
            'season'          => '2026',
            'status'          => 'final',
            'date'            => '2026-05-15',
            'fee_competition' => 45.0,
            'fee_event'       => 15.0,
            'total'           => 60.0,
        );
        $from_array = Charge::from_array( $raw_array );
        self::assertSame( 12, $from_array->get_id() );
        self::assertSame( 3, $from_array->get_competition_id() );
        self::assertSame( '2026', $from_array->get_season() );
        self::assertSame( 'final', $from_array->get_status() );
        self::assertSame( '2026-05-15', $from_array->get_date() );
        self::assertSame( 45.0, $from_array->get_fee_competition() );
        self::assertSame( 15.0, $from_array->get_fee_event() );
        self::assertSame( 60.0, $from_array->get_total() );
        self::assertEquals( $from_object, $from_array );

        $dto = Charge_Hydration_DTO::from_object( $raw );
        $from_dto = Charge_Factory::create_from_dto( $dto );
        self::assertSame( 12, $from_dto->get_id() );
        self::assertSame( '2026-05-15', $from_dto->get_date() );
        self::assertSame( 60.0, $from_dto->get_total() );
        self::assertEquals( $from_object, $from_dto );
    }

    public function test_repository_save_uses_typed_getters_and_hydrates_id(): void {
        $wpdb = $this->createMock( wpdb::class );
        $wpdb->prefix = 'wp_';
        $wpdb->insert_id = 999;

        // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        $GLOBALS['wpdb'] = $wpdb;

        $wpdb->expects( self::once() )
            ->method( 'insert' )
            ->with(
                'wp_racketmanager_charges',
                array(
                    'competition_id'  => 5,
                    'season'          => '2026',
                    'date'            => '2026-06-01',
                    'status'          => 'draft',
                    'fee_competition' => 50.0,
                    'fee_event'       => 20.0,
                ),
                array( '%d', '%s', '%s', '%s', '%d', '%d' )
            )
            ->willReturn( 1 );

        $charge = new Charge();
        $charge->set_competition_id( 5 );
        $charge->set_season( '2026' );
        $charge->set_date( '2026-06-01' );
        $charge->set_fee_competition( 50.0 );
        $charge->set_fee_event( 20.0 );

        $repo = new Charge_Repository();
        $result = $repo->save( $charge );

        self::assertTrue( $result );
        self::assertSame( 999, $charge->get_id() );

        // Test update path
        $wpdb->expects( self::once() )
            ->method( 'update' )
            ->with(
                'wp_racketmanager_charges',
                array(
                    'competition_id'  => 5,
                    'season'          => '2026',
                    'date'            => '2026-06-01',
                    'status'          => 'final',
                    'fee_competition' => 50.0,
                    'fee_event'       => 20.0,
                ),
                array( 'id' => 999 ),
                array( '%d', '%s', '%s', '%s', '%d', '%d' ),
                array( '%d' )
            )
            ->willReturn( 1 );

        $charge->set_status( 'final' );
        $update_result = $repo->save( $charge );
        self::assertSame( 1, $update_result );
    }
}
