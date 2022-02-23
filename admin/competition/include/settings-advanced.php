<table class="lm-form-table">
  <?php do_action( 'competition_settings_'.$competition->sport, $competition ); ?>
  <?php do_action( 'competition_settings_'.$competition->mode, $competition ); ?>
  <?php do_action( 'competition_settings', $competition ); ?>
</table>
