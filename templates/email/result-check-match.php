<?php
/**
 * Result check matchemail body
 *
 * @package Racketmanager/Templates/Email
 */

namespace Racketmanager;

require 'email-header.php';
?>
			<?php
			$salutation_link = $captain;
			require 'components/salutation.php';
			?>
			<?php
			$paragraph_text = __( 'You broke one of the rules for this match.', 'racketmanager' );
			require 'components/paragraph.php';
			?>
			<div style="font-size: 16px; color: #000; background-color: #fff; padding: 0 20px;">
				<table align="center" style="display: block;" role="presentation" cellspacing="0" cellpadding="0">
					<tbody>
						<tr>
							<td role="presentation" cellspacing="0" cellpadding="0" bgcolor="#fff">
								<table style="width: 100%; border-collapse: collapse;" role="presentation" cellspacing="0" cellpadding="0">
									<tbody>
										<tr>
											<td style="font-weight: 400; min-width: 5px; width: 600px; height: 0;" role="presentation" cellspacing="0" cellpadding="0" align="left" bgcolor="#fff" valign="top">
												<table width="100%" style="height: 100%; text-align: left; margin-left: 10px;" role="presentation" cellspacing="0" cellpadding="0">
													<tbody>
														<tr style="line-height: 22px;">
															<td>
																<ul style="margin-top: 0;">
																	<li><?php echo esc_html( $reason ); ?></li>
																</ul>
															</td>
														</tr>
													</tbody>
												</table>
											</td>
										</tr>
									</tbody>
								</table>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<?php
			if ( $penalty ) {
				/* translators: %s: penalty */
				$paragraph_text = sprintf( __( 'You have been deducted %s point.', 'racketmanager' ), $penalty );
				require 'components/paragraph.php';
			}
			?>
			<?php
			if ( ! empty( $contact_email ) ) {
				require 'components/contact.php';
			}
			?>
			<?php require 'components/closing.php'; ?>
<?php
require 'email-footer.php';
