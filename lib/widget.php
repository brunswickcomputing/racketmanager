<?php
/**
* RacketManagerWidget API: RacketManagerWidget class
*
* @author Kolja Schleich
* @author Paul Moffat
* @package RacketManager
* @subpackage RacketManagerWidget
*/

/**
* Class to implement the widget
*
* @package RacketManager
* @subpackage RacketManagerWidget
*/
class RacketManagerWidget extends WP_Widget {
  /**
  * index for matches in widget
  *
  * @var array
  */
  private $match_index = array( 'next' => 0, 'prev' => 0 );


  /**
  * initialize
  *
  * @param boolean $template set to true if widget is display in template
  */
  public function __construct( $template = false ) {
    if ( !$template ) {
      $widget_ops = array('classname' => 'racketmanager_widget', 'description' => __('Clubs at a glance.', 'racketmanager') );
      parent::__construct('racketmanager-widget', __( 'Racket Manager', 'racketmanager' ), $widget_ops);
    }
  }

  /**
  * displays widget
  *
  * @param array $args
  * @param array $instance
  */
  public function widget( $args, $instance ) {
    global $racketmanager_shortcodes, $racketmanager;
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
      echo $cache[ $args['widget_id'] ];
      return;
    }

    ob_start();
    extract( $args );
    wp_enqueue_script( 'racketmanager_widget_js', '/wp-content/plugins/leaguemanager/js/widget.js' );

    $title          = ( ! empty( $instance['title'] ) ) ? $instance['title'] : '';
    $title          = apply_filters('widget_title', $instance['title']);
    $clubname       = isset( $instance['clubname'] ) ? esc_html($instance['clubname']) : '';
    $clublink       = isset( $instance['clublink'] ) ? esc_html($instance['clublink']) : '';
    $clubbio        = isset( $instance['clubbio'] ) ? esc_html($instance['clubbio']) : '';
    $itemstodisplay = ( ! empty( $instance['itemstodisplay'] ) ) ? intval( $instance['itemstodisplay'] ) : -1;
    $club_type      = $instance['club_type'];
    $orderby        = $instance['orderby'];

    $clubs = $racketmanager->getClubs();

    echo $args['before_widget'];

    if ( $clubs ) {
      ?>
      <?php if ( $title ) echo $before_title . $title . $after_title;?>
      <div id="clubs" class="roll-club" data-autoplay="5000">
        <?php foreach ($clubs as $club) { ?>
          <?php
          $title          = $club->name;
          $cluburl        = $club->website;
          $clubfacilities = $club->facilities;
          $clubaddress    = $club->address;
          //                $excerpt        = $club->description;
          $clubinfolink   = "/club/".sanitize_title($club->shortcode)."/";
          ?>
          <div class="club-item">
            <div class="club-inner">
            </div>
            <div class="club-content">
              <?php if( $clubname == '1' ) { ?>
                <div class="clubdtls">
                  <h4 class="wp_clubname"><a href="<?php echo ($clubinfolink); ?>"><?php echo ($title); ?></a></h4>
                  <p><?php echo ($clubfacilities); ?></p>
                  <p><?php echo ($clubaddress); ?></p>
                  <?php if( $clublink == '1' ) { ?>
                    <?php if ($cluburl != null) { ?>
                      <p><a href="<?php echo ($cluburl); ?>" target="_blank"><?php echo ($cluburl); ?></a></p>
                    <?php } ?>
                  <?php } ?>
                </div>
                <?php ;}

                if( $clubbio == '1' ) { ?>
                  <div class="clubbio">
                    <p><?php echo ($excerpt); ?></p>
                  </div>
                  <?php ;} ?>
                </div>
              </div>

            <?php } ?>
          </div>

          <a href="/clubs" class="roll-button more-button">
            <?php echo __('See all our clubs', 'racketmanager'); ?>
          </a>

        <?php } ?>

        <?php
        wp_reset_postdata();
        echo $args['after_widget'];

