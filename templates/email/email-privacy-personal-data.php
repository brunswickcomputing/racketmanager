<?php
/**
 * Template for privacy personal data action
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
$email_subject = __( 'Personal Data Export', 'racketmanager' );
$action_url    = '###LINK###';
?>
<?php require 'email-header.php'; ?>
<!-- START MAIN CONTENT AREA -->
            <?php $salutation_link = ''; ?>
            <?php require $salutation; ?>
            <?php
            /* translators: %s: organisation name */
            $paragraph_text = sprintf( __( 'Your request for an export of personal data from %s has been completed.', 'racketmanager' ), $organisation );
            require $paragraph;
            ?>
            <?php
            $paragraph_format = 'bold';
            /* translators: %s: organisation name */
            $paragraph_text = __( 'For privacy and security, we will automatically delete the file on ###EXPIRATION###, so please download it before then.', 'racketmanager' );
            require $paragraph;
            $paragraph_format = '';
            ?>
            <?php $action_link_text = __( 'Download personal data', 'racketmanager' ); ?>
            <?php require 'components/action-link.php'; ?>
            <?php require $closing; ?>
            <?php require 'components/link-text.php'; ?>
<?php
require 'email-footer.php';
