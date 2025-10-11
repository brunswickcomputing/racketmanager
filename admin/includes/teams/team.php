<?php
/**
 * Team administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

use Racketmanager\util\Util_Lookup;

/** @var object $league */
/** @var string $form_title */
/** @var int    $league_id */
/** @var int    $club_id */
/** @var string $season */
/** @var object $team */
/** @var array  $clubs */
/** @var string $form_action */
/** @var string $edit */
/** @var array  $match_days */
?>
<div class="container league-block">
    <div class="row justify-content-end">
        <div class="col-auto racketmanager_breadcrumb">
            <a href="/wp-admin/admin.php?page=racketmanager"><?php esc_html_e( 'RacketManager', 'racketmanager' ); ?></a>
                <?php
                if ( $league ) {
                    ?>
                    &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_attr( $league->event->competition->type ); ?>s&amp;view=league&amp;league_id=<?php echo esc_html( $league->id ); ?>"><?php echo esc_html( $league->title ); ?></a>
                    <?php
                }
                ?>
                &raquo; <?php echo esc_html( $form_title ); ?>
        </div>
    </div>
    <?php
    if ( $league ) {
        ?>
        <h1><?php echo esc_html( $league->title ) . ' - ' . esc_html( $form_title ); ?></h1>
        <?php
    } else {
        ?>
        <h1><?php echo esc_html( $form_title ); ?></h1>
        <?php
    }
    if ( $league ) {
        $action_form = 'admin.php?page=racketmanager-' . $league->event->competition->type . 's&amp;view=league&amp;league_id=' . $league_id . '&amp;season=' . $season;
    } else {
        $action_form = 'admin.php?page=racketmanager-clubs&amp;view=teams';
        if ( $club_id ) {
            $action_form .= '&amp;club_id=' . $club_id;
        }
    }
    ?>
    <form action="<?php echo esc_html( $action_form ); ?>" method="post" enctype="multipart/form-data" name="team_edit" class="form-control">
        <?php wp_nonce_field( 'racketmanager_manage-teams', 'racketmanager_nonce' ); ?>
        <div class="form-group">
            <label for="team"><?php esc_html_e( 'Team', 'racketmanager' ); ?></label>
            <div class="input">
                <input type="text" id="team" name="team" readonly value="<?php echo esc_html( $team->title ); ?>" size="30" placeholder="<?php esc_html_e( 'Add Team', 'racketmanager' ); ?>"/>
            </div>
        </div>
        <div class="form-group">
            <label for="clubId"><?php esc_html_e( 'Club', 'racketmanager' ); ?></label>
            <?php
            if ( $league && $edit ) {
                ?>
                <input type="hidden" name="clubId" value="<?php echo esc_html( $team->club_id ); ?>" />
                <?php
            }
            ?>
            <div class="input">
                <select <?php echo ( $league && $edit ) ? ' disabled ' : null; ?> name="clubId" id="clubId">
                    <option disabled <?php selected( 1, empty( $club->id ) ? 1 : null ); ?>><?php esc_html_e( 'Select club', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $clubs as $club ) {
                        ?>
                        <option value="<?php echo esc_html( $club->id ); ?>" <?php selected( $club->id, $team->club_id ?? null ); ?>>
                            <?php echo esc_html( $club->name ); ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="form-group">
            <label for="team_type"><?php esc_html_e( 'Type', 'racketmanager' ); ?></label>
            <?php
            if ( $league && $edit ) {
                ?>
                <input type="hidden" name="team_type" value="<?php echo esc_html( $team->type ); ?>" />
                <?php
            }
            ?>
            <div class="input">
                <select size='1' <?php echo ( $league && $edit ) ? ' disabled ' : null; ?> required name='team_type' id='team_type'>
                    <option disabled <?php selected( 1, empty( $team->type ) ? 1 : null ); ?> value="1"> ?>>
                        <?php esc_html_e( 'Select', 'racketmanager' ); ?>
                    </option>
                    <?php
                    $event_types = Util_Lookup::get_event_types();
                    foreach ( $event_types as $key => $event_type ) {
                        ?>
                        <option value="<?php echo esc_attr( $key ); ?>"<?php selected( $team->type, $key ); ?>><?php echo esc_html( $event_type ); ?></option>
                        <?php
                    }
                    ?>
                </select>
            </div>
        </div>
        <?php if ( $league ) { ?>
            <div class="form-group">
                <label for="captain"><?php esc_html_e( 'Captain', 'racketmanager' ); ?></label>
                <div class="input">
                    <input type="text" name="captain" id="captain" autocomplete="off" value="<?php echo esc_html( $team->captain ); ?>" size="30" /><input type="hidden" name="captainId" id="captainId" value="<?php echo esc_html( $team->captain_id ); ?>" />
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
                    <input type="email" name="contactemail" id="contactemail" autocomplete="email" value="<?php echo esc_html( $team->contactemail ); ?>" size="30" />
                </div>
            </div>
            <div class="form-group">
                <label for="matchtime"><?php esc_html_e( 'Match Time', 'racketmanager' ); ?></label>
                <div class="input">
                    <input type="time" name="matchtime" id="matchtime" value="<?php echo esc_html( $team->match_time ); ?>" size="5" />
                </div>
            </div>
            <div class="form-group">
                <label for="matchday"><?php esc_html_e( 'Match Day', 'racketmanager' ); ?></label>
                <div class="input">
                <select size="1" name="matchday" id="matchday" >
                    <option value=""><?php esc_html_e( 'Select match day', 'racketmanager' ); ?></option>
                    <?php
                    foreach ( $match_days as $key => $match_day ) { ?>
                        <option value="<?php echo esc_html( $key ); ?>" <?php selected( $match_day, $team->match_day ?? null ); ?>><?php echo esc_html( $match_day ); ?></option>
                        <?php
                    }
                    ?>
                </select>
                </div>
            </div>
        <?php } ?>
        <?php do_action( 'racketmanager_team_edit_form', $team ); ?>
        <?php
        if ( $league ) {
            do_action( 'racketmanager_team_edit_form_' . ( isset( $league->sport ) ? ( $league->sport ) : '' ), $team );
        }
        ?>
        <input type="hidden" name="team_id" id="team_id" value="<?php echo esc_html( $team->id ); ?>" />
        <input type="hidden" name="league_id" value="<?php echo esc_html( $league_id ); ?>" />
        <input type="hidden" name="updateLeague" value="team" />
        <input type="hidden" name="league-tab" value="preliminary" />
        <input type="hidden" name="season" value="<?php echo esc_html( $season ); ?>" />
        <?php
        if ( isset( $club_id ) ) {
            ?>
            <input type="hidden" name="club_id" value="<?php echo esc_html( $club_id ); ?>" />
            <?php
        }
        if ( $edit ) {
            ?>
            <input type="hidden" name="editTeam" value="team" />
            <?php
        }
        ?>
        <input type="submit" name="action" value="<?php echo esc_html( $form_action ); ?>" class="btn btn-primary" />
        <div id="feedback" class="feedback"></div>
    </form>
</div>