        if ( ! $this->is_preview() ) {
          $cache[ $args['widget_id'] ] = ob_get_flush();
          wp_cache_set( 'racketmanager', $cache, 'widget' );
        } else {
          ob_end_flush();
        }

      }

      /**
      * save settings
      *
      * @param array $new_instance
      * @param $old_instance
      * @return array
      */
      public function update( $new_instance, $old_instance ) {
        return $new_instance;
      }

      /**
      * widget control panel
      *
      * @param int|array $widget_args
      */
      public function form( $instance ) {
        global $racketmanager;
        $title     = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
        $clubname  = isset( $instance['clubname'] ) ? esc_attr( $instance['clubname'] ) : '';
        $clublink  = isset( $instance['clublink'] ) ? esc_attr( $instance['clublink'] ) : '';
        $clubbio   = isset( $instance['clubbio'] ) ? esc_attr( $instance['clubbio'] ) : '';
        $orderby   = isset( $instance['orderby'] ) ? esc_attr( $instance['orderby'] ) : '';
        $itemstodisplay = isset( $instance['itemstodisplay'] ) ? esc_attr( $instance['itemstodisplay'] ) : -1;
        $club_type = isset( $instance['club_type'] ) ? esc_attr( $instance['club_type'] ) : '';
        ?>
        <p>
          <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title', 'racketmanager'); ?></label>
          <input id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" style="float:right; width:56%;" />
        </p>
        <p>
          <label for="<?php echo $this->get_field_id('orderby'); ?>"><?php _e('Sorting Method', 'racketmanager'); ?></label>
          <select id="<?php echo $this->get_field_id('orderby'); ?>" name="<?php echo $this->get_field_name('orderby'); ?>"  style="float:right; width:56%;">
            <option selected="selected" value="none"><?php _e( 'Select One', 'racketmanager' ); ?></option>
            <option <?php if ( $orderby == 'asc' ) { echo ' selected="selected"'; } ?> value="asc"><?php _e('Asc', 'racketmanager'); ?></option>
            <option <?php if ( $orderby == 'desc' ) { echo ' selected="selected"'; } ?> value="desc"><?php _e('Desc', 'racketmanager'); ?></option>
            <option <?php if ( $orderby == 'rand' ) { echo ' selected="selected"'; } ?> value="rand"><?php _e('Random', 'racketmanager'); ?></option>
            <option <?php if ( $orderby == 'menu_order' ) { echo ' selected="selected"'; } ?> value="menu_order"><?php _e('Page Attributes "Order"', 'racketmanager'); ?></option>
          </select>
        </p>
        <p>
          <label for="<?php echo $this->get_field_id('itemstodisplay'); ?>"><?php _e('How many to show?', 'racketmanager'); ?></label>
          <input id="<?php echo $this->get_field_id('itemstodisplay'); ?>" name="<?php echo $this->get_field_name('itemstodisplay'); ?>" type="text" value="<?php echo $itemstodisplay; ?>" style="float:right; width:56%;" />
        </p>

        <p>
          <label for="<?php echo $this->get_field_id('clubname'); ?>"><?php _e('Show club name?', 'racketmanager'); ?></label>
          <input id="<?php echo $this->get_field_id('clubname'); ?>" name="<?php echo $this->get_field_name('clubname'); ?>" type="checkbox" value="1" <?php checked( '1', $clubname ); ?> style="float:right; margin-right:6px;" />
        </p>
        <p>
          <label for="<?php echo $this->get_field_id('clublink'); ?>"><?php _e('Link club name?', 'racketmanager'); ?></label>
          <input id="<?php echo $this->get_field_id('clublink'); ?>" name="<?php echo $this->get_field_name('clublink'); ?>" type="checkbox" value="1" <?php checked( '1', $clublink ); ?> style="float:right; margin-right:6px;" />
        </p>
        <p>
          <label for="<?php echo $this->get_field_id('clubbio'); ?>"><?php _e('Show club bio?', 'racketmanager'); ?></label>
          <input id="<?php echo $this->get_field_id('clubbio'); ?>" name="<?php echo $this->get_field_name('clubbio'); ?>" type="checkbox" value="1" <?php checked( '1', $clubbio ); ?> style="float:right; margin-right:6px;" />
        </p>
        <p>
          <label for="<?php echo $this->get_field_id('club_type'); ?>"><?php _e('Club Type', 'racketmanager'); ?></label>
          <select id="<?php echo $this->get_field_id('club_type'); ?>" name="<?php echo $this->get_field_name('club_type'); ?>"  style="float:right; width:56%;" >
            <option selected="selected" value="none"><?php _e( 'Select One', 'racketmanager' ); ?></option>
            <?php $terms = get_terms( 'club_type' ); ?>
            <option <?php if ( $club_type == 'all' ) { echo ' selected="selected"'; } ?> value="all"><?php _e( 'All', 'racketmanager' ); ?></option>
            <option<?php if ( $club_type == "affiliated" ) { echo ' selected="selected"'; } ?> value="affiliated"><?php _e( 'Affiliated', 'racketmanager' ); ?></option>
          </select>
        </p>

        <?php

      }
    }
    ?>
