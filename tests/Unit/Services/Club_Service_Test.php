<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use Racketmanager\Infrastructure\Wordpress\Options\Option_Service;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Interfaces\Club_Role_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Registration_Repository_Interface;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Player_Service;

class Club_Service_Test extends TestCase {
    private Club_Service $club_service;
    private Option_Service $option_service;

    protected function setUp(): void {
        parent::setUp();
        
        $club_repository = $this->createStub( Club_Repository::class );
        $club_player_repository = $this->createStub( Registration_Repository_Interface::class );
        $club_role_repository = $this->createStub( Club_Role_Repository_Interface::class );
        $player_repository = $this->createStub( Player_Repository::class );
        $team_repository = $this->createStub( Team_Repository::class );
        $player_service = $this->createStub( Player_Service::class );
        
        $this->option_service = $this->createStub( Option_Service::class );
        
        $this->club_service = new Club_Service(
            $club_repository,
            $club_player_repository,
            $club_role_repository,
            $player_repository,
            $team_repository,
            $player_service,
            $this->option_service
        );
    }

    public function test_can_user_update_players_returns_false_if_not_logged_in(): void {
        // This relies on is_user_logged_in() which needs to be stubbed if it doesn't exist
        $this->assertFalse( $this->club_service->can_user_update_players( 1 ) );
    }
}
