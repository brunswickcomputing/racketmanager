<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Application\Player\Queries;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Application\General\Queries\Get_Alert_Handler;
use Racketmanager\Application\General\Queries\Get_Alert_Query;
use Racketmanager\Application\General\Queries\Get_Player_Search_Handler;
use Racketmanager\Application\General\Queries\Get_Player_Search_Query;
use Racketmanager\Application\Player\Queries\Get_Player_Details_Handler;
use Racketmanager\Application\Player\Queries\Get_Player_Details_Query;
use Racketmanager\Application\Player\Queries\Search_Players_Handler;
use Racketmanager\Application\Player\Queries\Search_Players_Query;
use Racketmanager\Domain\DTO\Club\Club_Player_DTO;
use Racketmanager\Domain\DTO\Player\Players_List_DTO;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Presentation\Presenters\Shortcode_View_Presenter;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Services\Player_Service;
use Racketmanager\Services\Registration_Service;

require_once dirname( __DIR__, 4 ) . '/wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Player_Queries_Test extends TestCase {

	public function test_search_players_query_from_request(): void {
		$request1 = [ 'search_string' => 'Roger' ];
		$query1   = Search_Players_Query::from_request( $request1 );
		$this->assertSame( 'Roger', $query1->search_string );

		$request2 = [ 'name' => 'Federer' ];
		$query2   = Search_Players_Query::from_request( $request2 );
		$this->assertSame( 'Federer', $query2->search_string );

		$request3 = [];
		$query3   = Search_Players_Query::from_request( $request3 );
		$this->assertNull( $query3->search_string );
	}

	public function test_search_players_handler_returns_search_markup(): void {
		$search_handler = $this->createMock( Get_Player_Search_Handler::class );
		$alert_handler  = $this->createMock( Get_Alert_Handler::class );

		$result = new \Racketmanager\Application\General\DTOs\Player_Search_Result( players: [ 'Nadal' ], search: 'Nadal' );
		$search_handler->expects( $this->once() )
			->method( 'handle' )
			->with( $this->callback( fn( Get_Player_Search_Query $q ) => 'Nadal' === $q->search ) )
			->willReturn( $result );

		$handler = new Search_Players_Handler(
			player_search_handler: $search_handler,
			alert_handler: $alert_handler
		);
		$query   = new Search_Players_Query( search_string: 'Nadal' );

		$output = $handler->handle( $query );
		$this->assertSame( $result, $output );
	}

	public function test_search_players_handler_missing_string_returns_alert(): void {
		$search_handler = $this->createMock( Get_Player_Search_Handler::class );
		$alert_handler  = $this->createMock( Get_Alert_Handler::class );

		$result = new \Racketmanager\Application\General\DTOs\Alert_Result( msg: 'Search string not supplied', type: 'danger' );
		$alert_handler->expects( $this->once() )
			->method( 'handle' )
			->with( $this->callback( fn( Get_Alert_Query $q ) => 'danger' === $q->type ) )
			->willReturn( $result );

		$handler = new Search_Players_Handler(
			player_search_handler: $search_handler,
			alert_handler: $alert_handler
		);
		$query   = new Search_Players_Query( search_string: null );

		$output = $handler->handle( $query );
		$this->assertSame( $result, $output );
	}

	public function test_get_player_details_query_from_request(): void {
		$request = [
			'type'          => 'club',
			'name'          => 'Murray',
			'partnerGender' => 'M',
			'club'          => '15',
		];

		$query = Get_Player_Details_Query::from_request( $request );
		$this->assertSame( 'club', $query->type );
		$this->assertSame( 'Murray', $query->name );
		$this->assertSame( 'M', $query->gender );
		$this->assertSame( 15, $query->club_id );
	}

	public function test_get_player_details_handler_unsupported_types_return_empty_array(): void {
		$repo = $this->createMock( Player_Repository_Interface::class );
		$repo->expects( $this->never() )->method( 'find_registered_player_autocomplete' );

		$handler = new Get_Player_Details_Handler( $repo );

		$this->assertSame( [], $handler->handle( new Get_Player_Details_Query( type: 'non_club', gender: 'M' ) ) );
		$this->assertSame( [], $handler->handle( new Get_Player_Details_Query( type: 'club', club_id: 25 ) ) );
		$this->assertSame( [], $handler->handle( new Get_Player_Details_Query( type: 'registered' ) ) );
		$this->assertSame( [], $handler->handle( new Get_Player_Details_Query( type: 'championship', name: 'Djokovic' ) ) );
		$this->assertSame( [], $handler->handle( new Get_Player_Details_Query( type: 'user' ) ) );
		$this->assertSame( [], $handler->handle( new Get_Player_Details_Query( type: 'all' ) ) );
		$this->assertSame( [], $handler->handle( new Get_Player_Details_Query( type: 'unknown_type' ) ) );
	}

	public function test_get_player_details_handler_name_lookup_returns_registered_players_projection(): void {
		$repo = $this->createMock( Player_Repository_Interface::class );
		$repo->expects( $this->once() )
			->method( 'find_registered_player_autocomplete' )
			->with( 'Smith', 'name', null, null, 100 )
			->willReturn( [
				[
					'id'         => 101,
					'playerId'   => 1,
					'name'       => 'John Smith',
					'user_email' => 'john@example.com',
					'club_id'    => 15,
					'club'       => 'Westcliff',
					'btm'        => '12345',
					'contactno'  => '01234 567890',
				],
			] );

		$handler = new Get_Player_Details_Handler( $repo );
		$result  = $handler->handle( new Get_Player_Details_Query( type: 'name', name: 'Smith' ) );

		$this->assertCount( 1, $result );
		$this->assertSame( [
			'label'      => 'John Smith - Westcliff',
			'value'      => 'John Smith',
			'name'       => 'John Smith',
			'id'         => 101,
			'playerId'   => 1,
			'btm'        => '12345',
			'contactno'  => '01234 567890',
			'user_email' => 'john@example.com',
			'club_id'    => 15,
			'club'       => 'Westcliff',
		], $result[0] );
	}

	public function test_get_player_details_handler_name_lookup_skips_unregistered_players(): void {
		$repo = $this->createMock( Player_Repository_Interface::class );
		$repo->expects( $this->once() )
			->method( 'find_registered_player_autocomplete' )
			->with( 'Unregistered', 'name', null, null, 100 )
			->willReturn( [] );

		$handler = new Get_Player_Details_Handler( $repo );
		$result  = $handler->handle( new Get_Player_Details_Query( type: 'name', name: 'Unregistered' ) );

		$this->assertSame( [], $result );
	}

	public function test_get_player_details_handler_btm_lookup_returns_correct_js_contract(): void {
		$repo = $this->createMock( Player_Repository_Interface::class );
		$repo->expects( $this->once() )
			->method( 'find_registered_player_autocomplete' )
			->with( '987654', 'btm', null, null, 100 )
			->willReturn( [
				[
					'id'         => 202,
					'playerId'   => 42,
					'name'       => 'Andy Murray',
					'user_email' => 'andy@example.com',
					'club_id'    => 12,
					'club'       => 'PTC',
					'btm'        => '987654',
					'contactno'  => '07700900000',
				],
			] );

		$handler = new Get_Player_Details_Handler( $repo );
		$result  = $handler->handle( new Get_Player_Details_Query( type: 'btm', name: '987654' ) );

		$this->assertCount( 1, $result );
		$this->assertSame( '987654', $result[0]['value'] );
		$this->assertSame( 'Andy Murray', $result[0]['name'] );
		$this->assertSame( 'Andy Murray - PTC', $result[0]['label'] );
		$this->assertSame( 42, $result[0]['playerId'] );
		$this->assertSame( 202, $result[0]['id'] );
		$this->assertSame( 12, $result[0]['club_id'] );
		$this->assertSame( 'PTC', $result[0]['club'] );
		$this->assertSame( '987654', $result[0]['btm'] );
		$this->assertSame( '07700900000', $result[0]['contactno'] );
		$this->assertSame( 'andy@example.com', $result[0]['user_email'] );
	}

	public function test_get_player_details_handler_btm_lookup_unregistered_player(): void {
		$repo = $this->createMock( Player_Repository_Interface::class );
		$repo->expects( $this->once() )
			->method( 'find_registered_player_autocomplete' )
			->with( '987654', 'btm', null, null, 100 )
			->willReturn( [] );

		$handler = new Get_Player_Details_Handler( $repo );
		$result  = $handler->handle( new Get_Player_Details_Query( type: 'btm', name: '987654' ) );

		$this->assertSame( [], $result );
	}

	public function test_get_player_details_handler_rejects_short_or_empty_autocomplete_term(): void {
		$repo = $this->createMock( Player_Repository_Interface::class );
		$repo->expects( $this->never() )->method( 'find_registered_player_autocomplete' );

		$handler = new Get_Player_Details_Handler( $repo );

		$this->assertSame( [], $handler->handle( new Get_Player_Details_Query( type: 'name', name: '' ) ) );
		$this->assertSame( [], $handler->handle( new Get_Player_Details_Query( type: 'name', name: 'j' ) ) );
		$this->assertSame( [], $handler->handle( new Get_Player_Details_Query( type: 'btm' ) ) );
		$this->assertSame( [], $handler->handle( new Get_Player_Details_Query( type: 'btm', name: '1' ) ) );
	}

	public function test_get_player_details_handler_filters_by_gender_and_club(): void {
		$repo = $this->createMock( Player_Repository_Interface::class );
		$repo->expects( $this->once() )
			->method( 'find_registered_player_autocomplete' )
			->with( 'jo', 'name', 'F', 15, 100 )
			->willReturn( [
				[
					'id'         => 20,
					'playerId'   => 2,
					'name'       => 'Jo Female Club',
					'user_email' => 'jo@example.com',
					'club_id'    => 15,
					'club'       => 'Club 15',
					'btm'        => '222',
					'contactno'  => '',
				],
			] );

		$handler = new Get_Player_Details_Handler( $repo );
		$result  = $handler->handle( new Get_Player_Details_Query( type: 'name', name: 'jo', gender: 'F', club_id: 15 ) );

		$this->assertCount( 1, $result );
		$this->assertSame( 2, $result[0]['playerId'] );
		$this->assertSame( 'Jo Female Club', $result[0]['value'] );
		$this->assertSame( 15, $result[0]['club_id'] );
		$this->assertSame( 'Club 15', $result[0]['club'] );
	}

	public function test_get_player_details_handler_autocomplete_excludes_sensitive_fields(): void {
		$repo = $this->createMock( Player_Repository_Interface::class );
		$repo->method( 'find_registered_player_autocomplete' )->willReturn( [
			[
				'id'         => 90,
				'playerId'   => 9,
				'name'       => 'Safe Player',
				'user_email' => 'safe@example.com',
				'club_id'    => 10,
				'club'       => 'Safe Club',
				'btm'        => '55555',
				'contactno'  => '0123456789',
			],
		] );

		$handler = new Get_Player_Details_Handler( $repo );
		$result  = $handler->handle( new Get_Player_Details_Query( type: 'name', name: 'Safe' ) );

		$this->assertSame( [ 'label', 'value', 'name', 'id', 'playerId', 'btm', 'contactno', 'user_email', 'club_id', 'club' ], array_keys( $result[0] ) );
		$encoded = wp_json_encode( $result );
		$this->assertIsString( $encoded );
		$this->assertStringNotContainsString( 'user_pass', $encoded );
		$this->assertStringNotContainsString( 'user_activation_key', $encoded );
		$this->assertStringNotContainsString( 'year_of_birth', $encoded );
		$this->assertStringNotContainsString( 'age', $encoded );
	}

	private function summary_player( array $overrides = [] ): Players_List_DTO {
		return new Players_List_DTO(
			array_merge(
				[
					'id'           => 1,
					'display_name' => 'John Smith',
					'first_name'   => 'John',
					'surname'      => 'Smith',
					'email'        => 'john@example.com',
					'gender'       => 'M',
					'btm'          => '12345',
					'contactno'    => '01234 567890',
				],
				$overrides
			)
		);
	}
}
