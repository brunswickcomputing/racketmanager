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
			$paragraph_text = sprintf( __( 'The entry form for the %1$s %2$s is now available.', 'racketmanager' ), ucfirst( $competition_name ), $season );
			require 'components/paragraph.php';
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
