<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Services\Mail;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Communication\Message;
use Racketmanager\Repositories\Interfaces\Message_Repository_Interface;
use Racketmanager\Services\Mail\Mail_Filter_Service;
use stdClass;

require_once dirname( __DIR__, 3 ) . '/wp-stubs.php';

#[AllowMockObjectsWithoutExpectations]
final class Mail_Filter_Service_Test extends TestCase {

	private Message_Repository_Interface $message_repository;
	private Mail_Filter_Service $service;

	protected function setUp(): void {
		parent::setUp();
		$this->message_repository = $this->createMock( Message_Repository_Interface::class );
		$this->service            = new Mail_Filter_Service( $this->message_repository );
	}

	public function test_adds_html_content_type_when_headers_empty(): void {
		$payload = [
			'to'      => 'player@example.com',
			'subject' => 'Test Subject',
			'message' => 'Hello World',
			'headers' => '',
		];

		$result = $this->service->filter_mail( $payload );

		$this->assertIsArray( $result['headers'] );
		$this->assertContains( 'Content-Type: text/html; charset=UTF-8', $result['headers'] );
	}

	public function test_converts_string_headers_to_array_and_appends_html(): void {
		$payload = [
			'to'      => 'player@example.com',
			'subject' => 'Test Subject',
			'message' => 'Hello World',
			'headers' => "Reply-To: reply@example.com\r\nCustom: value",
		];

		$result = $this->service->filter_mail( $payload );

		$this->assertIsArray( $result['headers'] );
		$this->assertSame( 'Reply-To: reply@example.com', $result['headers'][0] );
		$this->assertSame( 'Custom: value', $result['headers'][1] );
		$this->assertSame( 'Content-Type: text/html; charset=UTF-8', $result['headers'][2] );
	}

	public function test_records_messages_for_matching_recipients_when_from_header_is_present(): void {
		$saved_messages = [];
		$this->message_repository
			->expects( $this->exactly( 2 ) )
			->method( 'save' )
			->willReturnCallback( function ( Message $msg ) use ( &$saved_messages ): bool {
				$saved_messages[] = $msg;
				return true;
			} );

		// Set up dummy users in WP stubs
		$GLOBALS['wp_stubs_users']['email'] = [
			'to_user@example.com' => (object) [ 'ID' => 101 ],
			'cc_user@example.com' => (object) [ 'ID' => 202 ],
		];

		$payload = [
			'to'      => 'Recipient <to_user@example.com>',
			'subject' => 'Match Scheduled',
			'message' => '<p>Your match has been scheduled.</p>',
			'headers' => [
				'From: sender@example.com',
				'Cc: CC Recipient <cc_user@example.com>, unknown@example.com',
			],
		];

		$result = $this->service->filter_mail( $payload );

		$this->assertCount( 2, $saved_messages );
		$this->assertSame( 101, $saved_messages[0]->userid );
		$this->assertSame( 'Match Scheduled', $saved_messages[0]->subject );
		$this->assertSame( '<p>Your match has been scheduled.</p>', $saved_messages[0]->message_object );
		$this->assertSame( 'sender@example.com', $saved_messages[0]->sender );

		$this->assertSame( 202, $saved_messages[1]->userid );
		$this->assertSame( 'Match Scheduled', $saved_messages[1]->subject );
		$this->assertSame( 'sender@example.com', $saved_messages[1]->sender );

		// Clean up
		unset( $GLOBALS['wp_stubs_users']['email'] );
	}

	public function test_does_not_save_message_if_no_from_header(): void {
		$this->message_repository->expects( $this->never() )->method( 'save' );

		$payload = [
			'to'      => 'to_user@example.com',
			'subject' => 'No Sender',
			'message' => 'No sender message',
			'headers' => [],
		];

		$result = $this->service->filter_mail( $payload );
		$this->assertContains( 'Content-Type: text/html; charset=UTF-8', $result['headers'] );
	}
}
