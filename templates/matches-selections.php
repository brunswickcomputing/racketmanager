<?php
/**
 * Matches selection menu template
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Application\League\DTOs\League_View_State;

/** @var object $league */
/** @var League_View_State|null $view_state */
/** @var string $season */
/** @var int|string $post_id */
/** @var string $form_action */
$view_state  = $view_state ?? $vars['view_state'] ?? $vm->view_state ?? null;
$season      = $season ?? $vars['season'] ?? $vm->season ?? '';
$post_id     = $post_id ?? $vars['post_id'] ?? $vm->post_id ?? 0;
$form_action = $form_action ?? $vars['form_action'] ?? $vm->form_action ?? '';
?>
<?php
if ( ( $view_state?->show_match_day_selection ?? false ) && ! $league->event->competition->is_championship ) {
    ?>
    <div class="matches-selections wp-clearfix mb-3 row">
        <form method='get' action='<?php echo esc_url( $form_action ?? '' ); ?>' id='racketmanager_match_day_selection'>
            <div class="row g-1 align-items-center">
                <input type="hidden" name="page_id" value="<?php echo esc_attr( (string) ( $post_id ?? 0 ) ); ?>" />
                <input type="hidden" name="season" value="<?php echo esc_attr( $season ); ?>" />
                <input type="hidden" name="league_id" value="<?php echo esc_attr( $league->title ); ?>" />
                <input type="hidden" id="leagueId" value="<?php echo esc_attr( $league->id ); ?>" />

                <?php
                ?>
                <div class="form-floating col-auto">
                <select class="form-select col-auto" size="1" name="match_day" id="match_day">
                    <option value="-1"<?php selected( $view_state->current_match_day, -1 ); ?>><?php esc_html_e( 'Show all Matches', 'racketmanager' ); ?></option>
                    <?php
                        for ( $i = 1; $i <= $league->num_match_days; $i++ ) {
                            ?>
                            <option value='<?php echo esc_attr( $i ); ?>'<?php selected( $view_state->current_match_day, $i ); ?>>
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
