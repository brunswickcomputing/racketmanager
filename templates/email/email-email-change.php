<?php
/**
 * Template for email change email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

$organisation  = $vars['site_name'];
$sitename      = $vars['site_name'];
$siteurl       = $vars['site_url'];
$userlogin     = $vars['user_login'];
$username      = $vars['display_name'];
$email_subject = __( 'Email Change', 'racketmanager' );
$emaillink     = $vars['email_link'];
require 'email-header.php';
?>
			<?php $salutation_link = $username; ?>
			<?php require 'components/salutation.php'; ?>
			<?php
			/* translators: %s: organisation name */
			$paragraph_text = sprintf( __( 'Your email address has now been changed for your %s account.', 'racketmanager' ), $organisation );
			require 'components/paragraph.php';
			?>
			<?php
			$paragraph_text = __( 'The new email address is ###NEW_EMAIL###.', 'racketmanager' );
			require 'components/paragraph.php';
			?>
			<?php require 'components/contact-urgent.php'; ?>
			<?php require 'components/closing.php'; ?>
<?php
require 'email-footer.php';
