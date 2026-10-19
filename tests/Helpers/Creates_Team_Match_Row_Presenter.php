<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use Racketmanager\Gateways\Option\Option_Gateway_Interface;
use Racketmanager\Presentation\Presenters\Team_Match_Row_Presenter;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Fixture\Fixture_Relation_Loader;

/**
 * Builds a real Team_Match_Row_Presenter backed by stubbed collaborators.
 */
trait Creates_Team_Match_Row_Presenter {
    protected function create_team_match_row_presenter(
        ?bool $user_can_update = null,
        ?Fixture_Relation_Loader $relation_loader = null,
        ?Club_Repository_Interface $club_repository = null,
        ?Fixture_Link_Service $link_service = null,
        string $date_format = 'Y-m-d',
        ?Fixture_Permission_Service $permission_service = null,
        ?League_Repository_Interface $league_repository = null
    ): Team_Match_Row_Presenter {
        $options = $this->createStub( Option_Gateway_Interface::class );
        $options->method( 'get_option' )->willReturnCallback(
            static fn( string $option, mixed $default_value = false ) => 'date_format' === $option ? $date_format : $default_value
        );

        if ( null === $permission_service ) {
            $permission_service = $this->createStub( Fixture_Permission_Service::class );
            $permission_service->method( 'is_update_allowed' )->willReturn( (object) [ 'user_can_update' => (bool) $user_can_update ] );
        }

        return new Team_Match_Row_Presenter(
            $options,
            $relation_loader ?? $this->createStub( Fixture_Relation_Loader::class ),
            $permission_service,
            $link_service ?? $this->createStub( Fixture_Link_Service::class ),
            $club_repository ?? $this->createStub( Club_Repository_Interface::class ),
            $league_repository ?? $this->createStub( League_Repository_Interface::class )
        );
    }
}
