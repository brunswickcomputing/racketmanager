<?php
declare( strict_types=1 );
/**
 * RacketManagerWidget API: Club_Widget class
 *
 * @package RacketManager
 * @subpackage Infrastructure/Wordpress/Widgets
 */

namespace Racketmanager\Infrastructure\Wordpress\Widgets;

use InvalidArgumentException;
use Racketmanager\Public_Api;
use Racketmanager\Repositories\Interfaces\Club_Repository_Interface;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Container\Simple_Container;
use stdClass;
use WP_Widget;

/**
 * Class to implement the widget
 */
class Club_Widget extends WP_Widget {
    /**
     * Initialize
     *
     * @param boolean                        $template        Set to true if widget is display in template.
     * @param Club_Service|null              $club_service    Club service instance.
     * @param Club_Repository_Interface|null $club_repository Club repository instance.
     * @param Simple_Container|null          $container       Container instance.
     */
    public function __construct(
        $template = false,
        private ?Club_Service $club_service = null,
        private ?Club_Repository_Interface $club_repository = null,
        private readonly ?Simple_Container $container = null
    ) {
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
        $before_widget = $args['before_widget'];
        $before_title  = $args['before_title'];
        $after_title   = $args['after_title'];
        $after_widget  = $args['after_widget'];
        $title         = apply_filters( 'widget_title', $instance['title'] );
        $club_name     = isset( $instance['club_name'] ) ? esc_html( $instance['club_name'] ) : '';
        $club_link     = isset( $instance['club_link'] ) ? esc_html( $instance['club_link'] ) : '';

        $args['limit']     = empty( $instance['num_items'] ) ? 999999 : intval( $instance['num_items'] );
        $args['club_type'] = $instance['club_type'];
        $args['orderby']   = $instance['orderby'];

        $service = $this->resolve( Club_Service::class, $this->club_service );
        if ( null !== $service ) {
            $this->club_service = $service;
            $clubs              = $service->get_clubs( $args );
        } else {
            $repo = $this->resolve( Club_Repository_Interface::class, $this->club_repository );
            if ( null !== $repo ) {
                $this->club_repository = $repo;
                $clubs                 = $repo->find_all( $args );
            } else {
                $clubs = array();
            }
        }

        $data                = new stdClass();
        $data->clubs         = is_array( $clubs ) ? $clubs : array();
        $data->club_name     = $club_name;
        $data->club_link     = $club_link;
        $data->title         = $title;
        $data->before_widget = $before_widget;
        $data->before_title  = $before_title;
        $data->after_title   = $after_title;
        $data->after_widget  = $after_widget;
        $data->widget_id     = $args['widget_id'];
        $this->show_widget( $data );
    }

    /**
     * Resolve a service or repository dependency from injected property or container.
     *
     * @param string      $class_name Target class or interface name.
     * @param object|null $injected   Pre-injected instance if available.
     * @return object|null Resolved instance or null.
     */
    private function resolve( string $class_name, ?object $injected ): ?object {
        if ( null !== $injected ) {
            return $injected;
        }

        $resolved  = null;
        $container = $this->container ?? Public_Api::get_container();

        if ( null !== $container && $container->has_definition( $class_name ) ) {
            try {
                $instance = $container->get( $class_name );
                if ( $instance instanceof $class_name ) {
                    $resolved = $instance;
                }
            } catch ( InvalidArgumentException ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
                // Dependency retrieval failed; $resolved retains default null.
            }
        }

        return $resolved;
    }

    /**
     * Function to output widget data
     *
     * @param object $data Widget display data.
     * @return void
     */
    private function show_widget( object $data ): void {
        ob_start();
        echo esc_html( $data->before_widget );

        if ( ! empty( $data->clubs ) ) {
            $this->render_title( (string) $data->title, (string) $data->before_title, (string) $data->after_title );
            $this->render_clubs_list( (array) $data->clubs, (string) $data->club_name, (string) $data->club_link );
        }

        wp_reset_postdata();
        echo esc_html( $data->after_widget );

        $this->cache_or_flush_output( (string) $data->widget_id );
    }

    /**
     * Render widget title if present.
     *
     * @param string $title        Widget title.
     * @param string $before_title Title opening markup.
     * @param string $after_title  Title closing markup.
     * @return void
     */
    private function render_title( string $title, string $before_title, string $after_title ): void {
        if ( '' !== $title ) {
            echo $before_title . esc_html( $title ) . $after_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }

    /**
     * Render container and list of clubs.
     *
     * @param array  $clubs     List of club domain objects.
     * @param string $club_name Flag indicating if club name should be displayed.
     * @param string $club_link Flag indicating if club link should be displayed.
     * @return void
     */
    private function render_clubs_list( array $clubs, string $club_name, string $club_link ): void {
        ?>
        <div id="clubs" class="roll-club" data-autoplay="5000">
            <?php
            foreach ( $clubs as $club ) {
                $this->render_club_item( $club, $club_name, $club_link );
            }
            ?>
        </div>
        <a href="/clubs" class="roll-button more-button">
            <?php esc_html_e( 'See all our clubs', 'racketmanager' ); ?>
        </a>
        <?php
    }

    /**
     * Render an individual club item.
     *
     * @param object $club      Club data object.
     * @param string $club_name Flag indicating if club name should be displayed.
     * @param string $club_link Flag indicating if club link should be displayed.
     * @return void
     */
    private function render_club_item( object $club, string $club_name, string $club_link ): void {
        ?>
        <div class="club-item">
            <div class="club-content">
                <?php if ( '1' === $club_name ) : ?>
                    <div class="">
                        <h4 class=""><a href="<?php echo esc_html( '/clubs/' . sanitize_title( $club->shortcode ) . '/' ); ?>"><?php echo esc_html( $club->name ); ?></a></h4>
                        <p><?php echo esc_html( $club->facilities ); ?></p>
                        <p><?php echo esc_html( $club->address ); ?></p>
                        <?php $this->render_club_website( $club->website, $club_link ); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render website link if enabled and available.
     *
     * @param string|null $website   Website URL.
     * @param string      $club_link Flag indicating if club link should be displayed.
     * @return void
     */
    private function render_club_website( ?string $website, string $club_link ): void {
        if ( '1' === $club_link && null !== $website ) {
            ?>
            <p><a href="<?php echo esc_url( $website ); ?>" target="_blank"><?php echo esc_html( $website ); ?></a></p>
            <?php
        }
    }

    /**
     * Flush output buffer or store to cache.
     *
     * @param string $widget_id Identifier for widget instance.
     * @return void
     */
    private function cache_or_flush_output( string $widget_id ): void {
        if ( $this->is_preview() ) {
            ob_end_flush();
            return;
        }

        $cache               = wp_cache_get( 'racketmanager', 'widget' );
        $cache               = is_array( $cache ) ? $cache : array();
        $cache[ $widget_id ] = ob_get_flush();
        wp_cache_set( 'racketmanager', $cache, 'widget' );
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
        $num_items = isset( $instance['num_items'] ) ? esc_attr( $instance['num_items'] ) : -1;
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
            <label for="<?php echo esc_html( $this->get_field_id( 'num_items' ) ); ?>"><?php esc_html_e( 'How many to show?', 'racketmanager' ); ?></label>
            <input id="<?php echo esc_html( $this->get_field_id( 'num_items' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'num_items' ) ); ?>" type="text" value="<?php echo esc_html( $num_items ); ?>" style="float:right; width:56%;" />
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
