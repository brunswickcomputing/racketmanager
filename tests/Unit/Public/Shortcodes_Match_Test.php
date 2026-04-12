<?php
declare( strict_types=1 );

namespace Racketmanager\Public {
    function shortcode_atts( array $pairs, array $atts, string $shortcode = '' ): array {
        return array_merge( $pairs, $atts );
    }
}

namespace Racketmanager\Tests\Unit\Public {

    use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
    use PHPUnit\Framework\TestCase;
    use Racketmanager\Domain\Competition\Competition;
    use Racketmanager\Domain\Competition\Event;
    use Racketmanager\Domain\Competition\League;
    use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
    use Racketmanager\Domain\Fixture\Fixture;
    use Racketmanager\Public\Shortcodes_Match;
    use Racketmanager\RacketManager;
    use Racketmanager\Services\Club_Service;
    use Racketmanager\Services\Competition_Entry_Service;
    use Racketmanager\Services\Competition_Service;
    use Racketmanager\Services\Container\Simple_Container;
    use Racketmanager\Services\Finance_Service;
    use Racketmanager\Services\Fixture\Fixture_Detail_Service;
    use Racketmanager\Services\Fixture_Service;
    use Racketmanager\Services\Player_Service;
    use Racketmanager\Services\Registration_Service;
    use Racketmanager\Services\Team_Service;
    use Racketmanager\Services\Tournament_Service;

    #[AllowMockObjectsWithoutExpectations]
    class Shortcodes_Match_Test extends TestCase {

        private $plugin;
        private $container;
        private $fixture_detail_service;
        private $shortcode;

        public function test_show_match_option_modal_uses_dto(): void {
            $match_id = 123;
            $match    = $this->createMock( Fixture::class );
            $dto      = new Fixture_Details_DTO( $match, $this->createStub( League::class ), $this->createStub( Event::class ), $this->createStub( Competition::class ) );

            $this->fixture_detail_service->expects( $this->once() )->method( 'get_fixture_with_details' )->with( $match_id )->willReturn( $dto );

            $this->shortcode->expects( $this->once() )->method( 'load_template' )->with( $this->equalTo( 'match-option-modal' ), $this->callback( function ( $args ) use ( $dto, $match ) {
                    return $args['dto'] === $dto && $args['match'] === $match;
                } ), $this->equalTo( 'match' ) )->willReturn( 'template_content' );

            $result = $this->shortcode->show_match_option_modal( [ 'match_id' => $match_id, 'option' => 'schedule_match' ] );
            $this->assertEquals( 'template_content', $result );
        }

        public function test_show_match_status_modal_uses_dto(): void {
            $match_id = 123;
            $match    = $this->createMock( Fixture::class );
            $match->method( 'get_home_team' )->willReturn( '1' );
            $match->method( 'get_away_team' )->willReturn( '2' );

            $competition                  = $this->createStub( Competition::class );
            $competition->is_player_entry = true;
            $competition->is_team_entry   = false;

            $dto = new Fixture_Details_DTO( $match, $this->createStub( League::class ), $this->createStub( Event::class ), $competition );

            $this->fixture_detail_service->expects( $this->once() )->method( 'get_fixture_with_details' )->with( $match_id )->willReturn( $dto );

            $this->shortcode->expects( $this->once() )->method( 'load_template' )->willReturn( 'template_content' );

            $result = $this->shortcode->show_match_status_modal( [ 'match_id' => $match_id ] );
            $this->assertEquals( 'template_content', $result );
        }

        public function test_show_match_card_uses_dto(): void {
            $match_id            = 123;
            $match               = $this->createMock( Fixture::class );
            $league              = $this->createStub( League::class );
            $league->num_rubbers = 9;

            $dto = new Fixture_Details_DTO( $match, $league, $this->createStub( Event::class ), $this->createStub( Competition::class ) );

            $this->fixture_detail_service->expects( $this->once() )->method( 'get_fixture_with_details' )->with( $match_id )->willReturn( $dto );

            $match->expects( $this->once() )->method( 'get_rubbers' )->willReturn( [] );

            $this->shortcode->expects( $this->once() )->method( 'load_template' )->with( $this->equalTo( 'match-card-rubbers' ), $this->callback( function ( $args ) use ( $dto, $match ) {
                    return $args['dto'] === $dto && $args['match'] === $match;
                } ), $this->equalTo( 'match' ) )->willReturn( 'template_content' );

            $result = $this->shortcode->show_match_card( [ 'id' => $match_id ] );
            $this->assertEquals( 'template_content', $result );
        }

        protected function setUp(): void {
            $this->plugin            = $this->createMock( RacketManager::class );
            $this->container         = $this->createMock( Simple_Container::class );
            $this->plugin->container = $this->container;

            $this->fixture_detail_service = $this->createMock( Fixture_Detail_Service::class );

            $this->container->method( 'get' )->willReturnMap( [
                [ 'fixture_detail_service', $this->fixture_detail_service ],
                [ 'competition_service', $this->createMock( Competition_Service::class ) ],
                [ 'club_service', $this->createMock( Club_Service::class ) ],
                [ 'finance_service', $this->createMock( Finance_Service::class ) ],
                [ 'player_service', $this->createMock( Player_Service::class ) ],
                [ 'registration_service', $this->createMock( Registration_Service::class ) ],
                [ 'team_service', $this->createMock( Team_Service::class ) ],
                [ 'tournament_service', $this->createMock( Tournament_Service::class ) ],
                [ 'competition_entry_service', $this->createMock( Competition_Entry_Service::class ) ],
                [ 'fixture_service', $this->createMock( Fixture_Service::class ) ],
            ] );

            $this->shortcode = $this->getMockBuilder( Shortcodes_Match::class )->setConstructorArgs( [ $this->plugin ] )->onlyMethods( [ 'load_template', 'return_error' ] )->getMock();
        }
    }
}
