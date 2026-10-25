<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Seo;

/**
 * Contract for building dynamic page and document titles.
 */
interface Page_Title_Service_Interface {

	/**
	 * Build dynamic page title based on current post slug and query variables.
	 *
	 * @param string      $title     Default or existing title.
	 * @param string|null $site_name Optional site name override.
	 * @return string
	 */
	public function build_title( string $title = '', ?string $site_name = null ): string;
}
