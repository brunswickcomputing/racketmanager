<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\View_Models;

/**
 * View model representing a group of tournament fixtures.
 */
final readonly class Tournament_Match_Group_View_Model {
	/**
	 * @param string $key Raw time or group key (e.g. '10:00' or '99:99')
	 * @param string $header Formatted display header (e.g. '10:00' or 'Unscheduled')
	 * @param array $fixtures List of fixture details in this group
	 */
	public function __construct(
		public string $key,
		public string $header,
		public array $fixtures = []
	) {}
}
