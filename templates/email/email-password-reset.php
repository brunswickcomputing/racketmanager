<?php
/**
 * Template for password reset by email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var array $vars */
$organisation  = $vars['site_name'];
$sitename      = $vars['site_name'];
$siteurl       = $vars['site_url'];
$userlogin     = $vars['user_login'];
$username      = $vars['display_name'];
$action_url    = $vars['action_url'];
$email_subject = __( 'Password Reset Link', 'racketmanager' );
require 'email-header.php';
?>
            <?php $salutation_link = $username; ?>
            <?php require 'components/salutation.php'; ?>
            <?php
            /* translators: %s: organisation name */
            $paragraph_text = sprintf( __( 'You recently requested to reset your password for your %s account. Use the button below to reset it.', 'racketmanager' ), $organisation );
            require 'components/paragraph.php';
            ?>
            <?php $paragraph_format = 'bold'; ?>
            <?php $paragraph_text = __( 'This password reset is only valid for the next 24 hours.', 'racketmanager' ); ?>
            <?php require 'components/paragraph.php'; ?>
            <?php $action_link_text = __( 'Reset your password', 'racketmanager' ); ?>
            <?php require 'components/action-link.php'; ?>
            <?php $paragraph_format = ''; ?>
            <?php $paragraph_text = __( 'If you did not request a password reset, please ignore this email.', 'racketmanager' ); ?>
            <?php require 'components/paragraph.php'; ?>
            <?php require 'components/closing.php'; ?>
            <?php require 'components/link-text.php'; ?>
<?php
require 'email-footer.php';
