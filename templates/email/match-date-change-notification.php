<?php
/**
 * Template for match result pending notification email
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

global $racketmanager;
/** @var object $match */
/** @var bool   $delay */
/** @var string $paragraph */
/** @var string $contact */
/** @var string $closing */
$competition_name = $match->league->title;
if ( empty( $match->start_time ) ) {
    $match_date    = mysql2date( $racketmanager->date_format, $match->date );
    $original_date = mysql2date( $racketmanager->date_format, $match->date_original );
} else {
    $match_date    = mysql2date( 'j F Y H:i', $match->date );
    $original_date = mysql2date( 'j F Y H:i', $match->date_original );
}
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
