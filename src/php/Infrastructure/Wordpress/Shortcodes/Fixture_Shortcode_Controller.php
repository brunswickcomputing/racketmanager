<?php
/**
 * Fixture shortcode controller
 *
 * @package    RacketManager
 * @subpackage Infrastructure/Wordpress/Shortcodes
 */

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

use Racketmanager\RacketManager;

/**
 * Implement shortcode fixture responses.
 *
 * @author Paul Moffat
 */
class Fixture_Shortcode_Controller {
    private RacketManager $racketmanager;

    /**
     * @param RacketManager $plugin_instance
     */
    public function __construct( RacketManager $plugin_instance ) {
        $this->racketmanager = $plugin_instance;
    }

    /**
     * Handle the fixture shortcode.
     *
     * @param array|string $atts Shortcode attributes.
     *
     * @return string HTML output.
     */
    public function handle( array|string $atts ): string {
        $adapter = $this->racketmanager->container->get( 'fixture_shortcode_adapter' );

        return $adapter->handle( $atts );
    }
}
