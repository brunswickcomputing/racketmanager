<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Finance;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Finance\Fee_Normaliser;
use Racketmanager\Domain\Tournament\Currency;
use Racketmanager\Domain\Value_Objects\Money;

/**
 * Unit tests for Fee_Normaliser domain utility.
 *
 * @covers \Racketmanager\Domain\Finance\Fee_Normaliser
 */
final class Fee_Normaliser_Test extends TestCase {

    public function test_normalise_returns_null_when_fee_is_null(): void {
        self::assertNull( Fee_Normaliser::normalise( null, 'Competition fee' ) );
        self::assertNull( Fee_Normaliser::normalise( null, 'Event fee' ) );
        self::assertNull( Fee_Normaliser::normalise( null, 'Total fee' ) );
    }

    public function test_normalise_returns_null_when_fee_is_empty_string(): void {
        self::assertNull( Fee_Normaliser::normalise( '', 'Competition fee' ) );
        self::assertNull( Fee_Normaliser::normalise( '', 'Event fee' ) );
        self::assertNull( Fee_Normaliser::normalise( '', 'Total fee' ) );
    }

    #[DataProvider( 'zero_fee_values' )]
    public function test_normalise_handles_zero_values( mixed $zero_value ): void {
        self::assertSame( 0.0, Fee_Normaliser::normalise( $zero_value, 'Fee' ) );
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function zero_fee_values(): array {
        return array(
            'integer zero'        => array( 0 ),
            'float zero'          => array( 0.0 ),
            'string zero'         => array( '0' ),
            'string decimal zero' => array( '0.00' ),
            'money zero'          => array( Money::zero() ),
            'money zero cents'    => array( Money::from_cents( 0, Currency::GBP ) ),
        );
    }

    public function test_normalise_handles_positive_integers(): void {
        self::assertSame( 25.0, Fee_Normaliser::normalise( 25, 'Competition fee' ) );
        self::assertSame( 100.0, Fee_Normaliser::normalise( 100, 'Event fee' ) );
        self::assertSame( 1.0, Fee_Normaliser::normalise( 1, 'Total fee' ) );
    }

    public function test_normalise_handles_positive_floats(): void {
        self::assertSame( 12.25, Fee_Normaliser::normalise( 12.25, 'Competition fee' ) );
        self::assertSame( 12.26, Fee_Normaliser::normalise( 12.255, 'Event fee' ) );
        self::assertSame( 12.25, Fee_Normaliser::normalise( 12.254, 'Total fee' ) );
        self::assertSame( 50.0, Fee_Normaliser::normalise( 50.0, 'Fee' ) );
    }

    public function test_normalise_handles_numeric_strings(): void {
        self::assertSame( 47.5, Fee_Normaliser::normalise( '47.50', 'Competition fee' ) );
        self::assertSame( 15.5, Fee_Normaliser::normalise( '15.5', 'Event fee' ) );
        self::assertSame( 10.0, Fee_Normaliser::normalise( '10', 'Total fee' ) );
        self::assertSame( 101.0, Fee_Normaliser::normalise( '100.999', 'Fee' ) );
    }

    public function test_normalise_handles_money_instances(): void {
        $gbp_money = Money::from_cents( 3000, Currency::GBP );
        self::assertSame( 30.0, Fee_Normaliser::normalise( $gbp_money, 'Competition fee' ) );

        $eur_money = Money::from_cents( 1500, Currency::EUR );
        self::assertSame( 15.0, Fee_Normaliser::normalise( $eur_money, 'Event fee' ) );

        $pence_money = Money::from_cents( 99, Currency::GBP );
        self::assertSame( 0.99, Fee_Normaliser::normalise( $pence_money, 'Total fee' ) );

        $from_float_money = Money::from_float( 45.50, Currency::GBP );
        self::assertSame( 45.5, Fee_Normaliser::normalise( $from_float_money, 'Competition fee' ) );
    }

    #[DataProvider( 'negative_fee_values' )]
    public function test_normalise_negative_fee_throws_invalid_argument_exception( mixed $negative_value ): void {
        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Competition fee must be non-negative.' );

        Fee_Normaliser::normalise( $negative_value, 'Competition fee' );
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function negative_fee_values(): array {
        return array(
            'negative small float'  => array( -0.01 ),
            'negative float'        => array( -10.5 ),
            'negative small int'    => array( -1 ),
            'negative int'          => array( -25 ),
            'negative string'       => array( '-5.50' ),
            'negative small string' => array( '-0.01' ),
            'negative money GBP'    => array( Money::from_cents( -100, Currency::GBP ) ),
            'negative money EUR'    => array( Money::from_cents( -1500, Currency::EUR ) ),
        );
    }

    #[DataProvider( 'label_and_exception_message_provider' )]
    public function test_normalise_exception_message_contains_custom_label( string $label, string $expected_message ): void {
        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( $expected_message );

        Fee_Normaliser::normalise( -5.0, $label );
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function label_and_exception_message_provider(): array {
        return array(
            'competition fee label' => array( 'Competition fee', 'Competition fee must be non-negative.' ),
            'event fee label'       => array( 'Event fee', 'Event fee must be non-negative.' ),
            'total fee label'       => array( 'Total fee', 'Total fee must be non-negative.' ),
            'custom fee label'      => array( 'Custom fee', 'Custom fee must be non-negative.' ),
        );
    }
}
