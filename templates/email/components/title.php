<?php
/**
 * Email title section
 *
 * @package Racketmanager/Templates/Email
 */

namespace Racketmanager;

/** @var string $title_text */
/** @var string $align */
?><!-- title -->
			<div style="font-size: 16px; color: #000; background-color: #fff; padding: 0 20px;">
				<table align="center" style="display: block;" role="presentation" cellspacing="0" cellpadding="0">
					<tbody>
						<tr>
							<td role="presentation" cellspacing="0" cellpadding="0" bgcolor="#fff">
								<table style="width: 100%; border-collapse: collapse;" role="presentation" cellspacing="0" cellpadding="0">
									<tbody>
										<tr>
											<td style="font-weight: 400; min-width: 5px; width: 600px; height: 0;" role="presentation" cellspacing="0" cellpadding="0" align="left" bgcolor="#fff" valign="top">
												<table width="100%" style="height: 100%;" role="presentation" cellspacing="0" cellpadding="0">
													<tbody>
														<tr>
															<td style="min-width: 5px; font-weight: 400;" role="presentation" cellspacing="0" cellpadding="0" align="left" bgcolor="#fff" valign="top">
																<div style="font-size: 16px; color: #000; background-color: transparent; margin: 10px;">
																	<?php
																	if ( ! empty( $title_align ) ) {
																		if ( 'center' === $title_align ) {
																			$align = 'center';
																		} elseif ( 'left' === $title_align ) {
																			$align = 'left';
																		} elseif ( 'right' === $title_align ) {
																			$align = 'right';

																		}
																	} else {
																		$align = 'left';
																	}
																	if ( empty( $title_level ) ) {
																		$title_level = 1;
																	}
																	$title_style = match ($title_level) {
																		1 => 'font-size: 24px; font-weight: 900;',
																		2 => 'font-size: 21px; font-weight: 700;',
																		3 => 'font-size: 18px; font-weight: 600;',
																		default => 'font-size: 16px;',
																	};
																	?>
																	<h<?php echo esc_attr( $title_level ); ?> style="<?php esc_attr( $title_style ); ?> line-height: 1.25; margin: 0;" align="<?php echo esc_attr( $align ); ?>">
																		<?php echo ' ' . esc_html( $title_text ); ?>
																	</h<?php echo esc_attr( $title_level ); ?>>
																</div>
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
$title_text = '';
