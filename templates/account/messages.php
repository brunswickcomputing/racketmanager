<?php
/**
 * Template for messages
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="module module--card">
	<div class="module__banner">
		<h2 class="module__title"><?php esc_html_e( 'My messages', 'racketmanager' ); ?></h2>
	</div>
	<div class="module__content">
		<div class="module-container">
			<div class="row">
				<div class="col-12 col-md-3">
					<?php
					foreach ( $messages as $message ) {
						?>
						<a id="message-summary-<?php echo esc_attr( $message->id ); ?>" class="message-summary<?php echo '1' === $message->status ? ' unread' : ''; ?>" onclick="Racketmanager.getMessage(event, '<?php echo esc_attr( $message->id ); ?>')">
							<div class="description_wrapper">
								<div class="sender">
									<?php
									if ( $message->from_name ) {
										echo esc_html( $message->from_name );
									}
									?>
								</div>
								<div class="subject"><?php echo esc_html( $message->subject ); ?></div>
							</div>
							<div class="message_date">
								<span class="date__month"><?php echo esc_html( mysql2date( 'M', $message->date ) ); ?></span>
								<span class="date__day"><?php echo esc_html( mysql2date( 'j', $message->date ) ); ?></span>
								<span class="date__year"><?php echo esc_html( mysql2date( 'Y', $message->date ) ); ?></span>
							</div>
						</a>
						<?php
					}
					?>
				</div>
				<div class="col-12 col-md-9">
					<div id="message_detail"></div>
				</div>
			</div>
		</div>
	</div>
</div>