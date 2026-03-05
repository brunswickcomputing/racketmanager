<?php
/**
 * Tournament events administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Tournament_Overview_Page_View_Model;

// Preferred input: $vm from the overview page.
$vm = isset( $vm ) && ( $vm instanceof Tournament_Overview_Page_View_Model ) ? $vm : null;

// BC fallback: allow legacy locals if $vm isn't provided.
if ( $vm ) {
    $tournament = $vm->tournament;
    $events     = $vm->events;
}

/** @var object $tournament */
/** @var array  $events */
?>
    <div class="row">
        <div class="col-12 col-md-6">
            <table class="table table-striped">
                <thead class="table-dark">
                    <tr>
                        <th><?php esc_html_e( 'Draw', 'racketmanager' ); ?></th>
                        <th>
                            <?php
                                esc_html_e( 'Entries', 'racketmanager' );
                            ?>
                        </th>
                        <th>
                            <?php
                                esc_html_e( 'Draw Size', 'racketmanager' );
                            ?>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    foreach ( $events as $name => $event ) {
                        ?>
                        <tr>
                            <td colspan=3><?php echo esc_html( $name ); ?></td>
                        </tr>
                        <?php
                        foreach ( $event as $league ) {
                            ?>
                            <tr>
                                <td><a href="/wp-admin/admin.php?page=racketmanager-tournaments&view=draw&tournament=<?php echo esc_attr( $tournament->id ); ?>&league=<?php echo esc_attr( $league->league_id ); ?>&season=<?php echo esc_attr( $tournament->season ); ?>"><?php echo esc_html( $league->league_name ); ?></a></td>
                                <td><?php echo esc_html( $league->total_entries ); ?></td>
                                <td>
                                    <?php
                                    echo esc_html( $league->draw_size );
                                    ?>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
