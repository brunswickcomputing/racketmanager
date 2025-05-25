<?php
/**
 * RacketManagerWidget API: RacketManagerWidget class
 *
 * @author Paul Moffat
 * @package RacketManager
 * @subpackage RacketManagerWidget
 */

namespace Racketmanager;

/**
 * Class to implement the widget
 */
class RacketManager_Widget extends \WP_Widget {
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
		$widget_id     = $args['widget_id'];
		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		$before_title  = $args['before_title'];
		$after_title   = $args['after_title'];
		wp_enqueue_script( 'racketmanager_widget_js', '/wp-content/plugins/leaguemanager/js/widget.js', array(), RACKETMANAGER_VERSION, array( 'in_footer' => true ) );

		$title          = ( ! empty( $instance['title'] ) ) ? $instance['title'] : '';
		$title          = apply_filters( 'widget_title', $instance['title'] );
		$clubname       = isset( $instance['clubname'] ) ? esc_html( $instance['clubname'] ) : '';
		$clublink       = isset( $instance['clublink'] ) ? esc_html( $instance['clublink'] ) : '';
		$itemstodisplay = ( ! empty( $instance['itemstodisplay'] ) ) ? intval( $instance['itemstodisplay'] ) : -1;
		$club_type      = $instance['club_type'];
		$orderby        = $instance['orderby'];

		$clubs = $racketmanager->get_clubs(
			array(
				'type'    => $club_type,
				'limit'   => $itemstodisplay,
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
					$title          = $club->name;
					$cluburl        = $club->website;
					$clubfacilities = $club->facilities;
					$clubaddress    = $club->address;
					$clubinfolink   = '/clubs/' . sanitize_title( $club->shortcode ) . '/';
					?>
					<div class="club-item">
						<div class="club-inner">
						</div>
						<div class="club-content">
							<?php if ( '1' === $clubname ) { ?>
								<div class="clubdtls">
									<h4 class="wp_clubname"><a href="<?php echo esc_html( $clubinfolink ); ?>"><?php echo esc_html( $title ); ?></a></h4>
									<p><?php echo esc_html( $clubfacilities ); ?></p>
									<p><?php echo esc_html( $clubaddress ); ?></p>
									<?php if ( '1' === $clublink ) { ?>
										<?php if ( null !== $cluburl ) { ?>
											<p><a href="<?php echo esc_url( $cluburl ); ?>" target="_blank"><?php echo esc_html( $cluburl ); ?></a></p>
										<?php } ?>
									<?php } ?>
								</div>
								<?php
							}
							?>
						</div>
					</div>
				<?php } ?>
			</div>

			<a href="/clubs" class="roll-button more-button">
				<?php esc_html_e( 'See all our clubs', 'racketmanager' ); ?>
			</a>
		<?php } ?>
		<?php
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
		$title          = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$clubname       = isset( $instance['clubname'] ) ? esc_attr( $instance['clubname'] ) : '';
		$clublink       = isset( $instance['clublink'] ) ? esc_attr( $instance['clublink'] ) : '';
		$orderby        = isset( $instance['orderby'] ) ? esc_attr( $instance['orderby'] ) : '';
		$itemstodisplay = isset( $instance['itemstodisplay'] ) ? esc_attr( $instance['itemstodisplay'] ) : -1;
		$club_type      = isset( $instance['club_type'] ) ? esc_attr( $instance['club_type'] ) : '';
		?>
		<p>
			<label for="<?php echo esc_html( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'racketmanager' ); ?></label>
			<input id="<?php echo esc_html( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_html( $title ); ?>" style="float:right; width:56%;" />
		</p>
		<p>
			<label for="<?php echo esc_html( $this->get_field_id( 'orderby' ) ); ?>"><?php esc_html_e( 'Sorting Method', 'racketmanager' ); ?></label>
			<select id="<?php echo esc_html( $this->get_field_id( 'orderby' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'orderby' ) ); ?>"  style="float:right; width:56%;">
				<option selected="selected" value="none"><?php esc_html_e( 'Select One', 'racketmanager' ); ?></option>
				<option value="asc"
				<?php
				if ( 'asc' === $orderby ) {
					echo ' selected="selected"';
				}
				?>
				value="asc"><?php esc_html_e( 'Asc', 'racketmanager' ); ?></option>
				<option value="desc"
				<?php
				if ( 'desc' === $orderby ) {
					echo ' selected="selected"';
				}
				?>
				><?php esc_html_e( 'Desc', 'racketmanager' ); ?></option>
				<option value="rand"
				<?php
				if ( 'rand' === $orderby ) {
					echo ' selected="selected"';
				}
				?>
				><?php esc_html_e( 'Random', 'racketmanager' ); ?></option>
				<option value="menu_order"
				<?php
				if ( 'menu_order' === $orderby ) {
					echo ' selected="selected"';
				}
				?>
				><?php esc_html_e( 'Page Attributes "Order"', 'racketmanager' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_html( $this->get_field_id( 'itemstodisplay' ) ); ?>"><?php esc_html_e( 'How many to show?', 'racketmanager' ); ?></label>
			<input id="<?php echo esc_html( $this->get_field_id( 'itemstodisplay' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'itemstodisplay' ) ); ?>" type="text" value="<?php echo esc_html( $itemstodisplay ); ?>" style="float:right; width:56%;" />
		</p>
		<p>
			<label for="<?php echo esc_html( $this->get_field_id( 'clubname' ) ); ?>"><?php esc_html_e( 'Show club name?', 'racketmanager' ); ?></label>
			<input id="<?php echo esc_html( $this->get_field_id( 'clubname' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'clubname' ) ); ?>" type="checkbox" value="1" <?php checked( '1', $clubname ); ?> style="float:right; margin-right:6px;" />
		</p>
		<p>
			<label for="<?php echo esc_html( $this->get_field_id( 'clublink' ) ); ?>"><?php esc_html_e( 'Link club name?', 'racketmanager' ); ?></label>
			<input id="<?php echo esc_html( $this->get_field_id( 'clublink' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'clublink' ) ); ?>" type="checkbox" value="1" <?php checked( '1', $clublink ); ?> style="float:right; margin-right:6px;" />
		</p>
		<p>
			<label for="<?php echo esc_html( $this->get_field_id( 'club_type' ) ); ?>"><?php esc_html_e( 'Club Type', 'racketmanager' ); ?></label>
			<select id="<?php echo esc_html( $this->get_field_id( 'club_type' ) ); ?>" name="<?php echo esc_html( $this->get_field_name( 'club_type' ) ); ?>"  style="float:right; width:56%;" >
				<option selected="selected" value="none"><?php esc_html_e( 'Select One', 'racketmanager' ); ?></option>
				<?php $terms = get_terms( 'club_type' ); ?>
				<option value="all"
				<?php
				if ( 'all' === $club_type ) {
					echo ' selected="selected"';
				}
				?>
				><?php esc_html_e( 'All', 'racketmanager' ); ?></option>
				<option value="affiliated"
				<?php
				if ( 'affiliated' === $club_type ) {
					echo ' selected="selected"';
				}
				?>
				><?php esc_html_e( 'Affiliated', 'racketmanager' ); ?></option>
			</select>
		</p>
		<?php
	}
}
?>
