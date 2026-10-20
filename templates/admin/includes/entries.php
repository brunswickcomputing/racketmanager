<?php
/**
 * Admin screen for entries.
 *
 * @package Racketmanager/Templates/Admin
 */

namespace Racketmanager;

/** @var object $competition */
/** @var string $season */
/** @var array<string, string> $entry_urls */
$entry_urls = $entry_urls ?? array();
?>
<div class="row">
    <div class="col-12">
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th><?php esc_html_e( 'Entries', 'racketmanager' ); ?> <?php echo empty( $current_season->entries ) ? null : '(' . count( $current_season->entries ) . ')'; ?></th>
                    <th><?php esc_html_e( 'Teams', 'racketmanager' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ( empty( $current_season->entries ) ) {
                    ?>
                    <tr><td><?php esc_html_e( 'No entries found', 'racketmanager' ); ?></td></tr>
                    <?php
                } else {
                    foreach ( $current_season->entries as $club ) {
                        $shortcode  = is_object( $club ) ? (string) ( $club->shortcode ?? '' ) : (string) ( $club['shortcode'] ?? '' );
                        $club_id    = is_object( $club ) ? (string) ( $club->id ?? '' ) : (string) ( $club['id'] ?? '' );
                        $team_count = is_object( $club ) ? ( $club->team_count ?? 0 ) : ( $club['team_count'] ?? 0 );
                        $entry_url  = $entry_urls[ $shortcode ] ?? ( $entry_urls[ $club_id ] ?? '' );
                        ?>
                        <tr>
                            <td>
                                <?php if ( '' !== $entry_url ) : ?>
                                    <a href="<?php echo esc_url( $entry_url ); ?>"><?php echo esc_html( $shortcode ); ?></a>
                                <?php else : ?>
                                    <?php echo esc_html( $shortcode ); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html( (string) $team_count ); ?></td>
                        </tr>
                        <?php
                    }
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
