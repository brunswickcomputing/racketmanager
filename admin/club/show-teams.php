<?php
/**
 * Teams main page administration panel
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

use Racketmanager\util\Util_Lookup;

/** @var object $club */
/** @var int    $club_id */
/** @var array  $teams */
?>
<div class="container">
    <div class="row justify-content-end">
        <div class="col-auto racketmanager_breadcrumb">
            <a href="/wp-admin/admin.php?page=racketmanager-clubs"><?php esc_html_e( 'Clubs', 'racketmanager' ); ?></a> &raquo; <?php echo esc_html( $club->shortcode ); ?>  &raquo; <?php esc_html_e( 'Teams', 'racketmanager' ); ?>
        </div>
    </div>
    <h1><?php esc_html_e( 'Teams', 'racketmanager' ); ?> - <?php echo esc_html( $club->name ); ?></h1>

    <!-- View Teams -->
    <div class="mb-3">
        <form id="teams-filter" method="post" action="" class="form-control">
            <?php wp_nonce_field( 'racketmanager_teams-bulk', 'racketmanager_nonce' ); ?>
            <div class="row gx-3 mb-3 align-items-center">
                <!-- Bulk Actions -->
                <div class="col-auto">
                    <label>
                        <select class="form-select" name="action">
                            <option value="-1" selected="selected"><?php esc_html_e( 'Bulk Actions', 'racketmanager' ); ?></option>
                            <option value="delete"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></option>
                        </select>
                    </label>
                </div>
                <div class="col-auto">
                    <button name="doTeamDel" id="doTeamDel" class="btn btn-secondary"><?php esc_html_e( 'Apply', 'racketmanager' ); ?></button>
                </div>
            </div>
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th class="col-1 check-column"><label for="selectAll" class="visually-hidden"><?php esc_html_e( 'Check all', 'racketmanager' ); ?></label><input type="checkbox" name="selectAll" id="selectAll" onclick="Racketmanager.checkAll(document.getElementById('teams-filter'));" /></th>
                        <th class="col-1 column-num">ID</th>
                        <th class="col-3"><?php esc_html_e( 'Title', 'racketmanager' ); ?></th>
                        <th class="col-3"><?php esc_html_e( 'Stadium', 'racketmanager' ); ?></th>
                    </tr>
                </thead>
                <?php
                if ( $teams ) {
                    ?>
                    <tbody>
                        <?php
                        foreach ( $teams as $team ) {
                        ?>
                        <tr>
                            <td class="col-1 check-column">
                                <label for="team-<?php echo esc_html( $team->id ); ?>" class="visually-hidden"><?php esc_html_e( 'Check', 'racketmanager' ); ?></label><input type="checkbox" value="<?php echo esc_html( $team->id ); ?>" name="team[<?php echo esc_html( $team->id ); ?>]" id="team-<?php echo esc_html( $team->id ); ?>" />
                            </td>
                            <td class="col-1 column-num"><?php echo esc_html( $team->id ); ?></td>
                            <td class="col-3 team-name">
                                <a href="/wp-admin/admin.php?page=racketmanager-clubs&amp;view=team&amp;edit=<?php echo esc_html( $team->id ); ?>&amp;club_id=<?php echo esc_html( $team->club_id ); ?>">
                                    <?php echo esc_html( $team->title ); ?>
                                </a>
                            </td>
                            <td class="col-3"><?php echo esc_html( $team->stadium ); ?></td>
                        </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                    <?php
                }
                ?>
            </table>
        </form>
    </div>
    <!-- Add New Team -->
    <div class="mb-3">
        <h3><?php esc_html_e( 'Add Team', 'racketmanager' ); ?></h3>
        <form action="" method="post" class="form-control">
            <?php wp_nonce_field( 'racketmanager_add-team', 'racketmanager_nonce' ); ?>
            <div class="form-floating mb-3">
                <select class="form-select" size='1' required="required" name='team_type' id='team_type'>
                    <option value=""><?php esc_html_e( 'Select event type', 'racketmanager' ); ?></option>
                    <?php
                    $event_types = Util_Lookup::get_event_types();
                    foreach ( $event_types as $key => $event_type ) {
                        ?>
                        <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $event_type ); ?></option>
                        <?php
                    }
                    ?>
                </select>
                <label for="team_type"><?php esc_html_e( 'Type', 'racketmanager' ); ?></label>
            </div>
            <input type="hidden" name="club" value=<?php echo esc_html( $club->id ); ?> />
            <input type="hidden" name="addTeam" value="team" />
            <input type="submit" name="addTeam" value="<?php esc_html_e( 'Add Team', 'racketmanager' ); ?>" class="btn btn-primary" />

        </form>
    </div>
</div>
