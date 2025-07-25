<?php
/**
 * Template for welcome email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var array $vars */
/** @var string $salutation */
/** @var string $paragraph */
/** @var string $contact */
/** @var string $closing */
$organisation  = $vars['site_name'];
$sitename      = $vars['site_name'];
$siteurl       = $vars['site_url'];
$userlogin     = $vars['user_login'];
$username      = $vars['display_name'];
$loginurl      = $vars['site_url'] . '/login';
$actionurl     = $vars['action_url'];
$contact_email = $vars['email_link'];
$email_subject = __( 'Welcome Email', 'racketmanager' );
require 'email-header.php';
?>
            <?php $salutation_link = $username; ?>
            <?php require $salutation; ?>
            <?php
            /* translators: %s: organisation name */
            $paragraph_text = sprintf( __( 'Thanks for joining %s. We are delighted to have you on board.', 'racketmanager' ), $organisation );
            require $paragraph;
            /* translators: %s: organisation name */
            $paragraph_text = sprintf( __( 'To get the most out of %s, you need to complete the registration and chose a password.', 'racketmanager' ), $organisation );
            require $paragraph;
            ?>
            <?php $action_link_text = __( 'Complete Registration', 'racketmanager' ); ?>
            <?php require 'components/action-link.php'; ?>
            <?php
            if ( ! empty( $contact_email ) ) {
                require $contact;
            }
            ?>
            <?php require $closing; ?>
            <?php require 'components/link-text.php'; ?>

<!-- END MAIN CONTENT AREA -->
</table>
<!-- END CENTERED WHITE CONTAINER -->
<?php require 'email-footer.php'; ?>
