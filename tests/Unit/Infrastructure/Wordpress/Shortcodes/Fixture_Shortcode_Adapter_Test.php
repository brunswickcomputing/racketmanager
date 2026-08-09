<?php

namespace Racketmanager\Tests\Unit\Infrastructure\Wordpress\Shortcodes;

use PHPUnit\Framework\TestCase;
use Racketmanager\Application\Fixture\Queries\Get_Fixture_Details_Handler;
use Racketmanager\Application\Fixture\Queries\Get_Fixture_Details_Query;
use Racketmanager\Application\Fixture\DTOs\Fixture_Details_DTO;
use Racketmanager\Infrastructure\Wordpress\Shortcodes\Fixture_Shortcode_Adapter;
use Racketmanager\Presenters\Fixture_Presenter;
use Racketmanager\Application\Fixture\DTOs\Fixture_Header_Read_Model;

class Fixture_Shortcode_Adapter_Test extends TestCase {
    private Fixture_Shortcode_Adapter $adapter;
    private Get_Fixture_Details_Handler $handler;
    private Fixture_Presenter $presenter;

    protected function setUp(): void {
        parent::setUp();
        
        $this->handler = $this->createStub(Get_Fixture_Details_Handler::class);
        $this->presenter = $this->createStub(Fixture_Presenter::class);
        $this->adapter = new Fixture_Shortcode_Adapter($this->handler, $this->presenter);

        if (!function_exists('shortcode_atts')) {
            eval('function shortcode_atts($pairs, $atts) { return array_merge($pairs, $atts); }');
        }
        if (!function_exists('get_query_var')) {
            eval('function get_query_var($var) { return $GLOBALS["query_vars"][$var] ?? null; }');
        }
        if (!function_exists('Racketmanager\un_seo_url')) {
            eval('namespace Racketmanager { function un_seo_url($s) { return str_replace("-", " ", $s); } }');
        }
        $GLOBALS['query_vars'] = [];
    }

    public function test_handle_uses_un_seo_url_and_nulls_empty_vars(): void {
        $GLOBALS['query_vars'] = [
            'league_name' => 'premier-league',
            'teamHome' => 'team-a',
            'teamAway' => 'team-b',
            'match_day' => '0', // Should be null
            'leg' => '', // Should be null
            'season' => '2023',
        ];

        $captured_query = null;
        $this->handler->method('handle')->willReturnCallback(function($query) use (&$captured_query) {
            $captured_query = $query;
            return null;
        });

        $this->adapter->handle([]);

        $this->assertNotNull($captured_query);
        $criteria = $captured_query->slug_criteria;
        
        $this->assertEquals('premier league', $criteria['league_slug']);
        $this->assertEquals('team a', $criteria['home_team_slug']);
        $this->assertEquals('team b', $criteria['away_team_slug']);
        $this->assertNull($criteria['match_day']);
        $this->assertNull($criteria['leg']);
        $this->assertEquals('2023', $criteria['season']);
    }

    public function test_handle_returns_not_found_if_no_fixture_found(): void {
        $this->handler->method('handle')->willReturn(null);
        
        $result = $this->adapter->handle(['match_id' => 123]);
        
        $this->assertEquals('not found', $result);
    }

    public function test_handle_renders_header(): void {
        $fixture = $this->createStub(\Racketmanager\Domain\Fixture\Fixture::class);
        $dto = new Fixture_Details_DTO($fixture);
        $this->handler->method('handle')->willReturn($dto);
        
        $header_model = $this->createStub(Fixture_Header_Read_Model::class);
        $this->presenter->method('map_to_header_read_model')->willReturn($header_model);
        $this->presenter->method('render_header')->willReturn('<div id="match-header"><header>Header</header></div>');
        
        $this->presenter->method('map_to_detail_read_model')->willReturn($this->createStub(\Racketmanager\Application\Fixture\DTOs\Fixture_Detail_Read_Model::class));
        $this->presenter->method('render_detail')->willReturn('<div>Detail</div>');

        $result = $this->adapter->handle(['match_id' => 123]);
        
        $this->assertStringContainsString('<div id="match-header">', $result);
        $this->assertStringContainsString('<header>Header</header>', $result);
        $this->assertStringContainsString('<div>Detail</div>', $result);
        $this->assertStringNotContainsString('<script type="text/javascript">', $result);
        $this->assertStringNotContainsString('function matchHeaderListener ()', $result);
        
        // Ensure the div is ONLY around the header in the output from presenter
        // In this test, we mock render_header to include it, so we just check it's present.
        $this->assertStringStartsWith('<div id="match-header">', $result);
    }

    public function test_handle_includes_detail_even_for_result_action(): void {
        $GLOBALS['query_vars']['action'] = 'result';
        $fixture = $this->createStub(\Racketmanager\Domain\Fixture\Fixture::class);
        $dto = new Fixture_Details_DTO($fixture);
        $this->handler->method('handle')->willReturn($dto);
        
        $this->presenter->method('render_header')->willReturn('Header');
        $this->presenter->method('render_detail')->willReturn('Detail');

        $result = $this->adapter->handle(['match_id' => 123]);
        
        $this->assertStringContainsString('Header', $result);
        $this->assertStringContainsString('Detail', $result);
    }
}
