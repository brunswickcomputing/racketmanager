<?php
/**
 * Constitution administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

/** @var int $event_id */
if ( empty( $event->is_box ) && empty( $this->seasons ) ) {
    ?>
    <p><?php esc_html_e( 'No seasons defined', 'racketmanager' ); ?>
    <?php
} elseif ( empty( $event->leagues ) ) {
    ?>
    <p><?php esc_html_e( 'No leagues defined', 'racketmanager' ); ?>
    <?php
} elseif ( empty( $event->seasons ) ) {
    ?>
    <p><?php esc_html_e( 'No pending seasons for event', 'racketmanager' ); ?>
    <?php
} else {
    $today              = gmdate( 'Y-m-d' );
    $latest_season_dtls = $event->current_season;
    $latest_season      = $latest_season_dtls['name'];
    if ( ! empty( $event->competition->seasons[ $latest_season ]['date_start'] ) && $event->competition->seasons[ $latest_season ]['date_start'] > $today ) {
        $updates_allowed = true;
    } else {
        $updates_allowed = false;
    }
    $latest_event_season = $event->current_season['name'];
    $stop_next           = false;
    foreach ( array_reverse( $event->competition->seasons ) as $season ) {
        if ( $stop_next ) {
            $latest_event_season = $season['name'];
            break;
        }
        if ( $latest_season === $season['name'] ) {
            $stop_next = true;
        }
    }
    $teams               = $event->get_constitution(
        array(
            'season'    => $latest_season,
            'oldseason' => $latest_event_season,
        )
    );
    $constitution_action = 'update';
    $constitution_exists = true;
    if ( ! $teams ) {
        $teams               = $event->build_constitution( array( 'season' => $latest_event_season ) );
        $constitution_action = 'insert';
        $constitution_exists = false;
    }
    $leagues         = $event->get_leagues();
    $standing_status = Racketmanager_Util::get_standing_statuses();
    ?>
    <h2 class="header"><?php esc_html_e( 'Constitution', 'racketmanager' ); ?> - <?php echo esc_html( $latest_season ); ?></h2>
    <form id="teams-filter" method="post" action="">
        <div class="mb-3">
            <?php
            if ( $updates_allowed ) {
                ?>
                <input type="submit" value="<?php esc_html_e( 'Save', 'racketmanager' ); ?>" name="saveConstitution" id="saveConstitution" class="btn btn-primary action" />
                <a id="addTeams" class="btn btn-secondary" href="/wp-admin/admin.php?page=racketmanager&amp;subpage=teams&amp;league_id=<?php echo esc_html( end( $leagues )->id ); ?>&amp;season=<?php echo esc_html( $latest_season ); ?>&amp;view=constitution"><?php esc_html_e( 'Add Teams', 'racketmanager' ); ?></a>
                <?php
            }
            ?>
            <?php
            if ( $constitution_exists ) {
                ?>
                <?php
                if ( $updates_allowed ) {
                    ?>
                    <input type="submit" value="<?php esc_html_e( 'Promote/Relegate', 'racketmanager' ); ?>" name="promoteRelegate" id="promoteRelegate" class="btn btn-secondary action" />
                    <input type="submit" value="<?php esc_html_e( 'Generate Matches', 'racketmanager' ); ?>" name="generate_matches" id="generate_matches" class="btn btn-secondary action" />
                    <?php
                }
                ?>
                <button id="emailConstitution" class="btn btn-secondary" data-event-id="<?php echo esc_attr( $event->id ); ?>"><?php esc_html_e( 'Email Constitution', 'racketmanager' ); ?></button>
                <span class="notify-message" id="notifyMessage-constitution"></span>
                <?php
            }
            ?>
            <span id="notifyMessage"></span>
        </div>
        <?php wp_nonce_field( 'constitution-bulk', 'racketmanager_nonce' ); ?>

        <input type="hidden" name="js-active" value="0" class="js-active" />
        <input type="hidden" name="constitutionAction" value="<?php echo esc_html( $constitution_action ); ?>" />
        <input type="hidden" name="event_id" value="<?php echo esc_html( $event_id ); ?>" />
        <input type="hidden" name="latest_season" id="latest_season" value="<?php echo esc_html( $latest_season ); ?>" />
        <input type="hidden" name="latest_event_season" value="<?php echo esc_html( $latest_event_season ); ?>" />
        <?php
        if ( $updates_allowed ) {
            ?>
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
                    <button name="doActionConstitution" id="doActionConstitution" class="btn btn-secondary"><?php esc_html_e( 'Apply', 'racketmanager' ); ?></button>
                </div>
            </div>
            <?php
        }
        ?>
        <table class="table table-striped" title="RacketManager" aria-label="constitution table">
            <thead class="table-dark">
                <tr>
                    <th scope="col" class="check-column"><label for="check-all-teams" class="visually-hidden"><?php esc_html_e( 'Check all', 'racketmanager' ); ?></label><input type="checkbox" id="check-all-teams" onclick="Racketmanager.checkAll(document.getElementById('teams-filter'));" /></th>
                    <th scope="col"><?php esc_html_e( 'Previous League', 'racketmanager' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'New League', 'racketmanager' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Team', 'racketmanager' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Status', 'racketmanager' ); ?></th>
                    <th scope="col" class="column-num"><?php esc_html_e( 'Previous Rank', 'racketmanager' ); ?></th>
                    <th scope="col" class="column-num"><?php esc_html_e( 'Rank', 'racketmanager' ); ?></th>
                    <th scope="col" class="column-num"><?php esc_html_e( 'Points', 'racketmanager' ); ?></th>
                    <th scope="col" class="column-num"><?php esc_html_e( 'Rating', 'racketmanager' ); ?></th>
                    <th scope="col"><?php esc_html_e( 'Entered', 'racketmanager' ); ?></th>
                </tr>
            </thead>
            <tbody id="the-list" class="standings-table sortable">
                <?php
                if ( $teams ) {
                    $class = '';
                    foreach ( $teams as $team ) {
                        $class = ( 'alternate' === $class ) ? '' : 'alternate';
                        ?>
                        <tr class="<?php echo esc_html( $class ); ?>">
                            <th scope="row" class="check-column">
                                <label for="table-<?php echo esc_html( $team->table_id ); ?>" class="visually-hidden"><?php esc_html_e( 'Check all', 'racketmanager' ); ?></label><input type="checkbox" value="<?php echo esc_html( $team->table_id ); ?>" name="table[<?php echo esc_html( $team->table_id ); ?>]" id="table-<?php echo esc_html( $team->table_id ); ?>" />
                                <input type="hidden" name="table_id[<?php echo esc_html( $team->table_id ); ?>]" value="<?php echo esc_html( $team->table_id ); ?>" />
                            </th>
                            <td>
                                <?php echo esc_html( $team->old_league_title ); ?>
                                <input type="hidden" name="original_league_id[<?php echo esc_html( $team->table_id ); ?>]" value=<?php echo esc_html( $team->old_league_id ); ?> />
                            </td>
                            <td>
                                <label>
                                    <select size=1 name="league_id[<?php echo esc_html( $team->table_id ); ?>]">
                                        <?php foreach ( $leagues as $league ) { ?>
                                            <option value="<?php echo esc_html( $league->id ); ?>" <?php selected( $league->id, $team->league_id ); ?>><?php echo esc_html( $league->title ); ?></option>
                                        <?php } ?>
                                    </select>
                                </label>
                            </td>
                            <td>
                                <?php echo esc_html( $team->title ); ?>
                                <input type="hidden" name="team_id[<?php echo esc_html( $team->table_id ); ?>]" id="team_id[<?php echo esc_html( $team->table_id ); ?>]" value=<?php echo esc_html( $team->team_id ); ?> />
                            </td>
                            <td>
                                <label>
                                    <select size=1 name="status[<?php echo esc_html( $team->table_id ); ?>]">
                                        <option value="" <?php selected( '', $team->status ); ?>></option>
                                        <?php
                                        foreach ( $standing_status as $key => $value ) {
                                            ?>
                                            <option value="<?php echo esc_html( $key ); ?>" <?php selected( $key, $team->status ); ?>><?php echo esc_html( $value ); ?></option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                </label>
                            </td>
                            <td class="column-num">
                                    <?php echo esc_html( $team->old_rank ); ?>
                                <input type="hidden" name="old_rank[<?php echo esc_html( $team->table_id ); ?>]" id="old_rank[<?php echo esc_html( $team->table_id ); ?>]" value=<?php echo esc_html( $team->old_rank ); ?> />
                            </td>
                            <td class="column-num">
                                <label for="rank[<?php echo esc_html( $team->table_id ); ?>]" class="visually-hidden"><?php esc_html_e( 'Check', 'racketmanager' ); ?></label><input type="text" size="2" class="rank-input" name="rank[<?php echo esc_html( $team->table_id ); ?>]" id="rank[<?php echo esc_html( $team->table_id ); ?>]" value=<?php echo esc_html( $team->rank ); ?> />
                            </td>
                            <td class="column-num" name="points[<?php echo esc_html( $team->table_id ); ?>]">
                                <?php echo esc_html( $team->points_plus + $team->add_points ); ?>
                                <input type="hidden" name="points_plus[<?php echo esc_html( $team->table_id ); ?>]" value=<?php echo esc_html( $team->points_plus ); ?> />
                            </td>
                            <td class="column-num">
                                <?php echo isset( $team->rating ) ? esc_html( $team->rating ) : null; ?>
                                <input type="hidden" name="rating[<?php echo esc_html( $team->table_id ); ?>]" value=<?php echo isset( $team->rating ) ? esc_html( $team->rating ) : null; ?> />
                            </td>
                            <td>
                                <label>
                                    <select size=1 name="profile[<?php echo esc_html( $team->table_id ); ?>]">
                                        <option value="0" <?php selected( '0', $team->profile ); ?>><?php esc_html_e( 'Pending', 'racketmanager' ); ?></option>
                                        <option value="1" <?php selected( '1', $team->profile ); ?>><?php esc_html_e( 'Confirmed', 'racketmanager' ); ?></option>
                                        <option value="2" <?php selected( '2', $team->profile ); ?>><?php esc_html_e( 'New team', 'racketmanager' ); ?></option>
                                        <option value="3" <?php selected( '3', $team->profile ); ?>><?php esc_html_e( 'Withdrawn', 'racketmanager' ); ?></option>
                                    </select>
                                </label>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </tbody>
        </table>
    </form>
    <?php
}
?>
<script type="text/javascript">
    document.getElementById('emailConstitution').addEventListener('click', function (e) {
        let eventId = this.dataset.eventId;
        Racketmanager.emailConstitution(e, eventId)
    });
</script>
