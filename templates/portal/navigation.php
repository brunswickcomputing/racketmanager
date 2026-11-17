<?php
/**
 * Portal navigation template
 *
 * @package RacketManager
 * @var Portal_Navigation_View_Model $vm
 */

use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Navigation_View_Model;

?>
<nav class="rm-portal-nav" aria-label="<?php echo esc_attr__( 'Management Navigation', 'racketmanager' ); ?>">
    <ul class="rm-portal-nav__list">
        <?php foreach ( $vm->items as $item ) : ?>
            <li class="rm-portal-nav__item<?php echo $item->is_active ? ' rm-portal-nav__item--active' : ''; ?>">
                <a href="<?php echo esc_url( $item->url ); ?>" class="rm-portal-nav__link<?php echo $item->is_active ? ' is-active' : ''; ?>"<?php echo $item->is_active ? ' aria-current="page"' : ''; ?>>
                    <?php echo esc_html( $item->title ); ?>
                    <?php if ( ! $item->is_ported ) : ?>
                        <span class="rm-portal-nav__badge" title="<?php echo esc_attr__( 'Opens in wp-admin', 'racketmanager' ); ?>">wp-admin</span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>
