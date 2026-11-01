<?php
/**
 * Template for privacy user request action
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
$email_subject = __( 'Request Action Confirmation', 'racketmanager' );
$action_url    = '###CONFIRM_URL###';
?>
<?php require 'email-header.php'; ?>
<!-- START MAIN CONTENT AREA -->
            <?php $salutation_link = ''; ?>
            <?php require $salutation; ?>
            <?php
            /* translators: %s: organisation name */
            $paragraph_text = sprintf( __( 'A request has been made to ###DESCRIPTION### on your account at %s.', 'racketmanager' ), $organisation );
            require $paragraph;
            ?>
            <?php $action_link_text = __( 'Confirm action', 'racketmanager' ); ?>
            <?php require 'components/action-link.php'; ?>
            <?php $paragraph_text = __( 'You can safely ignore and delete this email if you do not want to take this action.', 'racketmanager' ); ?>
            <?php require $paragraph; ?>
            <?php require $closing; ?>
            <?php require 'components/link-text.php'; ?>
<?php
require 'email-footer.php';
