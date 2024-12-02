<?php
/**
 * Email header
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

$site_url = $racketmanager->site_url;
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns="http://www.w3.org/1999/xhtml" lang="en-gb" xml:lang="en-gb">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<meta name="x-apple-disable-message-reformatting" />
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="color-scheme" content="light dark" />
		<meta name="supported-color-schemes" content="light dark" />
		<title><?php echo esc_html( $email_subject ); ?></title>
	<style>
body#email-body {
font-size: 16px; font-family:Arial,Helvetica,sans-serif; color: #1d8057ed; background-color: #e6e6e6;
}
#email-body h1 {
	font-size: 2em;
	color: #000;
}
#email-body h2 {
	font-size: 1.5em;
	color: #000;
}
#email-body h3 {
	font-size: 1.17em;
	color: #000;
}
#email-body th, #email-body td {
	text-align: left;
}
.button--green:hover {
color: #006800; background-color: #fff; border-color: #006800;
}
a.button--green:hover {
color: #006800; background-color: #fff; border-color: #006800;
}
.standingstable th, .standingstable td {
width: 50%;
text-align: left;
}
.standings-archive h2.header a {
	font-size: 1.5rem;
	text-decoration: none;
	color: #006800;
}
.standingstable {
	width: 100%;
}
.bold {
	font-weight: 900;
}
table.fixtures {
	font-size: .8em;
	width: 100%;
}
@media only screen and (max-width:768px) {
.tbContainer {
	display: block !important;
}
#email-body a span {
	line-height: inherit !important;
}
.multi table {
	table-layout: fixed; height: auto !important;
}
.multi td {
	width: auto !important; min-height: 0 !important;
}
.multi tbody {
	display: block; box-sizing: border-box; height: auto !important; min-height: 0 !important;
}
.multi tbody tr {
	display: block; box-sizing: border-box; height: auto !important; min-height: 0 !important;
}
.multi tbody tr td {
	display: block; box-sizing: border-box; height: auto !important; min-height: 0 !important;
}
.multi tbody tr th {
	display: block; box-sizing: border-box; height: auto !important; min-height: 0 !important;
}
.outer tbody {
	display: block; box-sizing: border-box; height: auto !important; min-height: 0 !important;
}
.outer tbody tr {
	display: block; box-sizing: border-box; height: auto !important; min-height: 0 !important;
}
.outer tbody tr td {
	display: block; box-sizing: border-box; height: auto !important; min-height: 0 !important;
}
.outer tbody tr th {
	display: block; box-sizing: border-box; height: auto !important; min-height: 0 !important;
}
.wrap-section .multi tbody {
	width: 100% !important;
}
.wrap-section .multi tbody tr {
	width: 100% !important;
}
.wrap-section .multi tbody tr td {
	width: 100% !important;
}
.wrap-section .multi tbody tr th {
	width: 100% !important;
}
.wrap-section .outer tbody {
	width: 100% !important;
}
.wrap-section .outer tbody tr {
	width: 100% !important;
}
.wrap-section .outer tbody tr td {
	width: 100% !important;
}
.wrap-section .outer tbody tr th {
	width: 100% !important;
}
.multi .inner {
	height: auto !important; min-height: 0 !important;
}
.tbContainer .inner {
	box-sizing: border-box;
}
.outer {
	width: 100% !important;
}
.tbContainer .columnContainer table {
	table-layout: fixed !important;
}
.tbContainer .columnContainer>table {
	height: auto !important;
}
.innerTable {
	min-height: 0 !important;
}
.no-wrap-section .outer {
	display: table !important; border-collapse: separate !important;
}
.no-wrap-section .multi table {
	height: 100% !important;
}
.no-wrap-section .multi tbody {
	display: table-row-group !important; width: 100% !important;
}
.no-wrap-section .outer tbody {
	display: table-row-group !important; width: 100% !important;
}
.no-wrap-section .multi tbody tr {
	display: table-row !important; width: 100% !important;
}
.no-wrap-section .outer tbody tr {
	display: table-row !important; width: 100% !important;
}
.no-wrap-section .multi tbody tr td {
	display: table-cell !important;
}
.no-wrap-section .multi tbody tr th {
	display: table-cell !important;
}
.no-wrap-section .outer tbody tr td {
	display: table-cell !important;
}
.no-wrap-section .outer tbody tr th {
	display: table-cell !important;
}
.no-wrap-section.columns-equal-class .outer tbody tr td {
	height: 0 !important;
}
.no-wrap-section.columns-equal-class .outer tbody tr th {
	height: 0 !important;
}
.no-wrap-section .outer tbody tr td.inner {
	width: 100% !important; height: auto !important;
}
.no-wrap-section .outer tbody tr th.inner {
	width: 100% !important; height: auto !important;
}
a[x-apple-data-detectors] {
	color: inherit !important; text-decoration: none !important; font-weight: inherit !important; line-height: inherit !important;
}
.hide-on-mobile-class {
	display: none !important;
}
.hide-on-desktop-class {
	display: block !important;
}
.hide-on-desktop-class.hide-on-mobile-class {
	display: none !important;
}
}
</style>
</head>
	<body id="email-body" style="font-size: 16px; color: #1d8057ed; margin: 0; padding: 0;" bgcolor="#e6e6e6">
		<div style="display: none; max-height: 0px; overflow: hidden; font-size: 16px; color: #1d8057ed; background-color: #e6e6e6;">
		</div>
		<div style="font-size: 16px; color: #006800; background-color: #e6e6e6; max-width: 600px; margin: auto;">
			<!-- header hero -->
			<div style="font-size: 16px; color: #fff; background-color: #006800;">
				<table align="center" style="border-collapse: collapse; display: block;" role="presentation" cellspacing="0" cellpadding="0">
					<tbody>
						<tr>
							<td role="presentation" cellspacing="0" cellpadding="0" bgcolor="#006800">
								<table style="width: 100%; border-collapse: collapse;" role="presentation" cellspacing="0" cellpadding="0">
									<tbody>
										<tr>
											<td style="font-weight: 400; min-width: 5px; width: 600px; height: 0;" role="presentation" cellspacing="0" cellpadding="0" align="left" valign="top">
												<table width="100%" style="height: 100%;" role="presentation" cellspacing="0" cellpadding="0">
													<tbody>
														<tr>
															<td style="min-width: 5px; word-wrap: break-word; word-break: break-word; font-weight: 400;" role="presentation" cellspacing="0" cellpadding="0" align="left" valign="top">
																<div style="font-size: 32px; font-weight: 600; color: #fff; background-color: transparent; padding: 10px 0;">
																	<div align="center" style="font-size: 32px; color: #fff; background-color: transparent;">
																		<span class="title">
																			<a href="<?php echo esc_url( $site_url ); ?>" style="text-decoration: none;color: #fff;"><?php echo esc_html( $organisation ); ?></a>
																		</span>
																	</div>
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
