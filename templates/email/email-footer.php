<?php
/**
 * Email footer
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var string $organisation */
?>
<!-- footer -->
            <div style="font-size: 16px; color: #fff; background-color: #1c1c1c;">
                <table align="center" style="display: block;" role="presentation" cellspacing="0" cellpadding="0">
                    <tbody>
                        <tr>
                            <td role="presentation" cellspacing="0" cellpadding="0" bgcolor="#152d25">
                                <table style="width: 100%; border-collapse: collapse;" role="presentation" cellspacing="0" cellpadding="0">
                                    <tbody>
                                        <tr>
                                            <td style="font-weight: 400; min-width: 5px; width: 600px; height: 0;" role="presentation" cellspacing="0" cellpadding="0" align="left" bgcolor="#1c1c1c" valign="top">
                                                <table width="100%" style="height: 100%;" role="presentation" cellspacing="0" cellpadding="0">
                                                    <tbody>
                                                        <tr>
                                                            <td style="min-width: 5px; word-wrap: break-word; word-break: break-word; font-weight: 400;" role="presentation" cellspacing="0" cellpadding="0" align="left" bgcolor="#1c1c1c" valign="top">
                                                                <div style="font-size: 16px; color: #fff; background-color: transparent; padding: 20px 0;">
                                                                    <p style="line-height: 1.25; mso-line-height-rule: at-least; margin: 0; padding: 0;" align="center">
                                                                        <span style="color: #fff;">
                                                                            © <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $organisation ); ?>
                                                                        </span>
                                                                    </p>
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
        </div>
    </body>
</html>
