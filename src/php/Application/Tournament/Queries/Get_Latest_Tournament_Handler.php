<?php
declare( strict_types=1 );

namespace Racketmanager\Application\Tournament\Queries;

use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Tournament_Service;
use Racketmanager\Util\Util;
use function esc_url;

class Get_Latest_Tournament_Handler {
    private const BASE_TOURNAMENTS = '/tournaments/';

    public function __construct(
        private readonly Tournament_Service $tournament_service
    ) {}

    public function handle( Get_Latest_Tournament_Query $query ): Shortcode_View_Model {
        $age_group         = $query->age_group;
        $active_tournament = $this->tournament_service->get_active_tournament( $age_group );

        if ( $active_tournament ) {
            $new_url = '/tournament/' . Util::seo_url( $active_tournament->get_name() ) . '/';
        } elseif ( $age_group ) {
            $new_url = self::BASE_TOURNAMENTS . $age_group . '/';
        } else {
            $new_url = self::BASE_TOURNAMENTS;
        }

        $script = '<script>location.href = "' . esc_url( $new_url ) . '"</script>';

        return Shortcode_View_Model::for_html( $script );
    }
}
