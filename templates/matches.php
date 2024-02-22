<?php
/**
 * Matches table template
 */

namespace Racketmanager;

?>
<div class="racketmanager-container">

<?php
    if ( is_single_match() ) {
        the_single_match(); 
    } else {
        include('matches-selections.php');
?>

<?php if ( have_matches() ) { ?>
    <table class='racketmanager matchtable' summary='' title='<?php _e( 'Match Plan', 'racketmanager' ) ?> <?php the_league_title() ?>'>
        <thead>
            <tr>
                <th class='match'><?php _e( 'Match', 'racketmanager' ) ?></th>
                <th class='score'><?php _e( 'Score', 'racketmanager' ) ?></th>
            </tr>
        </thead>
        <tbody>
    <?php while( have_matches() ) { the_match(); ?>

                <tr class='<?php the_match_class() ?>'>
                    <td class='match'><?php the_match_date() ?> <?php the_match_time() ?> <?php the_match_location() ?><br /><?php the_match_title() ?> <?php the_match_report() ?></td>
                    <td class='score' valign='bottom'><?php the_match_score() ?></td>
                </tr>

            <?php } ?>
        </tbody>
    </table>

    <?php the_matches_pagination() ?>

	<?php } ?>

<?php } ?>
</div>
