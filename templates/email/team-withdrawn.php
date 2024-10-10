<?php
/**
 * Withdrawn league team email body
 *
 * @package Racketmanager/Templates/Email
 */

namespace Racketmanager;

require 'email-header.php';
?>
			<?php
			$paragraph_text = __( 'Dear Captain', 'racketmanager' );
			require 'components/paragraph.php';
			/* translators: %1$s: team name %2$s: league name */
			$paragraph_text = sprintf( __( 'Unfortunately %1$s have withdrawn from the %2$s league.', 'racketmanager' ), $team->title, $league->title );
			require 'components/paragraph.php';
			/* translators: %s: team name */
			$paragraph_text = sprintf( __( 'All unplayed matches involving %s have been cancelled.', 'racketmanager' ), $team->title );
			require 'components/paragraph.php';
			/* translators: %s: team name */
			$paragraph_text = sprintf( __( 'All points from any completed match involving %s have been removed.', 'racketmanager' ), $team->title );
			require 'components/paragraph.php';
			if ( ! empty( $email_from ) ) {
				$contact_email = $email_from;
				require 'components/contact.php';
			}
			require 'components/closing.php';
			?>
<?php
require 'email-footer.php';
