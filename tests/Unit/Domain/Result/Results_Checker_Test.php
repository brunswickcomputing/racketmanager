<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Domain\Result;

use Error;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\DTO\Result\Results_Checker_Data;
use Racketmanager\Domain\Enums\Results_Checker_Status;
use Racketmanager\Domain\Result\Results_Checker;

final class Results_Checker_Test extends TestCase {

    public function test_default_constructor_initialises_expected_defaults(): void {
        $checker = new Results_Checker();

        $this->assertSame( 0, $checker->id );
        $this->assertSame( 0, $checker->get_id() );
        $this->assertSame( 0, $checker->match_id );
        $this->assertSame( 0, $checker->get_match_id() );
        $this->assertSame( 0, $checker->team_id );
        $this->assertSame( 0, $checker->get_team_id() );
        $this->assertNull( $checker->player_id );
        $this->assertNull( $checker->get_player_id() );
        $this->assertSame( 0, $checker->league_id );
        $this->assertSame( 0, $checker->get_league_id() );
        $this->assertNull( $checker->rubber_id );
        $this->assertNull( $checker->get_rubber_id() );
        $this->assertNull( $checker->status );
        $this->assertNull( $checker->get_status() );
        $this->assertSame( '', $checker->description );
        $this->assertSame( '', $checker->get_description() );
        $this->assertNull( $checker->updated_date );
        $this->assertNull( $checker->get_updated_date() );
        $this->assertNull( $checker->updated_user );
        $this->assertNull( $checker->get_updated_user() );
        $this->assertTrue( $checker->is_outstanding() );
        $this->assertFalse( $checker->is_approved() );
        $this->assertFalse( $checker->is_handled() );
        $this->assertFalse( $checker->is_player_error() );
        $this->assertTrue( $checker->is_match_error() );
    }

    public function test_constructor_with_dto_hydrates_properties(): void {
        $dto = new Results_Checker_Data(
            id: 10,
            league_id: 20,
            match_id: 30,
            team_id: 40,
            player_id: 50,
            rubber_id: 60,
            description: 'Unregistered player',
            status: Results_Checker_Status::APPROVED->value,
            updated_date: '2026-11-22 12:00:00',
            updated_user: 99
        );

        $checker = new Results_Checker( $dto );

        $this->assertSame( 10, $checker->get_id() );
        $this->assertSame( 20, $checker->get_league_id() );
        $this->assertSame( 30, $checker->get_match_id() );
        $this->assertSame( 40, $checker->get_team_id() );
        $this->assertSame( 50, $checker->get_player_id() );
        $this->assertSame( 60, $checker->get_rubber_id() );
        $this->assertSame( 'Unregistered player', $checker->get_description() );
        $this->assertSame( Results_Checker_Status::APPROVED->value, $checker->get_status() );
        $this->assertSame( '2026-11-22 12:00:00', $checker->get_updated_date() );
        $this->assertSame( 99, $checker->get_updated_user() );
        $this->assertTrue( $checker->is_approved() );
        $this->assertFalse( $checker->is_handled() );
        $this->assertFalse( $checker->is_outstanding() );
        $this->assertTrue( $checker->is_player_error() );
        $this->assertFalse( $checker->is_match_error() );
    }

    public function test_setters_mutate_internal_state(): void {
        $checker = new Results_Checker();

        $checker->set_id( 1 );
        $checker->set_match_id( 2 );
        $checker->set_team_id( 3 );
        $checker->set_player_id( 4 );
        $checker->set_league_id( 5 );
        $checker->set_rubber_id( 6 );
        $checker->set_status( 1 );
        $checker->set_description( 'Late result' );
        $checker->set_updated_date( '2026-11-22' );
        $checker->set_updated_user( 7 );

        $this->assertSame( 1, $checker->get_id() );
        $this->assertSame( 2, $checker->get_match_id() );
        $this->assertSame( 3, $checker->get_team_id() );
        $this->assertSame( 4, $checker->get_player_id() );
        $this->assertSame( 5, $checker->get_league_id() );
        $this->assertSame( 6, $checker->get_rubber_id() );
        $this->assertSame( 1, $checker->get_status() );
        $this->assertSame( 'Late result', $checker->get_description() );
        $this->assertSame( '2026-11-22', $checker->get_updated_date() );
        $this->assertSame( 7, $checker->get_updated_user() );
    }

    public function test_approve_marks_status_approved_and_sets_user(): void {
        $checker = new Results_Checker();
        $this->assertTrue( $checker->is_outstanding() );

        $checker->approve( 42 );

        $this->assertSame( Results_Checker_Status::APPROVED->value, $checker->get_status() );
        $this->assertSame( 42, $checker->get_updated_user() );
        $this->assertTrue( $checker->is_approved() );
        $this->assertFalse( $checker->is_handled() );
        $this->assertFalse( $checker->is_outstanding() );
    }

    public function test_handle_marks_status_handled_and_sets_user(): void {
        $checker = new Results_Checker();
        $this->assertTrue( $checker->is_outstanding() );

        $checker->handle( 84 );

        $this->assertSame( Results_Checker_Status::HANDLED->value, $checker->get_status() );
        $this->assertSame( 84, $checker->get_updated_user() );
        $this->assertTrue( $checker->is_handled() );
        $this->assertFalse( $checker->is_approved() );
        $this->assertFalse( $checker->is_outstanding() );
    }

    public function test_is_outstanding_when_status_is_explicitly_zero(): void {
        $checker = new Results_Checker();
        $checker->set_status( Results_Checker_Status::OUTSTANDING->value );

        $this->assertTrue( $checker->is_outstanding() );
        $this->assertFalse( $checker->is_approved() );
        $this->assertFalse( $checker->is_handled() );
    }

    public function test_player_vs_match_error_detection(): void {
        $match_error = new Results_Checker();
        $this->assertTrue( $match_error->is_match_error() );
        $this->assertFalse( $match_error->is_player_error() );

        $player_error = new Results_Checker();
        $player_error->set_player_id( 123 );
        $this->assertFalse( $player_error->is_match_error() );
        $this->assertTrue( $player_error->is_player_error() );
    }

    public function test_direct_property_mutation_is_forbidden_by_private_set(): void {
        $checker = new Results_Checker();

        $this->expectException( Error::class );
        // Direct modification of public private(set) property must fail
        $checker->id = 999; // @phpstan-ignore-line
    }
}
