<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Mail;

/**
 * Interface for filtering mail arguments and recording sent messages.
 */
interface Mail_Filter_Service_Interface {

	/**
	 * Add HTML content type and record message for matching user accounts.
	 *
	 * @param array $args wp_mail payload arguments.
	 * @return array Modified wp_mail payload arguments.
	 */
	public function filter_mail( array $args ): array;
}
