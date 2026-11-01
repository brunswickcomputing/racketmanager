<?php
/**
 * Template for sending change of password email
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
$email_subject = __( 'Password Change', 'racketmanager' );
$email_link    = $vm->email_link ?? ( $vars['email_link'] ?? '' );
require 'email-header.php';
?>
            <?php $salutation_link = $username; ?>
            <?php require $salutation; ?>
            <?php
            /* translators: %s: organisation name */
            $paragraph_text = sprintf( __( 'Your password has now been changed for your %s account.', 'racketmanager' ), $organisation );
            require $paragraph;
            ?>
            <?php require 'components/contact-urgent.php'; ?>
            <?php require $closing; ?>
<?php
require 'email-footer.php';
