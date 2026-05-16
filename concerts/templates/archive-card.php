<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dades = get_field( 'dades_dels_concerts' );
$post_id = get_the_ID();
$concert_title = coral_get_concert_display_title( $post_id, $dades );

if ( ! is_array( $dades ) ) {
	return;
}

$imatge = $dades['imatge'] ?? '';
$data_raw = $dades['data_i_hora'] ?? '';

$imatge_url = '';
$imatge_alt = $concert_title;

if ( $imatge ) {
	if ( is_array( $imatge ) ) {
		$imatge_url = $imatge['url'] ?? '';
		$imatge_alt = $imatge['alt'] ?? $concert_title;
	} elseif ( is_numeric( $imatge ) ) {
		$imatge_url = wp_get_attachment_image_url( (int) $imatge, 'medium_large' );
		$imatge_alt = get_post_meta( (int) $imatge, '_wp_attachment_image_alt', true ) ?: $concert_title;
	} else {
		$imatge_url = $imatge;
	}
}

$gradient_seed = abs( crc32( $post_id . '|' . $concert_title ) );
$gradient_hue = $gradient_seed % 360;
$gradient_angle = 120 + ( $gradient_seed % 80 );
$gradient_style = sprintf(
	'--archive-gradient-angle:%1$ddeg;--archive-gradient-a:hsl(%2$d 58%% 34%%);--archive-gradient-b:hsl(%3$d 72%% 44%%);--archive-gradient-c:hsl(%4$d 66%% 70%%);',
	$gradient_angle,
	$gradient_hue,
	( $gradient_hue + 42 ) % 360,
	( $gradient_hue + 190 ) % 360
);

$data_formatejada = '';
$hora = '';

if ( $data_raw ) {
	try {
		$date = new DateTime( $data_raw );
		$data_formatejada = $date->format( 'd/m/Y' );
		$hora = $date->format( 'H:i' );
	} catch (Exception $e) {
		$data_formatejada = '';
		$hora = '';
	}
}
?>

<article class="concert-archive-card">

	<a class="concert-archive-card__link" href="<?php the_permalink(); ?>"
		aria-label="<?php echo esc_attr( 'Veure concert: ' . $concert_title ); ?>">

		<?php if ( $imatge_url ) : ?>
			<div class="concert-archive-card__image">
				<img src="<?php echo esc_url( $imatge_url ); ?>" alt="<?php echo esc_attr( $imatge_alt ); ?>">
			</div>
		<?php else : ?>
			<div class="concert-archive-card__placeholder" style="<?php echo esc_attr( $gradient_style ); ?>"
				aria-hidden="true"></div>
		<?php endif; ?>

		<div class="concert-archive-card__content">

			<?php if ( $data_formatejada ) : ?>
				<p class="concert-archive-card__date">
					<?php echo esc_html( $data_formatejada ); ?>

					<?php if ( $hora && $hora !== '00:00' ) : ?>
						<span>
							<?php echo esc_html( $hora ); ?>
						</span>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<h3 class="concert-archive-card__title">
				<?php echo esc_html( $concert_title ); ?>
			</h3>

		</div>

		<span class="concert-archive-card__arrow" aria-hidden="true">
			→
		</span>

	</a>

</article>
