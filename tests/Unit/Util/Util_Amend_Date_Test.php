<?php

namespace Racketmanager\Tests\Unit\Util;

use PHPUnit\Framework\TestCase;
use Racketmanager\Util\Util;

class Util_Amend_Date_Test extends TestCase {

    public function test_amend_date_with_date_only(): void {
        $result = Util::amend_date( '2026-05-15', 7, '+', 'days' );
        $this->assertSame( '2026-05-22', $result );

        $sub_result = Util::amend_date( '2026-05-15', 2, '-', 'weeks' );
        $this->assertSame( '2026-05-01', $sub_result );
    }

    public function test_amend_date_preserves_time(): void {
        $result = Util::amend_date( '2026-05-15 19:30:00', 2, '+', 'weeks' );
        $this->assertSame( '2026-05-29 19:30:00', $result );

        $result_short = Util::amend_date( '2026-05-15 19:30', 1, '+', 'day' );
        $this->assertSame( '2026-05-16 19:30:00', $result_short );
    }

    public function test_amend_date_with_empty_or_zero_adjustment(): void {
        $this->assertSame( '', Util::amend_date( '', 7 ) );
        $this->assertSame( '2026-05-15', Util::amend_date( '2026-05-15', 0 ) );
    }
}
