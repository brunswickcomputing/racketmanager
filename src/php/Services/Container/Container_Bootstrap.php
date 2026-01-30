<?php
namespace Racketmanager\Services\Container;

use Racketmanager\RacketManager;
use Racketmanager\Repositories\Charge_Repository;
use Racketmanager\Repositories\Club_Repository;
use Racketmanager\Repositories\Competition_Repository;
use Racketmanager\Repositories\Event_Repository;
use Racketmanager\Repositories\Invoice_Repository;
use Racketmanager\Repositories\League_Repository;
use Racketmanager\Repositories\League_Team_Repository;
use Racketmanager\Repositories\Registration_Repository;
use Racketmanager\Repositories\Club_Role_Repository;
use Racketmanager\Repositories\Player_Repository;
use Racketmanager\Repositories\Player_Error_Repository;
use Racketmanager\Repositories\Team_Repository;
use Racketmanager\Repositories\Tournament_Repository;
use Racketmanager\Services\Competition_Entry_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\External\Wtn_Api_Client;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Registration_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\Tournament_Service;

/**
 * Registers core services in the Simple_Container.
 */
final class Container_Bootstrap {
    public static function boot(RacketManager $app): Simple_Container {
        $c = new Simple_Container();

        // Repositories
        $c->set('club_repository', fn() => new Club_Repository());
        $c->set('registration_repository', fn() => new Registration_Repository());
        $c->set('club_role_repository', fn() => new Club_Role_Repository());
        $c->set('player_repository', fn() => new Player_Repository());
        $c->set('player_error_repository', fn() => new Player_Error_Repository());
        $c->set('team_repository', fn() => new Team_Repository());
        $c->set('event_repository', fn() => new Event_Repository());
        $c->set('league_repository', fn() => new League_Repository());
        $c->set('league_team_repository', fn() => new League_Team_Repository());
        $c->set('competition_repository', fn() => new Competition_Repository());
        $c->set('charge_repository', fn() => new Charge_Repository());
        $c->set('invoice_repository', fn() => new Invoice_Repository());
        $c->set('tournament_repository', fn() => new Tournament_Repository());

        // External clients
        $c->set('wtn_api_client', fn() => new Wtn_Api_Client());

        // Services
        $c->set('player_service', function(Simple_Container $c) use ($app) {
            return new Player_Service(
                $app,
                $c->get('player_repository'),
                $c->get('player_error_repository'),
                $c->get('club_role_repository'),
                $c->get('wtn_api_client'),
                $c->get('league_team_repository'),
                $c->get('club_repository'),
                $c->get('registration_repository'),
            );
        });

        $c->set('competition_service', function(Simple_Container $c) use ($app) {
            return new Competition_Service(
                $app,
                $c->get('competition_repository'),
                $c->get('club_repository'),
                $c->get('event_repository'),
                $c->get('league_repository'),
                $c->get('league_team_repository'),
                $c->get('team_repository'),
            );
        });

        $c->set('club_service', function(Simple_Container $c) {
            return new Club_Service(
                $c->get('club_repository'),
                $c->get('registration_repository'),
                $c->get('club_role_repository'),
                $c->get('player_repository'),
                $c->get('team_repository'),
                $c->get('player_service'),
            );
        });

        $c->set('competition_entry_service', function(Simple_Container $c) use ($app) {
            return new Competition_Entry_Service(
                $app,
                $c->get('club_repository'),
                $c->get('league_repository'),
                $c->get('league_team_repository'),
                $c->get('team_repository'),
                $c->get('tournament_repository'),
                $c->get('club_service'),
                $c->get('competition_service'),
                $c->get('finance_service'),
                $c->get('player_service'),
            );
        });

        $c->set('team_service', function(Simple_Container $c) {
            return new Team_Service(
                $c->get('team_repository'),
                $c->get('club_repository'),
                $c->get('event_repository'),
                $c->get('player_service'),
            );
        });

        $c->set('registration_service', function(Simple_Container $c) use ($app) {
            // Depends on player_service
            return new Registration_Service(
                $app,
                $c->get('registration_repository'),
                $c->get('player_repository'),
                $c->get('club_repository'),
                $c->get('player_service')
            );
        });

        $c->set('league_service', function(Simple_Container $c) use ($app) {
            return new League_Service(
                $app,
                $c->get('league_repository'),
                $c->get('event_repository'),
                $c->get('league_team_repository'),
                $c->get('team_repository'),
            );
        });

        $c->set('finance_service', function(Simple_Container $c) use ( $app ) {
            return new Finance_Service(
                $app,
                $c->get('charge_repository'),
                $c->get('invoice_repository'),
                $c->get('club_repository'),
                $c->get('tournament_repository'),
                $c->get('competition_service'),
                $c->get('player_service')
            );
        });

        $c->set('tournament_service', function(Simple_Container $c) use ( $app ) {
            return new Tournament_Service(
                $app,
                $c->get('tournament_repository'),
                $c->get('charge_repository'),
//                $c->get('invoice_repository'),
            );
        });

        return $c;
    }
}
