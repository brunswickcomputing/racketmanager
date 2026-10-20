<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class Aliases_Test extends TestCase {
    public function test_compatibility_aliases_exist(): void {
        $this->assertTrue( class_exists( 'Racketmanager\Domain\User' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Player' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Club' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Tournament' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Team' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Season' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Message' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Results_Report' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Charge' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Invoice' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Club_Player' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\Club_Role' ) );
    }

    public function test_command_aliases_exist(): void {
        $this->assertTrue( class_exists( 'Racketmanager\Domain\DTO\Account\Login_Command' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\DTO\Account\Add_Favourite_Command' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\DTO\Club\Update_Club_Command' ) );
        $this->assertTrue( class_exists( 'Racketmanager\Domain\DTO\Club\Set_Club_Role_Command' ) );
    }
}
