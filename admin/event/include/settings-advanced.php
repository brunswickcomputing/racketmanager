<?php
/**
 * Template for event advanced settings
 *
 * @package Racketmanager/Templats/Admin
 */

namespace Racketmanager;

?>
<div>
	<?php do_action( 'racketmanager_event_settings_' . $event->competition->sport, $event ); ?>
	<?php do_action( 'racketmanager_event_settings_' . $event->competition->mode, $event ); ?>
	<?php do_action( 'racketmanager_event_settings_', $event ); ?>
</div>
