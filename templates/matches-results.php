<?php
/**
 *
 * Template page for the specific match results table
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $matches_list: contains all matches for current league
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable)
 */

namespace Racketmanager;

/** @var string $header_level */
/** @var array  $matches_list */
$header_level = $header_level ?? $vars['header_level'] ?? $vm->header_level ?? 1;
$matches_list = $matches_list ?? $vars['matches_list'] ?? $vm->matches_list ?? array();
?>
<div class="module module--card">
    <div class="module__banner">
        <h<?php echo esc_attr( $header_level ); ?> class="module__title"><?php esc_html_e( 'Latest Results', 'racketmanager' ); ?></h<?php echo esc_attr( $header_level ); ?>>
    </div>
    <div class="module__content">
        <div class="module-container">
            <div class="module">
                <?php
                if ( $matches_list ) {
                    require RACKETMANAGER_PATH . 'templates/includes/matches-team-list-group.php';
                } else {
                    ?>
                    <p><?php esc_html_e( 'No recent results', 'racketmanager' ); ?></p>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>
