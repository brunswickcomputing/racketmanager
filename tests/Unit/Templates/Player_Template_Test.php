<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Player;
use Racketmanager\Presenters\Player_Presenter;

class Player_Template_Test extends TestCase {
    private string $player_header_template_path;
    private string $player_list_names_template_path;

    protected function setUp(): void {
        parent::setUp();
        $this->player_header_template_path     = dirname( __DIR__, 3 ) . '/templates/includes/player-header.php';
        $this->player_list_names_template_path = dirname( __DIR__, 3 ) . '/templates/includes/player-list-names.php';
    }

    public function test_player_header_renders_page_header_without_link(): void {
        $player = (object) array(
            'id'           => 10,
            'firstname'    => 'John',
            'surname'      => 'Doe',
            'display_name' => 'John Doe',
            'link'         => '/players/john-doe/',
            'wtn'          => array(
                'singles' => '25.4',
                'doubles' => '28.1',
            ),
            'statistics'   => (object) array(
                'total' => (object) array(
                    'played'       => 10,
                    'matches_won'  => 8,
                    'matches_lost' => 2,
                    'win_pct'      => 80.0,
                ),
            ),
        );
        $is_page_header   = true;
        $player_presenter = new Player_Presenter();

        ob_start();
        include $this->player_header_template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( '<h1 class="media__title">', $output );
        $this->assertStringNotContainsString( '<a href="/players/john-doe/">', $output );
        $this->assertStringContainsString( 'JD', $output ); // initials
        $this->assertStringContainsString( 'John Doe', $output );
        $this->assertStringContainsString( '25.4', $output );
        $this->assertStringContainsString( '28.1', $output );
        $this->assertStringContainsString( '8-2 (10)', $output );
        $this->assertStringContainsString( '80 won%', $output );
    }

    public function test_player_header_renders_sub_module_with_link(): void {
        $player = (object) array(
            'id'           => 11,
            'firstname'    => 'Jane',
            'surname'      => 'Smith',
            'display_name' => 'Jane Smith',
            'link'         => '/players/jane-smith/',
            'club'         => (object) array(
                'shortcode' => 'leigh-tc',
            ),
            'wtn'          => array(),
            'statistics'   => null,
        );
        $is_page_header   = false;
        $header_level     = 4;
        $player_presenter = new Player_Presenter();

        ob_start();
        include $this->player_header_template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( '<h4 class="media__title">', $output );
        $this->assertStringContainsString( '<a href="/players/jane-smith/">', $output );
        $this->assertStringContainsString( 'JS', $output );
        $this->assertStringContainsString( '<a href="/clubs/leigh_tc/">', $output );
        $this->assertStringNotContainsString( 'progress-bar bg-success', $output );
    }

    public function test_player_list_names_renders_alphabet_navigation_and_items(): void {
        $player_list = array(
            'A' => array(
                (object) array(
                    'id'           => 1,
                    'display_name' => 'Alex Adams',
                    'index'        => 'Adams, Alex',
                    'club'         => (object) array( 'shortcode' => 'LTC' ),
                ),
            ),
            'M' => array(
                (object) array(
                    'id'           => 2,
                    'display_name' => 'Michael Miller',
                    'index'        => 'Miller, Michael',
                    'club'         => null,
                ),
            ),
        );
        $player_link      = '/players/';
        $player_presenter = new Player_Presenter();

        ob_start();
        include $this->player_list_names_template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( 'Adams, Alex', $output );
        $this->assertStringContainsString( 'Miller, Michael', $output );
        $this->assertStringContainsString( '/players/alex-adams/', $output );
        $this->assertStringContainsString( '/players/michael-miller/', $output );
        // Alphabet navigation active/disabled checks:
        $this->assertStringContainsString( '<a href="#A" class="alphabet__char-href ">A</a>', $output );
        $this->assertStringContainsString( '<a href="#M" class="alphabet__char-href ">M</a>', $output );
        $this->assertStringContainsString( '<a href="#B" class="alphabet__char-href is-disabled">B</a>', $output );
        $this->assertStringContainsString( '<a href="#Z" class="alphabet__char-href is-disabled">Z</a>', $output );
    }

    public function test_player_list_names_renders_empty_fallback(): void {
        $player_list      = array();
        $player_link      = '/players/';
        $player_presenter = new Player_Presenter();

        ob_start();
        include $this->player_list_names_template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( 'No players found', $output );
    }
}
