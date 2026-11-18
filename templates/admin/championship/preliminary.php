<?php
/**
 * Admin screen for championship teams.
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

use BackedEnum;
use Racketmanager\Admin\View_Models\Cup_Draw_Page_View_Model;
use Racketmanager\Admin\View_Models\League_Standings_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Draw_Page_View_Model;
use stdClass;

/** @var Tournament_Draw_Page_View_Model|Cup_Draw_Page_View_Model|League_Standings_Page_View_Model|stdClass $vm */
// Preferred input (when included from tournament draw): $vm.
$draw_vm = isset( $vm ) && ( $vm instanceof Tournament_Draw_Page_View_Model ) ? $vm : null;

// BC fallback: if $draw_vm isn't present, rely on locals set by the parent template.
if ( $draw_vm ) {
    $league     = $draw_vm->league;
    $tournament = $draw_vm->tournament;
    $season     = $draw_vm->season;
} else {
    $league     = $league ?? ( $vm->league ?? null );
    $tournament = $tournament ?? ( $vm->tournament ?? null );
    $season     = $season ?? ( $vm->season ?? null );
}

/** @var object $league */
/** @var string $season */
/** @var object|null $tournament */
?>
<?php
$prelim_section = 'cups';
if ( ! empty( $tournament ) || 'racketmanager-tournaments' === ( $vm->page ?? ( $page ?? '' ) ) ) {
    $prelim_section = 'tournaments';
} elseif ( ! empty( $league->event->competition->is_league ) ) {
    $prelim_section = 'leagues';
}
$prelim_post_args = [
    'league_id' => (string) $league->id,
    'season'    => $season,
];
if ( 'tournaments' === $prelim_section && ! empty( $tournament ) ) {
    $prelim_post_args['tournament'] = (string) $tournament->id;
    $prelim_post_args['league']     = (string) $league->id;
} elseif ( 'cups' === $prelim_section && isset( $league->event->competition->id ) ) {
    $prelim_post_args['competition_id'] = (string) $league->event->competition->id;
    $prelim_post_args['league']         = (string) $league->id;
}
?>
<div class="championship-block">
    <form action="<?php echo esc_url( manage_url( $prelim_section, $draw_vm ? 'draw' : 'preliminary', $prelim_post_args ) ); ?>" method="post" class="form-control mb-3">
        <?php wp_nonce_field( 'racketmanager_championship_proceed', 'racketmanager_proceed_nonce' ); ?>
        <input type="hidden" name="tab" value="preliminary" />
        <input type="hidden" name="action" value="startFinals" />
        <p><?php esc_html_e( 'After adding the teams and arranging the rankings then ', 'racketmanager' ); ?></p>
        <div class="mb-3">
            <?php
            if ( $league->championship->is_consolation ) {
                $competition_type = $league->event->competition->type instanceof BackedEnum ? $league->event->competition->type->value : (string) ( $league->event->competition->type ?? '' );
                $add_teams_args   = [
                    'league_id' => (string) $league->id,
                    'season'    => $season,
                ];
                if ( empty( $tournament ) ) {
                    $add_teams_args['competition_id'] = (string) $league->event->competition->id;
                } else {
                    $add_teams_args['tournament'] = (string) $tournament->id;
                }
                $link_ref = manage_url( $competition_type . 's', 'teams', $add_teams_args );
                ?>
                <a class="btn btn-secondary" href="<?php echo esc_url( $link_ref ); ?>"><?php esc_html_e( 'Add Teams', 'racketmanager' ); ?></a>
                <?php
            }
            ?>
            <button class="btn btn-primary"><?php esc_html_e( 'Proceed to Final Rounds', 'racketmanager' ); ?></button>
        </div>
        <p><?php esc_html_e( 'Afterwards changes to rankings will NOT affect the final results', 'racketmanager' ); ?></p>
    </form>
    <?php
    if ( $league->is_championship && ! empty( $league->championship->num_seeds ) ) {
        ?>
        <div class="mb-3">
            <span><?php /* translators: %d: number of seeds */ printf( esc_html__( 'The top %d teams will be seeded and are highlighted below.', 'racketmanager' ), esc_attr( $league->championship->num_seeds ) ); ?></span>
        </div>
        <?php
    }
    if ( ! empty( $draw_vm ) ) {
        $teams = $draw_vm->teams;
    } elseif ( ! empty( $vm->teams ) ) {
        $teams = $vm->teams;
    } elseif ( ! isset( $teams ) || ! is_array( $teams ) ) {
        $teams = array();
    }
    require RACKETMANAGER_PATH . 'templates/admin/league/standings.php';
    ?>
</div>
