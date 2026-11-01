<?php
/**
 * Tournament events administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var object $competition */
/** @var string $season */
/** @var array $competition_events */
/** @var array<int|string, string> $draw_sizes */

$competition_events = $competition_events ?? $vars['competition_events'] ?? ( isset( $vm ) ? ( $vm->competition_events ?? array() ) : array() );
$competition        = $competition ?? $vars['competition'] ?? ( isset( $vm ) ? ( $vm->competition ?? null ) : null );
$season             = $season ?? $vars['season'] ?? ( isset( $vm ) ? ( $vm->season ?? '' ) : '' );
$draw_sizes_map     = $draw_sizes ?? $vars['draw_sizes'] ?? ( isset( $vm ) ? ( $vm->draw_sizes ?? array() ) : array() );
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
                    foreach ( $competition_events as $event ) {
                        if ( empty( $event->leagues ) ) {
                            continue;
                        }
                        foreach ( $event->leagues as $league ) {
                            $league    = is_object( $league ) ? $league : (object) $league;
                            $league_id = $league->id ?? '';
                            ?>
                            <tr>
                                <td><a href="/wp-admin/admin.php?page=racketmanager-cups&view=draw&competition_id=<?php echo esc_attr( (string) ( $competition->id ?? '' ) ); ?>&league=<?php echo esc_attr( (string) $league_id ); ?>&season=<?php echo esc_attr( (string) $season ); ?>"><?php echo esc_html( (string) ( $league->title ?? '' ) ); ?></a></td>
                                <td><?php echo esc_html( (string) ( $league->num_teams_total ?? '' ) ); ?></td>
                                <td><?php echo esc_html( (string) ( $draw_sizes_map[ $league_id ] ?? ( $league->draw_size ?? '' ) ) ); ?></td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
