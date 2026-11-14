<?php
/**
 * Template for favourites display
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var \Racketmanager\Presentation\View_Models\Favourite_List_Item_View_Model[] $favourite_rows */
$favourite_rows     = $favourite_rows ?? ( $vars['favourite_rows'] ?? ( $vm->favourite_rows ?? [] ) );
$favourite_controls = $favourite_controls ?? ( $vars['favourite_controls'] ?? ( $vm->favourite_controls ?? null ) );
?>
<ul class="list list--grid list--bordered">
    <?php
    if ( empty( $favourite_rows ) ) {
        ?>
        <li class="list__item col-12 text-center"><?php esc_html_e( 'No favourite found', 'racketmanager' ); ?></li>
        <?php
    } else {
        foreach ( $favourite_rows as $favourite_row ) {
            ?>
            <li class="list__item col-12 col-sm-6">
                <div class="media">
                    <div class="media__wrapper">
                        <div class="media__img">
                            <svg width="16" height="16" class="media__img-element--icon" aria-label="<?php esc_attr_e( 'Favourite', 'racketmanager' ); ?>">
                                <use xlink:href="<?php echo esc_url( $favourite_row->icon_url ); ?>"></use>
                            </svg>
                        </div>
                        <div class="media__content">
                            <h4 class="media__title">
                                <?php if ( '' !== $favourite_row->url ) : ?>
                                    <a class="nav--link media__link" href="<?php echo esc_url( $favourite_row->url ); ?>">
                                        <span class="nav-link__value"><?php echo esc_html( $favourite_row->name ); ?></span>
                                    </a>
                                <?php else : ?>
                                    <span class="nav-link__value"><?php echo esc_html( $favourite_row->name ); ?></span>
                                <?php endif; ?>
                            </h4>
                        </div>
                    </div>
                    <ul class="media__icons">
                        <li class="media__icons-item">
                            <?php
                            $favourite_type = $favourite_row->type;
                            $favourite_id   = $favourite_row->id;
                            require RACKETMANAGER_PATH . '/templates/includes/favourite-button.php';
                            ?>
                        </li>
                    </ul>
                </div>
            </li>
            <?php
        }
    }
    ?>
</ul>
