<?php
/**
 * Template for privacy user request action
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var array $vars */
$organisation  = $vars['site_name'];
$email_subject = __( 'Request Action Confirmation', 'racketmanager' );
$action_url    = '###CONFIRM_URL###';
?>
<?php require 'email-header.php'; ?>
<!-- START MAIN CONTENT AREA -->
			<?php $salutation_link = ''; ?>
			<?php require 'components/salutation.php'; ?>
			<?php
			/* translators: %s: organisation name */
			$paragraph_text = sprintf( __( 'A request has been made to ###DESCRIPTION### on your account at %s.', 'racketmanager' ), $organisation );
			require 'components/paragraph.php';
			?>
			<?php $action_link_text = __( 'Confirm action', 'racketmanager' ); ?>
			<?php require 'components/action-link.php'; ?>
			<?php $paragraph_text = __( 'You can safely ignore and delete this email if you do not want to take this action.', 'racketmanager' ); ?>
			<?php require 'components/paragraph.php'; ?>
			<?php require 'components/closing.php'; ?>
			<?php require 'components/link-text.php'; ?>
<?php
require 'email-footer.php';