<?php
/**
 * Szablon e-maila (tabele i style w atrybutach — zgodność z Outlookiem i Gmailem).
 *
 * Zmienne: $args['title'], $args['preheader'], $args['body'], $args['footer'].
 *
 * @package TSL_Core
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html lang="pl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title><?php echo esc_html( $args['title'] ); ?></title>
</head>
<body style="margin:0;padding:0;background:#E6E8EB;">
<?php if ( $args['preheader'] ) : ?>
<div style="display:none;max-height:0;overflow:hidden;opacity:0;color:transparent;"><?php echo esc_html( $args['preheader'] ); ?></div>
<?php endif; ?>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#E6E8EB;">
	<tr>
		<td align="center" style="padding:24px 12px;">
			<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#FFFFFF;border-radius:6px;overflow:hidden;font-family:Arial,Helvetica,sans-serif;">
				<tr>
					<td style="background:#0B1D33;padding:22px 32px;border-bottom:3px solid #B8734A;">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="text-decoration:none;">
							<span style="font-family:Arial,Helvetica,sans-serif;font-size:22px;font-weight:800;letter-spacing:1px;color:#FFFFFF;">TSL</span>
							<span style="font-family:Arial,Helvetica,sans-serif;font-size:11px;letter-spacing:4px;color:#E6E8EB;padding-left:6px;">MANAGER</span>
						</a>
					</td>
				</tr>
				<tr>
					<td style="padding:32px 32px 8px;">
						<h1 style="margin:0 0 20px;font-family:Georgia,'Times New Roman',serif;font-size:30px;line-height:1.15;font-weight:600;color:#0B1D33;"><?php echo esc_html( $args['title'] ); ?></h1>
						<div style="font-size:16px;line-height:1.6;color:#2B2F36;">
							<?php echo $args['body']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- treść składana i oczyszczana w mailer.php ?>
						</div>
					</td>
				</tr>
				<tr>
					<td style="padding:24px 32px 32px;">
						<a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="color:#8F5430;font-weight:700;font-size:15px;text-decoration:none;">Więcej w serwisie TSL Manager →</a>
					</td>
				</tr>
				<tr>
					<td style="background:#0B1D33;padding:24px 32px;font-size:12px;line-height:1.6;color:#E6E8EB;">
						<?php echo $args['footer']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- składane z esc_* w mailer.php ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</body>
</html>
