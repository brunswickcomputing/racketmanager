<?php
/**
 * Matches selection menu template
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $league */
/** @var string $season */
?>
<?php
if ( ( $league->show_match_day_selection ) && ! $league->event->competition->is_championship ) {
    ?>
    <div class="matches-selections wp-clearfix mb-3 row">
        <form method='get' action='<?php the_permalink( get_the_ID() ); ?>' id='racketmanager_match_day_selection'>
            <div class="row g-1 align-items-center">
                <input type="hidden" name="page_id" value="<?php the_ID(); ?>" />
                <input type="hidden" name="season" value="<?php echo esc_attr( $season ); ?>" />
                <input type="hidden" name="league_id" value="<?php echo esc_attr( $league->title ); ?>" />
                <input type="hidden" id="leagueId" value="<?php echo esc_attr( $league->id ); ?>" />

                <?php
                ?>
                <div class="form-floating col-auto">
                <select class="form-select col-auto" size="1" name="match_day" id="match_day">
                    <option value="-1"<?php selected( $league->match_day, -1 ); ?>><?php esc_html_e( 'Show all Matches', 'racketmanager' ); ?></option>
                    <?php
                        for ( $i = 1; $i <= $league->num_match_days; $i++ ) {
                            ?>
                            <option value='<?php echo esc_attr( $i ); ?>'<?php selected( $league->match_day, $i ); ?>>
                                <?php
                                /* Translators: %d: Match day */
                                echo esc_html( sprintf( __( '%d. Match Day', 'racketmanager' ), $i ) );
                                ?>
                            </option>
                            <?php
                        }
                        ?>
                    </select>
                    <label for="match_day"><?php esc_html_e( 'Match Day', 'racketmanager' ); ?></label>
                </div>
                <?php
                ?>
            </div>
        </form>
    </div>
    <?php
}
?>
