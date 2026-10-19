<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\DTO;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Tournament\Tournament_Invoice_Details_DTO;

class Tournament_Invoice_Details_DTO_Test extends TestCase {
    public function test_fractional_fees_keep_precision_and_float_contract(): void {
        $dto = new Tournament_Invoice_Details_DTO( 1, 'P', [ 'a', 'b' ], 12.50, 5.25, 10.0 );

        $this->assertSame( 25.0, $dto->fee_events );
        $this->assertSame( 5.25, $dto->fee_competition );
        $this->assertSame( 30.25, $dto->fee );
        $this->assertSame( 20.25, $dto->total );
    }

    public function test_null_fees_default_to_zero(): void {
        $dto = new Tournament_Invoice_Details_DTO( 1, 'P', [ 'a' ], null, null );

        $this->assertNull( $dto->fee_competition );
        $this->assertSame( 0.0, $dto->fee );
        $this->assertSame( 0.0, $dto->total );
    }
}
