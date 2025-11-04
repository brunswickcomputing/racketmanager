<?php
/**
 * Team list administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

/** @var string $view */
/** @var object $league */
/** @var int $league_id */
/** @var string $season */
/** @var int $tournament_id */
/** @var object $tournament */
/** @var array $teams */
/** @var string $type */
if ( 'constitution' === $type ) {
    $page_title = __( 'Add Teams to Constitution', 'racketmanager' );
    $page_link  = $league->event->name;
    $breadcrumb = 'show-event&amp;event_id=' . $league->event_id;
    $link_ref = 'admin.php?page=racketmanager-leagues&view=constitution&amp;event_id=' . $league->event_id . '&amp;season=' . $season;
} else {
    $page_title = __( 'Add Teams to League', 'racketmanager' );
    $page_link  = $league->title;
    $breadcrumb = 'show-league&amp;league_id=' . $league->id;
    $link_ref   = 'admin.php?page=racketmanager-' . $league->event->competition->type . 's&amp;season=' . $season;
    $link_ref .= match ($league->event->competition->type) {
        'cup' => '&amp;competition_id=' . $league->event->competition->id . '&amp;view=draw&amp;league=' . $league->id,
        'tournament' => '&amp;tournament=' . $tournament_id . '&amp;view=draw&amp;league=' . $league->id,
        default => '&view=league&amp;league_id=' . $league->id,
    };
}
$main_title = $page_link . ' - ' . $page_title;
?>
<div class="container">
    <div class="row justify-content-end">
        <div class="col-auto racketmanager_breadcrumb">
            <?php
            switch ( $league->event->competition->type ) {
                case 'cup':
                    ?>
                    <a href="/admin.php?page=racketmanager-"><?php esc_html_e( 'RacketManager', 'racketmanager' ); ?></a> &raquo; <a href="/admin.php?page=racketmanager&amp;subpage=<?php echo esc_html( $breadcrumb ); ?>"><?php echo esc_html( $page_link ); ?></a> &raquo; <?php echo esc_html( $page_title ); ?>
                    <?php
                    break;
                case 'tournament':
                    ?>
                    <a href="/admin.php?page=racketmanager-tournaments"><?php esc_html_e( 'Tournaments', 'racketmanager' ); ?></a> &raquo; <a href="/admin.php?page=racketmanager-tournaments&amp;view=tournament&amp;tournament=<?php echo esc_attr( $tournament->id ); ?>&amp;season=<?php echo esc_attr( $tournament->season ); ?>"><?php echo esc_html( $tournament->name ); ?></a> &raquo; <a href="/admin.php?page=racketmanager-tournaments&amp;view=draw&amp;tournament=<?php echo esc_attr( $tournament->id ); ?>&amp;season=<?php echo esc_attr( $tournament->season ); ?>&amp;league=<?php echo esc_attr( $league->id ); ?>"><?php echo esc_html( $league->title ); ?></a> &raquo; <?php echo esc_html( $page_title ); ?>
                    <?php
                    break;
                default:
                    ?>
                    <a href="/admin.php?page=racketmanager-<?php echo esc_attr( $league->event->competition->type ); ?>s"><?php echo esc_html( ucfirst( $league->event->competition->type ) ); ?>s</a> &raquo; <a href="/admin.php?page=racketmanager-<?php echo esc_attr( $league->event->competition->type ); ?>s&amp;view=seasons&amp;competition_id=<?php echo esc_html( $league->event->competition->id ); ?>"><?php echo esc_html( $league->event->competition->name ); ?></a> &raquo; <a href="/admin.php?page=racketmanager-<?php echo esc_html( $league->event->competition->type ); ?>s&amp;view=overview&amp;competition_id=<?php echo esc_attr( $league->event->competition->id ); ?>&amp;season=<?php echo esc_attr( $season ); ?>"><?php echo esc_html( $season ); ?></a> &raquo; <a href="/admin.php?page=racketmanager-<?php echo esc_attr( $league->event->competition->type ); ?>s&amp;view=constitution&amp;event_id=<?php echo esc_html( $league->event_id ); ?>"><?php echo esc_html( $league->event->name ); ?></a> &raquo; <?php echo esc_html( $page_title ); ?>
                    <?php
                    break;
            }
            ?>
        </div>
    </div>
    <h1><?php echo esc_html( $main_title ); ?></h1>
    <form id="teams-filter" action="<?php echo esc_html( $link_ref ); ?>" method="post" enctype="multipart/form-data" name="teams_add">
        <?php wp_nonce_field( 'racketmanager_add-teams-bulk', 'racketmanager_nonce' ); ?>
        <input type="hidden" name="event_id" value="<?php echo esc_html( $league->event->id ); ?>" />
        <input type="hidden" name="league_id" value="<?php echo esc_html( $league_id ); ?>" />
        <input type="hidden" name="season" value="<?php echo esc_html( $season ); ?>" />
        <legend><?php esc_html_e( 'Select Teams to Add', 'racketmanager' ); ?></legend>
        <div class="row gx-3 mb-3 align-items-center">
            <!-- Bulk Actions -->
            <div class="col-auto">
                <label>
                    <select class="form-select" name="action">
                        <option value="addTeamsToLeague"><?php esc_html_e( 'Add', 'racketmanager' ); ?></option>
                    </select>
                </label>
            </div>
            <div class="col-auto">
                <button name="doAddTeamToLeague" id="doAddTeamToLeague" class="btn btn-primary"><?php esc_html_e( 'Apply', 'racketmanager' ); ?></button>
            </div>
        </div>
        <div class="container">
            <div class="row table-header">
                <div class="col-1 check-column"><label for="checkAll" class="visually-hidden"><?php esc_html_e( 'Check all', 'racketmanager' ); ?></label><input type="checkbox" id="checkAll" onclick="Racketmanager.checkAll(document.getElementById('teams-filter'));" /></div>
                <div class="col-1 column-num">ID</div>
                <div class="col-3"><?php esc_html_e( 'Title', 'racketmanager' ); ?></div>
                <div class="col-3"><?php esc_html_e( 'Affiliated Club', 'racketmanager' ); ?></div>
                <div class="col-3"><?php esc_html_e( 'Stadium', 'racketmanager' ); ?></div>
            </div>
            <?php
            if ( $teams ) {
                $class = '';
                foreach ( $teams as $team ) {
                    $club_name = $team->club->shortcode ?? null;
                    ?>
                    <?php $class = ( 'alternate' === $class ) ? '' : 'alternate'; ?>
                    <div class="row table-row <?php echo esc_html( $class ); ?>">
                        <div class="col-1 check-column">
                            <label for="team-<?php echo esc_html( $team->id ); ?>" class="visually-hidden"><?php esc_html_e( 'Check', 'racketmanager' ); ?></label><input type="checkbox" value="<?php echo esc_html( $team->id ); ?>" name="team[<?php echo esc_html( $team->id ); ?>]" id="team-<?php echo esc_html( $team->id ); ?>" />
                        </div>
                        <div class="col-1 column-num"><?php echo esc_html( $team->id ); ?></div>
                        <div class="col-3"><?php echo esc_html( $team->title ); ?></div>
                        <div class="col-3"><?php echo esc_html( $club_name ); ?></div>
                        <div class="col-3"><?php echo esc_html( $team->stadium ); ?></div>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </form>
</div>
