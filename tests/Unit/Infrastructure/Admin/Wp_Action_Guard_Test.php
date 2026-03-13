<?php

declare(strict_types=1);

namespace Racketmanager\Tests\Unit\Infrastructure\Admin;

use PHPUnit\Framework\TestCase;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Security\Wp_Action_Guard;

final class Wp_Action_Guard_Test extends TestCase {

    public function test_assert_capability_allows_when_validator_has_no_error(): void {
        $validator = new class {
            public bool $error = false;
            public string $msg = 'should not be used';

            public function capability( string $capability ): object {
                $this->error = false;
                $this->msg   = '';
                return $this;
            }
        };

        $guard = new Wp_Action_Guard(
            static fn (): object => $validator
        );

        $guard->assert_capability( 'edit_matches' );

        self::assertFalse( $validator->error );
    }

    public function test_assert_capability_throws_when_validator_sets_error(): void {
        $validator = new class {
            public bool $error = false;
            public string $msg = '';

            public function capability( string $capability ): object {
                $this->error = true;
                $this->msg   = 'Not allowed';
                return $this;
            }
        };

        $guard = new Wp_Action_Guard(
            static fn (): object => $validator
        );

        $this->expectException( Invalid_Status_Exception::class );
        $this->expectExceptionMessage( 'Not allowed' );

        $guard->assert_capability( 'edit_matches' );
    }
}
