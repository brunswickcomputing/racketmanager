<?php
/**
 * Template page for a single match
 *
 * @deprecated 2.0.0 Orphan template (no caller in plugin source). Retained ONLY as a legacy
 * compatibility exception for custom themes; it is not an active, modernized template and
 * still renders from legacy variable ($match).
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *
 *  $match: contains data of displayed match
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

global $racketmanager;

/** @var object $match */
?>
<?php
if ( ! empty( $match ) ) {
    $date_format = $racketmanager->date_format ?? get_option( 'date_format', 'd/m/Y' );
    $match_date  = ! empty( $match->date ) ? mysql2date( $date_format, $match->date ) : '';
    ?>
    <div class="match" id="match-<?php echo esc_html( $match->id ?? '' ); ?>">
        <h3 class="header"><?php esc_html_e( 'Match', 'racketmanager' ); ?></h3>
        <div class="match-content">
            <h4><?php echo esc_html( method_exists( $match, 'match_title' ) ? $match->match_title( $match->id ?? null, false ) : ( $match->title ?? '' ) ); ?></h4>
            <?php
            if ( isset( $match->score ) && '0:0' === $match->score ) {
                ?>
                <p class="matchDate"><?php echo esc_html( $match->date ?? '' ) . ' ' . esc_html( $match->start_time ?? '' ) . ' ' . esc_html( $match->location ?? '' ); ?></p>
            <?php } else { ?>
                <p class="score">
                    <?php echo esc_html( $match->score ?? '' ); ?>
                </p>
            <?php } ?>
            <?php if ( ! empty( $match->match_day ) ) { ?>
                <?php /* translators: %d: Match day */ ?>
                <p class='match_day'><?php echo esc_html( sprintf( __( 'Match Day %d', 'racketmanager' ), $match->match_day ) ); ?></p>
            <?php } ?>
            <p class='date'><?php echo esc_html( $match_date ); ?>, <span class='time'><?php if ( function_exists( 'the_match_time' ) && isset( $match->start_time ) ) { the_match_time( $match->start_time ); } elseif ( function_exists( 'Racketmanager\the_match_time' ) && isset( $match->start_time ) ) { the_match_time( $match->start_time ); } elseif ( isset( $match->start_time ) ) { echo esc_html( $match->start_time ); } ?></span></p>
            <p class='location'><?php echo esc_html( $match->location ?? '' ); ?></p>
            <?php
            if ( ! empty( $match->post_id ) ) {
                ?>
                <p class='report'><a href='<?php the_permalink( $match->post_id ); ?>'><?php esc_html_e( 'Report', 'racketmanager' ); ?></a></p>
            <?php } ?>
        </div>
    </div>
<?php } ?>
