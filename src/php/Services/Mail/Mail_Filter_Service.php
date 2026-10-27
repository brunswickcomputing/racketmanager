<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Mail;

use Racketmanager\Domain\Communication\Message;
use Racketmanager\Repositories\Interfaces\Message_Repository_Interface;
use stdClass;

/**
 * Service for filtering outgoing wp_mail payloads (HTML headers and recording user messages).
 */
class Mail_Filter_Service implements Mail_Filter_Service_Interface {

	/**
	 * Constructor.
	 *
	 * @param Message_Repository_Interface $message_repository Message repository.
	 */
	public function __construct(
		private Message_Repository_Interface $message_repository
	) {
	}

	/**
	 * Add HTML content type to the mail header and record message if sender exists.
	 *
	 * @param array $args Arguments for mail message.
	 * @return array
	 */
	public function filter_mail( array $args ): array {
		$headers = $args['headers'] ?? array();
		if ( ! $headers ) {
			$headers = array();
		} elseif ( ! is_array( $headers ) ) {
			$temp_headers = explode( "\n", str_replace( "\r\n", "\n", (string) $headers ) );
			$headers      = $temp_headers;
		}

		$headers[]       = 'Content-Type: text/html; charset=UTF-8';
		$args['headers'] = $headers;
		$subject         = $args['subject'] ?? '';
		$message         = $args['message'] ?? '';
		$headers         = $args['headers'];

		if ( isset( $args['to'] ) && is_array( $args['to'] ) ) {
			$to = $args['to'];
		} else {
			$to = explode( ',', (string) ( $args['to'] ?? '' ) );
		}

		$cc       = array();
		$bcc      = array();
		$reply_to = array();
		$from     = '';

		foreach ( $headers as $header ) {
			if ( ! is_string( $header ) || ! str_contains( $header, ':' ) ) {
				continue;
			}

			// Explode them out.
			list( $name, $content ) = explode( ':', trim( $header ), 2 );

			// Cleanup crew.
			$name    = trim( $name );
			$content = trim( $content );

			switch ( strtolower( $name ) ) {
				// Mainly for legacy -- process a "From:" header if it's there.
				case 'from':
					$from = $content;
					break;
				case 'content-type':
					break;
				case 'cc':
					$cc = array_merge( (array) $cc, explode( ',', $content ) );
					break;
				case 'bcc':
					$bcc = array_merge( (array) $bcc, explode( ',', $content ) );
					break;
				case 'reply-to':
					$reply_to = array_merge( (array) $reply_to, explode( ',', $content ) );
					break;
				default:
					// Add it to our grand headers array.
					$headers[ trim( $name ) ] = trim( $content );
					break;
			}
		}

		if ( ! empty( $from ) ) {
			$address_headers = compact( 'to', 'cc', 'bcc' );
			foreach ( $address_headers as $addresses ) {
				if ( empty( $addresses ) ) {
					continue;
				}
				foreach ( $addresses as $address ) {
					if ( preg_match( '/(.*)<(.+)>/', (string) $address, $matches ) && 3 === count( $matches ) ) {
						$address = $matches[2];
					}
					$address = trim( (string) $address );
					if ( '' === $address ) {
						continue;
					}
					$user = function_exists( 'get_user_by' ) ? get_user_by( 'email', $address ) : null;
					if ( $user ) {
						$message_object                 = new stdClass();
						$message_object->subject        = $subject;
						$message_object->userid         = $user->ID;
						$message_object->date           = function_exists( 'current_time' ) ? current_time( 'mysql', false ) : date( 'Y-m-d H:i:s' );
						$message_object->message_object = $message;
						$message_object->sender         = $from;
						$message_object->status         = 1;
						$message_entity                 = Message::from_object( $message_object );
						$this->message_repository->save( $message_entity );
					}
				}
			}
		}

		return $args;
	}
}
