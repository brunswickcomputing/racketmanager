<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Value_Objects;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Tournament\Currency;
use Racketmanager\Domain\Value_Objects\Money;

/**
 * @covers \Racketmanager\Domain\Value_Objects\Money
 * @covers \Racketmanager\Domain\Tournament\Currency
 */
final class Money_Test extends TestCase {

    public function test_currency_symbol_and_parsing(): void {
        self::assertSame( '£', Currency::GBP->symbol() );
        self::assertSame( '€', Currency::EUR->symbol() );
        self::assertSame( '$', Currency::USD->symbol() );

        self::assertSame( Currency::GBP, Currency::from_symbol_or_code( '£' ) );
        self::assertSame( Currency::GBP, Currency::from_symbol_or_code( 'gbp' ) );
        self::assertSame( Currency::EUR, Currency::from_symbol_or_code( '€' ) );
        self::assertSame( Currency::USD, Currency::from_symbol_or_code( '$' ) );

        self::assertSame( Currency::GBP, Currency::try_from_symbol_or_code( '£' ) );
        self::assertSame( Currency::EUR, Currency::try_from_symbol_or_code( 'EUR' ) );
        self::assertNull( Currency::try_from_symbol_or_code( 'INVALID' ) );
        self::assertNull( Currency::try_from_symbol_or_code( null ) );
    }

    public function test_create_from_cents_and_float(): void {
        $money = Money::from_cents( 1250, Currency::GBP );
        self::assertSame( 1250, $money->get_cents() );
        self::assertSame( 12.50, $money->get_amount() );
        self::assertSame( '12.50', $money->get_amount_string() );
        self::assertSame( '£12.50', $money->format() );
        self::assertSame( '12.50', $money->format( false ) );
        self::assertSame( '£12.50', (string) $money );

        $float_money = Money::from_float( 9.99, 'USD' );
        self::assertSame( 999, $float_money->get_cents() );
        self::assertSame( Currency::USD, $float_money->get_currency() );
        self::assertSame( '$9.99', $float_money->format() );
    }

    public function test_create_from_string(): void {
        $m1 = Money::from_string( '25.00' );
        self::assertSame( 2500, $m1->get_cents() );

        $m2 = Money::from_string( '£45.50' );
        self::assertSame( 4550, $m2->get_cents() );
        self::assertSame( Currency::GBP, $m2->get_currency() );

        $m3 = Money::from_string( '€1,250.75' );
        self::assertSame( 125075, $m3->get_cents() );
        self::assertSame( Currency::EUR, $m3->get_currency() );

        // Exact decimal parsing without float inaccuracy
        $m_dec1 = Money::from_string( '1.005' );
        self::assertSame( 101, $m_dec1->get_cents() );

        $m_dec2 = Money::from_string( '1.004' );
        self::assertSame( 100, $m_dec2->get_cents() );

        $m_dec3 = Money::from_string( '0.999' );
        self::assertSame( 100, $m_dec3->get_cents() );

        // Negative formats and symbols
        $m_neg1 = Money::from_string( '-£5.00' );
        self::assertSame( -500, $m_neg1->get_cents() );
        self::assertSame( '-£5.00', $m_neg1->format() );

        $m_neg2 = Money::from_string( '£-5.00' );
        self::assertSame( -500, $m_neg2->get_cents() );

        $m_neg3 = Money::from_string( '-5.00', Currency::EUR );
        self::assertSame( -500, $m_neg3->get_cents() );
        self::assertSame( '-€5.00', $m_neg3->format() );

        // European comma decimal
        $m_eur_comma = Money::from_string( '12,50', Currency::EUR );
        self::assertSame( 1250, $m_eur_comma->get_cents() );

        $m_zero = Money::zero();
        self::assertTrue( $m_zero->is_zero() );
        self::assertSame( '£0.00', $m_zero->format() );
    }

    public function test_invalid_string_throws_exception(): void {
        $this->expectException( InvalidArgumentException::class );
        Money::from_string( 'invalid-amount' );
    }

    public function test_scientific_notation_throws_exception(): void {
        $this->expectException( InvalidArgumentException::class );
        Money::from_string( '1.5e2' );
    }

