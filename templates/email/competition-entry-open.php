<?php
/**
 * Template for competition entry open email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

require 'email-header.php';
?>
			<?php $salutation_link = $club->match_secretary_name; ?>
			<?php require 'components/salutation.php'; ?>
			<?php
			/* translators: %1$s: competition name, %2$s: season */
			$paragraph_text = sprintf( __( 'The entry form for the %1$s %2$s is now available.', 'racketmanager' ), ucfirst( $competition->name ), $season );
			require 'components/paragraph.php';
			?>
			<?php
			if ( ! empty( $date_start ) ) {
				/* translators: $s: start date */
				$paragraph_text = sprintf( __( 'The competition will run from %s', 'racketmanager' ), $date_start );
				if ( ! empty( $date_end ) ) {
					/* translators: $s: end date */
					$paragraph_text .= ' ' . sprintf( __( 'until %s', 'racketmanager' ), $date_end );
				}
				$paragraph_text .= '.';
				require 'components/paragraph.php';
			}
			?>
			<?php
			if ( ! empty( $date_closing ) ) {
				/* translators: $s: clsoing date */
				$paragraph_text = sprintf( __( 'The closing date for entries is %s.', 'racketmanager' ), $date_closing );
				require 'components/paragraph.php';
			}
			?>
			<?php $action_link_text = __( 'Entry Form', 'racketmanager' ); ?>
			<?php require 'components/action-link.php'; ?>
			<?php
			if ( ! empty( $from_email ) ) {
				$contact_email = $from_email;
				require 'components/contact.php';
			}
			?>
			<?php require 'components/closing.php'; ?>
			<?php require 'components/link-text.php'; ?>
<?php
require 'email-footer.php';
