<?php
/**
 * Tournament overview administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var object $tournament */
?>
    <div class="row mb-3">
        <div class="col-12 col-md-6">
            <table class="table table-borderless">
                <tbody>
                    <tr>
                        <th scope="col" class="col-6 col-md-3"><?php esc_html_e( 'Venue', 'racketmanager' ); ?></th>
                        <td class="col-6"><?php echo esc_html( $tournament->venue_name ); ?></td>
                    </tr>
                    <tr>
                        <th scope="col" class="col-6 col-md-3"><?php esc_html_e( 'Events', 'racketmanager' ); ?></th>
                        <td class="col-auto"><?php echo esc_html( count( $tournament->events ) ); ?></td>
                    </tr>
                    <tr>
                        <th scope="col" class="col-6 col-md-3"><?php esc_html_e( 'Entries', 'racketmanager' ); ?></th>
                        <td class="col-auto"><?php echo esc_html( $tournament->num_entries ); ?></td>
                    </tr>
                    <tr>
                        <th scope="col" class="col-6 col-md-3"><?php esc_html_e( 'Code', 'racketmanager' ); ?></th>
                        <td class="col-auto"><?php echo esc_html( $tournament->competition_code ); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="col-12 col-md-6">
            <table class="table table-borderless">
                <tbody>
                    <tr>
                        <th scope="col" class="col-6 col-md-3"><?php esc_html_e( 'Entry open', 'racketmanager' ); ?></th>
                        <td class="col-auto"><?php echo esc_html( $tournament->date_open ); ?></td>
                    </tr>
                    <tr>
                        <th scope="col" class="col-6 col-md-3"><?php esc_html_e( 'Entry closed', 'racketmanager' ); ?></th>
                        <td class="col-auto"><?php echo esc_html( $tournament->date_closing ); ?></td>
                    </tr>
                    <tr>
                        <th scope="col" class="col-6 col-md-3"><?php esc_html_e( 'Tournament start', 'racketmanager' ); ?></th>
                        <td class="col-auto"><?php echo esc_html( $tournament->date_start ); ?></td>
                    </tr>
                    <tr>
                        <th scope="col" class="col-6 col-md-3"><?php esc_html_e( 'Tournament end', 'racketmanager' ); ?></th>
                        <td class="col-auto"><?php echo esc_html( $tournament->date ); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-auto">
            <a class="btn btn-primary" href="/wp-admin/admin.php?page=racketmanager-tournaments&amp;view=modify&amp;tournament=<?php echo esc_html( $tournament->id ); ?> "><?php esc_html_e( 'Edit tournament', 'racketmanager' ); ?></a>
            <?php
            if ( $tournament->is_open ) {
                ?>
                <button class="btn btn-secondary" id="notifyOpen" data-tournament-id="<?php echo esc_html( $tournament->id ); ?>"><?php esc_html_e( 'Notify open', 'racketmanager' ); ?></button>
                <?php
            }
            if ( ! empty( $tournament->competition_code ) && $tournament->is_complete ) {
                ?>
                <a href="/index.php?tournament_id=<?php echo esc_html( $tournament->id ); ?>&season=<?php echo esc_html( $tournament->season ); ?>&competition_code=<?php echo esc_html( $tournament->competition_code ); ?>&racketmanager_export=report_results" class="btn btn-secondary" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_html_e( 'Report results', 'racketmanager' ); ?>" >
                    <span class="nav-link__value">
                        <?php esc_html_e( 'Report results', 'racketmanager' ); ?>
                    </span>
                </a>
                <?php
            }
            ?>
        </div>
    </div>
    <div class="alert_rm" id="alert-tournaments" style="display:none;">
        <div class="alert__body">
            <div class="alert__body-inner" id="alert-tournaments-response">
            </div>
        </div>
    </div>
<script type="text/javascript">
    document.getElementById('notifyOpen').addEventListener('click', function (e) {
        let tournamentId = this.dataset.tournamentId;
        Racketmanager.notifyTournamentEntryOpen(e, tournamentId);
    });
</script>
