<?php

namespace Racketmanager\Services;

use Racketmanager\Domain\Season;
use Racketmanager\RacketManager;
use Racketmanager\Repositories\Season_Repository;
use Racketmanager\Services\Validator\Validator;
use WP_Error;

class Season_Service {
    private RacketManager $racketmanager;
    private Season_Repository $season_repository;

    /**
     * Constructor
     */
    public function __construct( RacketManager $plugin_instance, Season_Repository $season_repository ) {
        $this->racketmanager     = $plugin_instance;
        $this->season_repository = $season_repository;
    }

    /**
     * @return Season[]
     */
    public function get_all_seasons(): array {
        return $this->season_repository->find_all();
    }

    public function create_season( ?string $season_name ): bool|WP_Error {
        $validator = new Validator();
        $error_field   = 'seasonName';
        if ( empty( $season_name ) ) {
            $error_message = __( 'Season must be specified', 'racketmanager' );
            $validator->set_errors( $error_field, $error_message );
        } else {
            $season_exists = $this->get_season_by_name( $season_name );
            if ( $season_exists ) {
                $error_message = __( 'Season already exists', 'racketmanager' );
                $validator->set_errors( $error_field, $error_message );
            }
        }
        if ( ! empty( $validator->error ) ) {
            return $validator->err;
        }
        $season = new Season();
        $season->set_name( $season_name );
        return $this->season_repository->save( $season );
    }

    public function get_season_by_name( $season_id ): ?Season {
        return $this->season_repository->find_by_id( $season_id, 'name' );
    }

    public function delete_season( ?int $season_id ): bool {
        if ( $season_id ) {
            return $this->season_repository->delete( $season_id );
        } else {
            return false;
        }

    }
}
