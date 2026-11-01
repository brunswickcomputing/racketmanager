<?php
/**
 * Template for password reset by email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object|array $vm */
/** @var array $vars */
/** @var string $salutation */
/** @var string $paragraph */
/** @var string $closing */
$vm            = ( isset( $vm ) && is_object( $vm ) ) ? $vm : (object) ( $vars ?? [] );
$vars          = is_array( $vars ?? null ) ? $vars : (array) $vm;
$organisation  = $vm->site_name ?? ( $vars['site_name'] ?? '' );
$sitename      = $vm->site_name ?? ( $vars['site_name'] ?? '' );
$siteurl       = $vm->site_url ?? ( $vars['site_url'] ?? '' );
$userlogin     = $vm->user_login ?? ( $vars['user_login'] ?? '' );
$username      = $vm->display_name ?? ( $vars['display_name'] ?? '' );
$action_url    = $vm->action_url ?? ( $vars['action_url'] ?? '' );
$email_subject = __( 'Password Reset Link', 'racketmanager' );
require 'email-header.php';
?>
            <?php $salutation_link = $username; ?>
            <?php require $salutation; ?>
            <?php
            /* translators: %s: organisation name */
            $paragraph_text = sprintf( __( 'You recently requested to reset your password for your %s account. Use the button below to reset it.', 'racketmanager' ), $organisation );
            require $paragraph;
            ?>
            <?php $paragraph_format = 'bold'; ?>
            <?php $paragraph_text = __( 'This password reset is only valid for the next 24 hours.', 'racketmanager' ); ?>
            <?php require $paragraph; ?>
            <?php $action_link_text = __( 'Reset your password', 'racketmanager' ); ?>
            <?php require 'components/action-link.php'; ?>
            <?php $paragraph_format = ''; ?>
            <?php $paragraph_text = __( 'If you did not request a password reset, please ignore this email.', 'racketmanager' ); ?>
            <?php require $paragraph; ?>
            <?php require $closing; ?>
            <?php require 'components/link-text.php'; ?>
<?php
require 'email-footer.php';
