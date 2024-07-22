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
			<?php
			if ( $messages ) {
				$read = $messages['total'] - $messages['unread'];
				?>
				<div class="row">
					<div class="col-12 text-center mb-3">
						<form id="user-messages-delete" method="post" class="form-control">
							<?php wp_nonce_field( 'racketmanager_delete-messages', 'racketmanager_nonce' ); ?>
							<div class="form-check form-check-inline">
								<input class="form-check-input" type="radio" name="message_type" id="message_type_read" value="0">
								<label class="form-check-label" for="message_type_read"><?php echo esc_html__( 'Read', 'racketmanager' ) . ' (<span id="read-messages">' . esc_html( $read ) . '</span>)'; ?></label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input" type="radio" name="message_type" id="message_type_unread" value="1">
								<label class="form-check-label" for="message_type_unread"><?php echo esc_html__( 'Unread', 'racketmanager' ) . ' (<span id="unread-messages">' . esc_html( $messages['unread'] ) . '</span>)'; ?></label>
							</div>
							<button class="btn btn-primary" onclick="Racketmanager.deleteMessages(event, this)"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></button>
						</form>
					</div>
				</div>
				<div class="row">
					<div class="col-12 col-md-3">
						<?php
						foreach ( $messages['detail'] as $message ) {
							?>
							<a id="message-summary-<?php echo esc_attr( $message->id ); ?>" class="message-summary<?php echo '1' === $message->status ? ' unread' : ' read'; ?>" onclick="Racketmanager.getMessage(event, '<?php echo esc_attr( $message->id ); ?>')">
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
						<div style="display: none;" id="messages-alert-response">
							<div class="alert_rm alert--danger">
								<div class="alert__body">
									<div class="alert__body-inner">
										<span id="messages-alert"></span>
									</div>
								</div>
							</div>
						</div>
						<div id="message_detail">
							<p class="text-center"><?php esc_html_e( 'Select message to read', 'racketmanager' ); ?></p>
						</div>
					</div>
				</div>
				<?php
			}
			?>
		</div>
	</div>
</div>