<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

use Racketmanager\Application\Fixture\Queries\Get_Fixture_Details_Handler;
use Racketmanager\Application\Fixture\Queries\Get_Fixture_Details_Query;
use Racketmanager\Presenters\Fixture_Presenter;
use function get_query_var;
use function Racketmanager\un_seo_url;
use function shortcode_atts;

/**
 * Adapter for the [fixture] and [match] shortcodes.
 */
readonly class Fixture_Shortcode_Adapter {
    public function __construct(
        private Get_Fixture_Details_Handler $handler,
        private Fixture_Presenter $presenter
    ) {}

    /**
     * Handle the shortcode.
     *
     * @param array|string $atts Shortcode attributes.
     *
     * @return string HTML output.
     */
    public function handle( array|string $atts ): string {
        $params     = $this->get_params( $atts );
        $fixture_id = $params['fixture_id'];
        $action     = $params['action'];

        // If no ID is provided, try to resolve via slug criteria from URL
        $slug_criteria = [];
        if ( ! $fixture_id ) {
            $slug_criteria = $this->get_slug_criteria();
        }

        $query = new Get_Fixture_Details_Query(
            fixture_id: $fixture_id ?: null,
            slug_criteria: $slug_criteria
        );

        $dto = $this->handler->handle( $query );

        if ( ! $dto ) {
            return 'not found';
        }

        return $this->render_response( $dto, $action );
    }

    /**
     * Get parameters from shortcode attributes and query variables.
     *
     * @param array|string $atts Shortcode attributes.
     *
     * @return array
     */
    private function get_params( array|string $atts ): array {
        $args = shortcode_atts(
            [
                'match_id' => 0,
                'player'   => '',
                'template' => '',
            ],
            is_array( $atts ) ? $atts : []
        );

        return [
            'fixture_id' => empty( $args['match_id'] ) ? (int) get_query_var( 'match_id' ) : (int) $args['match_id'],
            'action'     => (string) get_query_var( 'action' ),
        ];
    }

    /**
     * Get slug criteria from query variables.
     *
     * @return array
     */
    private function get_slug_criteria(): array {
        return [
            'league_slug'    => un_seo_url( (string) get_query_var( 'league_name' ) ) ?: null,
            'home_team_slug' => un_seo_url( (string) get_query_var( 'teamHome' ) ) ?: null,
            'away_team_slug' => un_seo_url( (string) get_query_var( 'teamAway' ) ) ?: null,
            'match_day'      => get_query_var( 'match_day' ) ? (int) get_query_var( 'match_day' ) : null,
            'season'         => get_query_var( 'season' ) ?: null,
            'round'          => get_query_var( 'round' ) ?: null,
            'leg'            => get_query_var( 'leg' ) ? (int) get_query_var( 'leg' ) : null,
        ];
    }

    /**
     * Render the fixture response.
     *
     * @param mixed $dto Fixture details DTO.
     * @param string $action Current action.
     *
     * @return string
     */
    private function render_response( mixed $dto, string $action ): string {
        $header_model = $this->presenter->map_to_header_read_model( $dto, 'result' === $action, true );
        $content      = $this->presenter->render_header( $header_model );

        $detail_model = $this->presenter->map_to_detail_read_model( $dto, true, 'result' === $action );
        $content     .= $this->presenter->render_detail( $detail_model );

        return $content;
    }
}
