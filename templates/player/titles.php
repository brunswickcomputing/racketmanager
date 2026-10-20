<?php
/**
 *
 * Template page for titles for a player
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Presentation\View_Models\Player_View_Model;

/** @var Player_View_Model|null $vm */
/** @var array $titles */
$titles = $vm->titles ?? ( $titles ?? [] );
?>
<div class="module module--card">
    <div class="module__content">
        <div class="module__banner">
            <h4 class="module__title"><?php esc_html_e( 'Titles/Finals', 'racketmanager' ); ?></h4>
        </div>
        <div class="module-container titles">
            <?php
            foreach ( $titles as $season => $tournaments ) {
                ?>
                <h5 class="module-divider">
                    <span class="module-divider__body">
                        <?php echo esc_html( $season ); ?>
                    </span>
                </h5>
                <div class="module-container">
                    <?php
                    foreach ( $tournaments as $tournament => $matches ) {
                        $tournament_first_match = is_array( $matches ) && ! empty( $matches ) ? reset( $matches ) : null;
                        $tournament_url         = is_object( $tournament_first_match ) && isset( $tournament_first_match->tournament_url )
                            ? (string) $tournament_first_match->tournament_url
                            : '';
                        ?>
                        <h6 class="module-divider">
                            <span class="module-divider__body">
                                <?php if ( '' !== $tournament_url ) : ?>
                                    <a class="nav--link media__link" href="<?php echo esc_url( $tournament_url ); ?>">
                                        <span class="nav-link__value"><?php echo esc_html( $tournament ); ?></span>
                                    </a>
                                <?php else : ?>
                                    <span class="nav-link__value"><?php echo esc_html( $tournament ); ?></span>
                                <?php endif; ?>
                            </span>
                        </h6>
                        <ul class="list list--naked list-bordered">
                            <?php
                            foreach ( $matches as $match ) {
                                if ( $match->team_id === $match->winner_id ) {
                                    $class = 'winner';
                                } else {
                                    $class = 'runner-up';
                                }
                                $match_draw_url = isset( $match->draw_url ) ? (string) $match->draw_url : '';
                                ?>
                                <li class="list__item">
                                    <div class="media">
                                        <div class="media__wrapper">
                                            <div class="media__img">
                                                <svg width="16" height="16" class="media__img-element--icon <?php echo esc_attr( $class ); ?>">
                                                    <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#trophy-fill' ); ?>"></use>
                                                </svg>
                                            </div>
                                            <div class="media__content">
                                                <span class="media__title">
                                                    <?php if ( '' !== $match_draw_url ) : ?>
                                                        <a class="nav--link media__link" href="<?php echo esc_url( $match_draw_url ); ?>">
                                                            <span class="nav-link__value"><?php echo esc_html( $match->title ); ?></span>
                                                        </a>
                                                    <?php else : ?>
                                                        <span class="nav-link__value"><?php echo esc_html( $match->title ); ?></span>
                                                    <?php endif; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <?php
                            }
                            ?>
                        </ul>
                        <?php
                    }
                    ?>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</div>
