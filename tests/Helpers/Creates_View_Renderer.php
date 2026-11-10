<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Helpers;

use Racketmanager\Presentation\Presenters\Club_Presenter;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Presentation\Presenters\Player_Presenter;
use Racketmanager\Presentation\View\Shortcode_Template_Renderer;
use Racketmanager\Services\View\Php_View_Renderer;
use Racketmanager\Services\View\View_Renderer_Interface;

trait Creates_View_Renderer {

    protected function create_view_renderer( ?string $template_path = null ): View_Renderer_Interface {
        return new Php_View_Renderer( $template_path ?? RACKETMANAGER_PATH . 'templates/' );
    }

    protected function create_shortcode_template_renderer(
        ?View_Renderer_Interface $renderer = null,
        ?Club_Presenter $club_presenter = null,
        ?Player_Presenter $player_presenter = null,
        mixed ...$named_args
    ): Shortcode_Template_Renderer {
        return new Shortcode_Template_Renderer(
            $renderer ?? $this->create_view_renderer(),
            $club_presenter ?? new Club_Presenter(),
            $player_presenter ?? new Player_Presenter(),
            ...$named_args
        );
    }

    protected function create_competition_shortcode_presenter(
        ?View_Renderer_Interface $renderer = null,
        ?Club_Presenter $club_presenter = null,
        ?Player_Presenter $player_presenter = null
    ): Competition_Shortcode_Presenter {
        return new Competition_Shortcode_Presenter(
            $renderer ?? $this->create_view_renderer(),
            $club_presenter ?? new Club_Presenter(),
            $player_presenter ?? new Player_Presenter()
        );
    }
}
