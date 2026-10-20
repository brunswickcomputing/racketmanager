<?php
/**
 * Template for match result pending notification email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object|null      $match */
/** @var bool|null        $delay */
/** @var string|null      $paragraph */
/** @var string|null      $contact */
/** @var string|null      $closing */
/** @var string|null      $competition_name */
/** @var string|null      $match_date */
/** @var string|null      $original_date */
/** @var string|null      $new_date */
/** @var string|null      $from_email */

$competition_name = (string) ( $competition_name ?? ( $match->league->title ?? '' ) );
$match_date       = (string) ( $match_date ?? ( $new_date ?? ( $match->match_date ?? '' ) ) );
$original_date    = (string) ( $original_date ?? ( $match->original_date ?? '' ) );
$delay            = ! empty( $delay );
?>
<?php require 'email-header.php'; ?>
            <?php require 'components/match-heading.php'; ?>
            <?php
            $paragraph_text = __( 'The date of this match has now changed.', 'racketmanager' );
            require $paragraph;
            /* translators: $s: match date */
            $paragraph_text = sprintf( __( 'The new date is %s. ', 'racketmanager' ), $match_date );
            require $paragraph;
            if ( $delay ) {
                $paragraph_format = 'bold';
                /* translators: $s: match date */
                $paragraph_text = sprintf( __( 'This is now after the round end date (%s). ', 'racketmanager' ), $original_date );
                require $paragraph;
                $paragraph_format = '';
            }
            ?>
            <?php
            if ( ! empty( $from_email ) ) {
                $contact_email = $from_email;
                require $contact;
            }
            ?>
            <?php require $closing; ?>
<?php
require 'email-footer.php';
