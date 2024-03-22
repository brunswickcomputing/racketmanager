<?php
/**
 * Template for privacy personal data action
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

$organisation  = $vars['site_name'];
$sitename      = $vars['site_name'];
$siteurl       = $vars['site_url'];
$email_subject = 'Personal Data Export';
$action_url    = '###LINK###';
?>
<?php require 'email-header.php'; ?>
<!-- START MAIN CONTENT AREA -->
			<?php $salutation_link = ''; ?>
			<?php require 'components/salutation.php'; ?>
			<?php
			/* translators: %s: organisation name */
			$paragraph_text = sprintf( __( 'Your request for an export of personal data from %s has been completed.', 'racketmanager' ), $organisation );
			require 'components/paragraph.php';
			?>
			<?php
			$paragraph_format = 'bold';
			/* translators: %s: organisation name */
			$paragraph_text = __( 'For privacy and security, we will automatically delete the file on ###EXPIRATION###, so please download it before then.', 'racketmanager' );
			require 'components/paragraph.php';
			$paragraph_format = '';
			?>
			<?php $action_link_text = __( 'Download personal data', 'racketmanager' ); ?>
			<?php require 'components/action-link.php'; ?>
			<?php require 'components/closing.php'; ?>
			<?php require 'components/link-text.php'; ?>
<?php
require 'email-footer.php';
