<?php
/**
 * Player Team administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

/** @var object $league */
/** @var object $tournament */
/** @var string $form_title */
/** @var int    $league_id */
/** @var string $season */
/** @var object $team */
/** @var array  $clubs */
/** @var string $form_action */
$action_url = '/wp-admin/admin.php?page=racketmanager-' . $league->event->competition->type .'s&amp;view=draw&amp;tournament=' . $tournament->id . '&amp;league=' . $league_id . '&amp;season=' . $season . '/';
?>
<div class="container league-block">
    <div class="row justify-content-end">
        <div class="col-auto racketmanager_breadcrumb">
            <a href="/wp-admin/admin.php?page=racketmanager-tournaments"><?php esc_html_e( 'RacketManager Tournaments', 'racketmanager' ); ?></a>
             &raquo; <a href="/wp-admin/admin.php?page=racketmanager-tournaments&amp;view=tournament&amp;tournament=<?php echo esc_attr( $tournament->id ); ?>&amp;season=<?php echo esc_attr( $tournament->season ); ?>"><?php echo esc_html( $tournament->name ); ?></a>
             &raquo; <a href="<?php echo esc_url( $action_url ); ?>"><?php echo esc_html( $league->title ); ?></a>
             &raquo; <?php echo esc_html( $form_title ); ?>
        </div>
    </div>
    <h1><?php printf( '%s - %s', esc_html( $league->title ), esc_html( $form_title ) ); ?></h1>
    <form action="<?php echo esc_url( $action_url ); ?>" class="form-control" method="post" enctype="multipart/form-data" name="team_edit" id="teamPlayerFrm" >
        <?php wp_nonce_field( 'racketmanager_manage-teams', 'racketmanager_nonce' ); ?>

        <div class="form-group">
            <label for="team"><?php esc_html_e( 'Team', 'racketmanager' ); ?></label>
            <div class="input">
                <input type="text" id="team" name="team" value="<?php echo esc_html( $team->title ); ?>" disabled />
            </div>
        </div>
        <div class="form-group">
            <label for="teamPlayer1"><?php esc_html_e( 'Player 1', 'racketmanager' ); ?></label>
            <div class="input">
                <input type="text" name="teamPlayer1" id="teamPlayer1" value="<?php echo isset( $team->player['1'] ) ? esc_html( $team->player['1'] ) : ''; ?>" size="50" />
                <input type="hidden" name="teamPlayerId1" id="teamPlayerId1" value="<?php echo isset( $team->player_id['1'] ) ? esc_html( $team->player_id['1'] ) : ''; ?>" />
            </div>
        </div>
        <?php
        if ( substr( $league->type, 1, 1 ) === 'D' ) {
            ?>
            <div class="form-group">
                <label for="teamPlayer2"><?php esc_html_e( 'Player 2', 'racketmanager' ); ?></label>
                <div class="input">
                    <input type="text" name="teamPlayer2" id="teamPlayer2" value="<?php echo isset( $team->player['2'] ) ? esc_html( $team->player['2'] ) : ''; ?>" size="50" />
                    <input type="hidden" name="teamPlayerId2" id="teamPlayerId2" value="<?php echo isset( $team->player_id['2'] ) ? esc_html( $team->player_id['2'] ) : ''; ?>" />
                </div>
            </div>
            <?php
        }
        ?>
        <div class="form-group">
            <label for="clubId"><?php esc_html_e( 'Club', 'racketmanager' ); ?></label>
            <div class="input">
                <select size="1" name="clubId" id="clubId" >
                    <option value="0" disabled <?php selected( empty( $team->club_id ) ); ?>><?php esc_html_e( 'Select club', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $clubs as $club ) {
                        ?>
                        <option value="<?php echo esc_html( $club->id ); ?>" <?php selected( $club->id, empty( $team->club_id ) ? null : $team->club_id ); ?>><?php echo esc_html( $club->name ); ?></option>
                        <?php
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="captain"><?php esc_html_e( 'Captain', 'racketmanager' ); ?></label>
            <div class="input">
                <input type="text" name="captain" id="captain" autocomplete="off" value="<?php echo esc_html( $team->captain ); ?>" disabled />
                <input type="hidden" name="captainId" id="captainId" value="<?php echo esc_html( $team->captain_id ); ?>" />
            </div>
        </div>
        <div class="form-group">
            <label for="contactno"><?php esc_html_e( 'Contact Number', 'racketmanager' ); ?></label>
            <div class="input">
                <input type="tel" name="contactno" id="contactno" autocomplete="tel" value="<?php echo esc_html( $team->contactno ); ?>" size="20" />
            </div>
        </div>
        <div class="form-group">
            <label for="contactemail"><?php esc_html_e( 'Contact Email', 'racketmanager' ); ?></label>
            <div class="input">
                <input type="email" name="contactemail" id="contactemail" autocomplete="email" value="<?php echo esc_html( $team->contactemail ); ?>" size="60" />
            </div>
        </div>

        <?php do_action( 'racketmanager_team_edit_form', $team ); ?>
        <?php do_action( 'racketmanager_team_edit_form_' . ( isset( $league->sport ) ? ( $league->sport ) : '' ), $team ); ?>

        <input type="hidden" name="team_id" id="team_id" value="<?php echo esc_attr( $team->id ); ?>" />
        <input type="hidden" name="league_id" value="<?php echo esc_attr( $league_id ); ?>" />
        <input type="hidden" name="updateLeague" value="teamPlayer" />
        <input type="hidden" name="league-tab" value="preliminary" />
        <input type="hidden" name="season" value="<?php echo esc_attr( $season ); ?>" />
        <?php
        if ( isset( $tournament ) ) {
            ?>
            <input type="hidden" name="tournament_id" value="<?php echo esc_attr( $tournament->id ); ?>" />
            <?php
        }
        ?>

        <button class="btn btn-primary" type="submit" id="actionPlayerTeam" name="action">
            <?php echo esc_html( $form_action ); ?>
        </button>
    </form>
    <div id="errorMsg" style="display:none;"></div>
</div>
