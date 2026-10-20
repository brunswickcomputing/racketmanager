<?php
/**
 * Template for event constitution
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $event */
/** @var string|null $site_url */
/** @var array<int|string, string> $league_urls */
$email_subject = $event->name ?? '';
$site_url      = $site_url ?? ( function_exists( 'site_url' ) ? site_url() : '' );
require RACKETMANAGER_PATH . 'templates/email/email-header.php';
require RACKETMANAGER_PATH . 'templates/email/div-top.php';
?>
<div id="leagues">
    <h1><?php echo esc_html( $event->name ) . ' - ' . esc_html( $event->current_season['name'] ); ?></h1>
    <?php
    foreach ( $event->leagues as $league ) {
        $href = $league_urls[ $league->id ] ?? ( $league_urls[ (string) $league->id ] ?? '' );
        ?>
        <div class="standings-archive">
            <h2 class="header">
                <?php if ( '' !== $href ) : ?>
                    <a href="<?php echo esc_url( $href ); ?>">
                        <?php echo esc_html( $league->title ); ?>
                    </a>
                <?php else : ?>
                    <?php echo esc_html( $league->title ); ?>
                <?php endif; ?>
            </h2>
            <?php
            league_standings(
                $league->id,
                array(
                    'season'   => $event->current_season['name'],
                    'template' => 'constitution',
                )
            );
            ?>
        </div>
        <?php
    }
    ?>
</div>
<?php
require RACKETMANAGER_PATH . 'templates/email/div-bottom.php';
require RACKETMANAGER_PATH . 'templates/email/email-footer.php';