    public function test_from_string_rejects_currency_symbol_mismatch(): void {
        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( 'Currency mismatch' );
        Money::from_string( '£5', Currency::EUR );
    }

    public function test_from_string_rejects_oversized_amount(): void {
        $this->expectException( InvalidArgumentException::class );
        Money::from_string( '999999999999999999999' );
    }

    public function test_null_currency_defaults_to_gbp(): void {
        $money = Money::from_string( '10.00' );
        self::assertSame( Currency::GBP, $money->get_currency() );
        self::assertSame( '£10.00', $money->format() );
    }

    public function test_arithmetic_operations(): void {
        $a = Money::from_cents( 2000, Currency::GBP );
        $b = Money::from_cents( 500, Currency::GBP );

        $sum = $a->add( $b );
        self::assertSame( 2500, $sum->get_cents() );

        $diff = $a->subtract( $b );
        self::assertSame( 1500, $diff->get_cents() );

        $multiplied = $b->multiply( 3 );
        self::assertSame( 1500, $multiplied->get_cents() );

        $divided = $a->divide( 4 );
        self::assertSame( 500, $divided->get_cents() );

        $neg = Money::from_cents( -1000, Currency::GBP );
        self::assertTrue( $neg->is_negative() );
        self::assertFalse( $neg->is_positive() );
        self::assertSame( 1000, $neg->abs()->get_cents() );
    }

    public function test_divide_by_zero_throws_exception(): void {
        $money = Money::from_cents( 1000 );
        $this->expectException( InvalidArgumentException::class );
        $money->divide( 0 );
    }

    public function test_currency_mismatch_throws_exception(): void {
        $gbp = Money::from_cents( 1000, Currency::GBP );
        $eur = Money::from_cents( 1000, Currency::EUR );

        $this->expectException( InvalidArgumentException::class );
        $gbp->add( $eur );
    }

    public function test_comparisons_and_equality(): void {
        $a = Money::from_cents( 1000, Currency::GBP );
        $b = Money::from_cents( 2000, Currency::GBP );
        $c = Money::from_cents( 1000, Currency::GBP );

        self::assertTrue( $a->equals( $c ) );
        self::assertFalse( $a->equals( $b ) );
        self::assertTrue( $b->is_greater_than( $a ) );
        self::assertTrue( $a->is_less_than( $b ) );
    }

    public function test_json_serialization(): void {
        $money = Money::from_cents( 1550, Currency::GBP );
        $json  = $money->jsonSerialize();

        self::assertSame( 1550, $json['cents'] );
        self::assertSame( 15.50, $json['amount'] );
        self::assertSame( 'GBP', $json['currency'] );
        self::assertSame( '£15.50', $json['formatted'] );
    }

    public function test_from_string_rejects_double_sign(): void {
        foreach ( [ '--5', '-£-5', '+-5', '-+5', '£--5' ] as $input ) {
            try {
                Money::from_string( $input );
                self::fail( 'Expected exception for ' . $input );
            } catch ( InvalidArgumentException ) {
                self::assertTrue( true );
            }
        }
        self::assertSame( -500, Money::from_string( '-£5' )->get_cents() );
        self::assertSame( -500, Money::from_string( '£-5' )->get_cents() );
    }

    public function test_from_string_accepts_iso_code_prefix_and_explicit_currency(): void {
        $gbp_code = Money::from_string( 'GBP 5.00' );
        self::assertSame( 500, $gbp_code->get_cents() );
        self::assertSame( Currency::GBP, $gbp_code->get_currency() );

        $eur_code = Money::from_string( 'eur5' );
        self::assertSame( 500, $eur_code->get_cents() );
        self::assertSame( Currency::EUR, $eur_code->get_currency() );

        $positive = Money::from_string( '+5.00' );
        self::assertSame( 500, $positive->get_cents() );

        $usd_explicit = Money::from_string( '7.25', 'usd' );
        self::assertSame( 725, $usd_explicit->get_cents() );
        self::assertSame( Currency::USD, $usd_explicit->get_currency() );

        $blank_explicit = Money::from_string( '3.00', '' );
        self::assertSame( 300, $blank_explicit->get_cents() );
        self::assertSame( Currency::GBP, $blank_explicit->get_currency() );
    }
}
