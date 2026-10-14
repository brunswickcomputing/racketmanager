<?php
declare( strict_types=1 );

namespace Racketmanager\Application\General\Queries;

use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\RacketManager;
use function method_exists;

class Get_Player_Search_Handler {
    public function __construct(
        private readonly ?RacketManager $racketmanager = null
    ) {}

    public function handle( Get_Player_Search_Query $query ): Shortcode_View_Model {
        $players = [];
        if ( $this->racketmanager && method_exists( $this->racketmanager, 'get_all_players' ) ) {
            $players = $this->racketmanager->get_all_players( [ 'name' => $query->search ] ) ?: [];
        }

        $filename = ! empty( $query->template ) ? 'players-list-' . $query->template : 'players-list';

        return Shortcode_View_Model::for_template( $filename, [ 'players' => $players ] );
    }
}
