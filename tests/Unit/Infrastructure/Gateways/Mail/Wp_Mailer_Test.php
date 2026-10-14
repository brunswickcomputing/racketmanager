<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Gateways\Mail;

use PHPUnit\Framework\TestCase;
use Racketmanager\Gateways\Mail\Mailer_Interface;
use Racketmanager\Infrastructure\Gateways\Mail\Wp_Mailer;

class Wp_Mailer_Test extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        $GLOBALS['wp_mail_calls'] = [];
    }

    protected function tearDown(): void {
        $GLOBALS['wp_mail_calls'] = [];
        parent::tearDown();
    }

    public function test_implements_mailer_interface(): void {
        $mailer = new Wp_Mailer();
        $this->assertInstanceOf( Mailer_Interface::class, $mailer );
    }

    public function test_send_forwards_all_arguments_to_wp_mail(): void {
        $mailer = new Wp_Mailer();
        $to = 'user@example.com';
        $subject = 'Match Rescheduled';
        $message = 'Your match has been moved to Sunday.';
        $headers = [ 'Content-Type: text/html', 'From: admin@example.com' ];
        $attachments = [ '/tmp/schedule.pdf' ];

        $result = $mailer->send( $to, $subject, $message, $headers, $attachments );

        $this->assertTrue( $result );
        $this->assertCount( 1, $GLOBALS['wp_mail_calls'] );
        $call = $GLOBALS['wp_mail_calls'][0];
        $this->assertSame( $to, $call['to'] );
        $this->assertSame( $subject, $call['subject'] );
        $this->assertSame( $message, $call['message'] );
        $this->assertSame( $headers, $call['headers'] );
        $this->assertSame( $attachments, $call['attachments'] );
    }

    public function test_send_returns_false_for_empty_recipients(): void {
        $mailer = new Wp_Mailer();

        $this->assertFalse( $mailer->send( '', 'Subject', 'Message' ) );
        $this->assertFalse( $mailer->send( null, 'Subject', 'Message' ) );
        $this->assertFalse( $mailer->send( [], 'Subject', 'Message' ) );
        $this->assertEmpty( $GLOBALS['wp_mail_calls'] );
    }

    public function test_send_returns_false_for_null_subject_or_message(): void {
        $mailer = new Wp_Mailer();

        $this->assertFalse( $mailer->send( 'user@example.com', null, 'Message' ) );
        $this->assertFalse( $mailer->send( 'user@example.com', 'Subject', null ) );
        $this->assertEmpty( $GLOBALS['wp_mail_calls'] );
    }
}
