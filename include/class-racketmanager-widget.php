<?php
/**
 * RacketManagerWidget API: RacketManagerWidget class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerWidget
 */

namespace Racketmanager;

use WP_Widget;

/**
 * Class to implement the widget
 */
class RacketManager_Widget extends WP_Widget {
    /**
     * Initialize
     *
     * @param boolean $template set to true if widget is display in template.
     */
    public function __construct( $template = false ) {
        if ( ! $template ) {
            $widget_ops = array(
                'classname'   => 'racketmanager_widget',
                'description' => __( 'Clubs at a glance.', 'racketmanager' ),
            );
            parent::__construct( 'racketmanager-widget', __( 'Racket Manager', 'racketmanager' ), $widget_ops );
        }
    }
    /**
     * Displays widget
     *
     * @param array $args arguments.
     * @param array $instance instance.
     */
    public function widget( $args, $instance ): void {
        global $racketmanager;
        $cache = array();
        if ( ! $this->is_preview() ) {
            $cache = wp_cache_get( 'racketmanager', 'widget' );
        }

        if ( ! is_array( $cache ) ) {
            $cache = array();
        }

        if ( ! isset( $args['widget_id'] ) ) {
            $args['widget_id'] = $this->id;
        }

        if ( isset( $cache[ $args['widget_id'] ] ) ) {
            echo esc_html( $cache[ $args['widget_id'] ] );
            return;
        }

        ob_start();
        $before_widget = $args['before_widget'];
        $before_title  = $args['before_title'];
        $after_title   = $args['after_title'];
        wp_enqueue_script( 'racketmanager_widget_js', plugins_url( '/js/widget.js', __DIR__ ), array(), RACKETMANAGER_VERSION, array( 'in_footer' => true ) );

        $title     = apply_filters( 'widget_title', $instance['title'] );
        $club_name = isset( $instance['club_name'] ) ? esc_html( $instance['club_name'] ) : '';
        $club_link = isset( $instance['club_link'] ) ? esc_html( $instance['club_link'] ) : '';
        $num_items = empty( $instance['num_items'] ) ? 999999 : intval( $instance['num_items'] );
        $club_type = $instance['club_type'];
        $orderby   = $instance['orderby'];

        $clubs = $racketmanager->get_clubs(
            array(
                'type'    => $club_type,
                'limit'   => $num_items,
                'orderby' => $orderby,
            )
        );

        echo esc_html( $before_widget );

        if ( $clubs ) {
            ?>
            <?php
            if ( $title ) {
                echo $before_title . esc_html( $title ) . $after_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            }
            ?>
            <div id="clubs" class="roll-club" data-autoplay="5000">
                <?php
                foreach ( $clubs as $club ) {
                    ?>
                    <div class="club-item">
                        <div class="club-content">
                            <?php if ( '1' === $club_name ) { ?>
                                <div class="">
                                    <h4 class=""><a href="<?php echo esc_html( '/clubs/' . sanitize_title( $club->shortcode ) . '/' ); ?>"><?php echo esc_html( $club->name ); ?></a></h4>
                                    <p><?php echo esc_html( $club->facilities ); ?></p>
                                    <p><?php echo esc_html( $club->address ); ?></p>
                                    <?php
                                    if ( '1' === $club_link ) {
                                        if ( null !== $club->website ) {
                                            ?>
                                            <p><a href="<?php echo esc_url( $club->website ); ?>" target="_blank"><?php echo esc_html( $club->website ); ?></a></p>
                                            <?php
                                        }
                                    }
                                    ?>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
            <a href="/clubs" class="roll-button more-button">
                <?php esc_html_e( 'See all our clubs', 'racketmanager' ); ?>
            </a>
            <?php
        }
        wp_reset_postdata();
        echo esc_html( $args['after_widget'] );
        if ( ! $this->is_preview() ) {
            $cache[ $args['widget_id'] ] = ob_get_flush();
            wp_cache_set( 'racketmanager', $cache, 'widget' );
        } else {
            ob_end_flush();
        }
    }

    /**
     * Save settings
     *
     * @param array $new_instance new instance of settings.
     * @param array $old_instance old instance of settings.
     * @return array
     */
    public function update( $new_instance, $old_instance ): array {
        return $new_instance;
    }

    /**
     * Widget control panel
     *
     * @param int|array $instance widget arguments.
     */
    public function form( $instance ): void {
        $title     = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
        $club_name = isset( $instance['club_name'] ) ? esc_attr( $instance['club_name'] ) : '';
        $club_link = isset( $instance['club_link'] ) ? esc_attr( $instance['club_link'] ) : '';
        $orderby   = isset( $instance['orderby'] ) ? esc_attr( $instance['orderby'] ) : '';
        $num_items = isset( $instance['num_items '] ) ? esc_attr( $instance['num_items '] ) : -1;
        $club_type = isset( $instance['club_type'] ) ? esc_attr( $instance['club_type'] ) : '';
        ?>
        <div>
            <label for="<?php echo esc_html( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'racketmanager' ); ?></label>
            <input id="<?php echo esc_html( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_html( $title ); ?>" style="float:right; width:56%;" />
        </div>
        <div>
            <label for="<?php echo esc_html( $this->get_field_id( 'orderby' ) ); ?>"><?php esc_html_e( 'Sorting Method', 'racketmanager' ); ?></label>
            <select id="<?php echo esc_html( $this->get_field_id( 'orderby' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'orderby' ) ); ?>"  style="float:right; width:56%;">
                <option selected="selected" value="none"><?php esc_html_e( 'Select One', 'racketmanager' ); ?></option>
                <option value="asc" <?php selected( 'asc', $orderby ); ?>><?php esc_html_e( 'Ascending', 'racketmanager' ); ?></option>
                <option value="desc" <?php selected( 'desc', $orderby ); ?>><?php esc_html_e( 'Descending', 'racketmanager' ); ?></option>
                <option value="rand" <?php selected( 'rand', $orderby ); ?>><?php esc_html_e( 'Random', 'racketmanager' ); ?></option>
                <option value="menu_order" <?php selected( 'menu_order', $orderby ); ?>><?php esc_html_e( 'Menu Order', 'racketmanager' ); ?></option>
            </select>
        </div>
        <div>
            <label for="<?php echo esc_html( $this->get_field_id( 'num_items ' ) ); ?>"><?php esc_html_e( 'How many to show?', 'racketmanager' ); ?></label>
            <input id="<?php echo esc_html( $this->get_field_id( 'num_items ' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'num_items ' ) ); ?>" type="text" value="<?php echo esc_html( $num_items  ); ?>" style="float:right; width:56%;" />
        </div>
        <div>
            <label for="<?php echo esc_html( $this->get_field_id( 'club_name' ) ); ?>"><?php esc_html_e( 'Show club name?', 'racketmanager' ); ?></label>
            <input id="<?php echo esc_html( $this->get_field_id( 'club_name' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'club_name' ) ); ?>" type="checkbox" value="1" <?php checked( '1', $club_name ); ?> style="float:right; margin-right:6px;" />
        </div>
        <div>
            <label for="<?php echo esc_html( $this->get_field_id( 'club_link' ) ); ?>"><?php esc_html_e( 'Link club name?', 'racketmanager' ); ?></label>
            <input id="<?php echo esc_html( $this->get_field_id( 'club_link' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'club_link' ) ); ?>" type="checkbox" value="1" <?php checked( '1', $club_link ); ?> style="float:right; margin-right:6px;" />
        </div>
        <div>
            <label for="<?php echo esc_html( $this->get_field_id( 'club_type' ) ); ?>"><?php esc_html_e( 'Club Type', 'racketmanager' ); ?></label>
            <select id="<?php echo esc_html( $this->get_field_id( 'club_type' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'club_type' ) ); ?>"  style="float:right; width:56%;" >
                <option selected="selected" value="none"><?php esc_html_e( 'Select One', 'racketmanager' ); ?></option>
                <option value="all" <?php selected( 'all', $club_type ); ?>><?php esc_html_e( 'All', 'racketmanager' ); ?></option>
                <option value="affiliated" <?php selected( 'affiliated', $club_type ); ?>><?php esc_html_e( 'Affiliated', 'racketmanager' ); ?></option>
            </select>
        </div>
        <?php
    }
}
?>
