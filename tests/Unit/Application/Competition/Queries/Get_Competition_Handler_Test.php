<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Competition\Queries;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Competition\Queries\Get_Competition_Handler;
use Racketmanager\Application\Competition\Queries\Get_Competition_Query;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Infrastructure\Security\Security_Service_Interface;
use Racketmanager\Presentation\View_Models\Competition_View_Model;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Settings_Service;
use Racketmanager\Tests\Helpers\Competition_Test_Factory;
use Racketmanager\Tests\Helpers\Creates_Competition_Season_Service;

class Get_Competition_Handler_Test extends TestCase {
    use Creates_Competition_Season_Service;
    public function test_handle_when_competition_open_and_logged_in_as_admin(): void {
        $competition = Competition_Test_Factory::create(
            name: 'Summer League',
            seasons: Competition_Test_Factory::season_list( '2026', [
                'date_open'    => '2020-01-01',
                'date_closing' => '2099-01-01',
            ] )
        );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );

        $club_service = $this->createStub( Club_Service::class );
        $club_service->method( 'get_clubs' )->willReturn( [ (object) [ 'shortcode' => 'WTC' ] ] );

        $player_service = $this->createStub( Player_Service::class );

        $security = $this->createStub( Security_Service_Interface::class );
        $security->method( 'is_user_logged_in' )->willReturn( true );
        $security->method( 'get_current_user_id' )->willReturn( 10 );
        $security->method( 'current_user_can' )->willReturnCallback( fn( string $cap ) => 'manage_racketmanager' === $cap );

        $settings = new Settings_Service( [] );

        $handler = new Get_Competition_Handler(
            $comp_service,
            $club_service,
            $player_service,
            $security,
            $settings,
            $this->competition_season_service()
        );

        $query = new Get_Competition_Query( competition_id: 1, season: '2026' );
        $vm    = $handler->handle( $query );

        $this->assertInstanceOf( Competition_View_Model::class, $vm );
        $this->assertStringContainsString( '/entry-form/summer-league/2026/wtc/', $competition->entry_link );
    }

    public function test_handle_when_competition_open_and_logged_in_as_player(): void {
        $competition = Competition_Test_Factory::create(
            name: 'Autumn League',
            type: Competition_Type::LEAGUE,
            seasons: Competition_Test_Factory::season_list( '2026', [
                'date_open'    => '2020-01-01',
                'date_closing' => '2099-01-01',
            ] )
        );

        $comp_service = $this->createStub( Competition_Service::class );
        $comp_service->method( 'get_by_id' )->willReturn( $competition );

        $club_service = $this->createStub( Club_Service::class );

        $player_service = $this->createStub( Player_Service::class );
        $player_service->method( 'find_all_associated_clubs' )
            ->willReturn( [ (object) [ 'shortcode' => 'LTC' ] ] );

        $security = $this->createStub( Security_Service_Interface::class );
        $security->method( 'is_user_logged_in' )->willReturn( true );
        $security->method( 'get_current_user_id' )->willReturn( 20 );
        $security->method( 'current_user_can' )->willReturn( false );

        $settings = new Settings_Service( [
            'league' => [
                'entry_level' => 'club',
            ],
        ] );

        $handler = new Get_Competition_Handler(
            $comp_service,
            $club_service,
            $player_service,
            $security,
            $settings,
            $this->competition_season_service()
        );

        $query = new Get_Competition_Query( competition_id: 2, season: '2026' );
        $vm    = $handler->handle( $query );

        $this->assertInstanceOf( Competition_View_Model::class, $vm );
        $this->assertStringContainsString( '/entry-form/autumn-league/2026/ltc/', $competition->entry_link );
    }
}
