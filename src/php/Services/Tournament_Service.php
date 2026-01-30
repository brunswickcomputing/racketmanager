<?php
/**
 * Tournament_Service class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage Services
 */

namespace Racketmanager\Services;

use Racketmanager\Domain\Tournament;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Charge_Repository;
use Racketmanager\Repositories\Tournament_Repository;
use Racketmanager\Util\Util_Messages;
use stdClass;

/**
 * Class to implement the Tournament Management Service
 */
class Tournament_Service {
    private RacketManager $racketmanager;
    private Tournament_Repository $tournament_repository;
    private Charge_Repository $charge_repository;

    /**
     * Constructor
     *
     */
    public function __construct( RacketManager $plugin_instance, Tournament_Repository $tournament_repository, Charge_Repository $charge_repository ) {
        $this->racketmanager = $plugin_instance;
        $this->tournament_repository  = $tournament_repository;
        $this->charge_repository  = $charge_repository;
    }

    public function get_tournament( null|string|int $tournament_id, $search_term = 'id' ): ?Tournament {
        $tournament = $this->tournament_repository->find_by_id( $tournament_id, $search_term );
        if ( ! $tournament ) {
            throw new Tournament_Not_Found_Exception( Util_Messages::charge_not_found( $tournament_id ) );
        }
        return $tournament;
    }

    public function get_fees( ?int $tournament_id ): stdClass {
        $tournament = $this->tournament_repository->find_by_id( $tournament_id );
        if ( ! $tournament ) {
            throw new Tournament_Not_Found_Exception( Util_Messages::tournament_not_found( $tournament_id ) );
        }
        $args                = array();
        $args['competition'] = $tournament->competition_id;
        $args['season']      = $tournament->season;
        $charges             = $this->racketmanager->get_charges( $args );
        $competition_fee     = null;
        $event_fee           = null;
        $fee_id              = null;
        $fee_status          = null;
        if ( $charges ) {
            $competition_fee = 0;
            $event_fee       = 0;
            foreach ( $charges as $charge ) {
                $competition_fee += $charge->fee_competition;
                $event_fee       += $charge->fee_event;
                $fee_id           = $charge->id;
                $fee_status       = $charge->status;
            }
        }
        $fees              = new stdClass();
        $fees->competition = $competition_fee;
        $fees->event       = $event_fee;
        $fees->id          = $fee_id;
        $fees->status      = $fee_status;
        return $fees;
    }

}
