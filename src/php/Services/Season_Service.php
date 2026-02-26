<?php

namespace Racketmanager\Services;

use Racketmanager\RacketManager;
use Racketmanager\Repositories\Season_Repository;

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

    public function get_all_seasons(): array {
        return $this->season_repository->find_all();
    }

}
