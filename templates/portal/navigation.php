<?php
/**
 * Portal navigation template
 *
 * @package RacketManager
 * @var Portal_Navigation_View_Model $vm
 */

use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Navigation_Item;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Navigation_View_Model;

$render_nav_item = static function ( Portal_Navigation_Item $item ): void {
    ?>
    <li class="rm-portal-nav__item<?php echo $item->is_active ? ' rm-portal-nav__item--active' : ''; ?>">
        <a href="<?php echo esc_url( $item->url ); ?>" class="rm-portal-nav__link<?php echo $item->is_active ? ' is-active' : ''; ?>"<?php echo $item->is_active ? ' aria-current="page"' : ''; ?>>
            <?php echo esc_html( $item->title ); ?>
            <?php if ( ! $item->is_ported ) : ?>
                <span class="rm-portal-nav__badge" aria-hidden="true">wp-admin</span>
                <span class="screen-reader-text"><?php echo esc_html__( 'Opens in wp-admin', 'racketmanager' ); ?></span>
            <?php endif; ?>
        </a>
    </li>
    <?php
};

$seen_group_ids = [];
?>
<nav class="rm-portal-nav" aria-label="<?php echo esc_attr__( 'Management Navigation', 'racketmanager' ); ?>">
    <ul class="rm-portal-nav__list rm-portal-nav__list--vertical">
        <?php foreach ( $vm->get_groups() as $group_index => $group ) : ?>
            <?php if ( '' === $group->label ) : ?>
                <?php foreach ( $group->items as $item ) : ?>
                    <?php $render_nav_item( $item ); ?>
                <?php endforeach; ?>
            <?php else : ?>
                <?php
                $group_slug = strtolower( trim( (string) preg_replace( '/[^a-zA-Z0-9_\-]+/', '-', $group->label ), '-' ) );
                $base_id    = 'rm-portal-nav-group-' . ( '' !== $group_slug ? $group_slug : 'section-' . $group_index );
                $group_id   = $base_id;
                $counter    = 2;
                while ( isset( $seen_group_ids[ $group_id ] ) ) {
                    $group_id = $base_id . '-' . $counter;
                    ++$counter;
                }
                $seen_group_ids[ $group_id ] = true;
                ?>
                <li class="rm-portal-nav__group">
                    <span id="<?php echo esc_attr( $group_id ); ?>" class="rm-portal-nav__group-title"><?php echo esc_html( $group->label ); ?></span>
                    <ul class="rm-portal-nav__group-list" aria-labelledby="<?php echo esc_attr( $group_id ); ?>">
                        <?php foreach ( $group->items as $item ) : ?>
                            <?php $render_nav_item( $item ); ?>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</nav>
