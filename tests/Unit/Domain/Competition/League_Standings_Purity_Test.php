<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Competition;

use Racketmanager\Tests\Helpers\League_Test_Factory;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\League;
use ReflectionClass;

final class League_Standings_Purity_Test extends TestCase {
    public function test_league_has_no_standings_collaborator_or_workflow_methods(): void {
        $reflection = new ReflectionClass( League::class );
        $this->assertFalse( $reflection->hasProperty( 'standings_service' ) );
        foreach ( [ 'set_standings_service', 'get_standings_service', 'get_standings', 'update_standings', 'set_teams_rank', 'save_standings_manually', 'save_standings', 'calculate_points', 'is_tie', 'get_standings_data' ] as $method ) {
            $this->assertFalse( $reflection->hasMethod( $method ), $method );
        }
    }

    public function test_standings_configuration_survives_serialization_without_services(): void {
        $league = League_Test_Factory::from_object( (object) [ 'title' => 'Division 1', 'standings' => [ 'points' => 1 ] ] );
        $restored = unserialize( serialize( $league ) );

        $this->assertSame( $league->standings, $restored->standings );
        $this->assertSame( 'Division 1', $restored->get_name() );
        $this->assertArrayNotHasKey( 'standings_service', $restored->__serialize() );
    }
}