<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$dades = get_field( 'dades_dels_concerts' );

if ( ! is_array( $dades ) ) {
	return;
}

$imatge = $dades['imatge'] ?? '';
$data_raw = $dades['data_i_hora'] ?? '';

$imatge_url = '';
$imatge_alt = get_the_title();

if ( $imatge ) {
	if ( is_array( $imatge ) ) {
		$imatge_url = $imatge['url'] ?? '';
		$imatge_alt = $imatge['alt'] ?? get_the_title();
	} else {
		$imatge_url = $imatge;
	}
}

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
		aria-label="<?php echo esc_attr( 'Veure concert: ' . get_the_title() ); ?>">

		<?php if ( $imatge_url ) : ?>
			<div class="concert-archive-card__image">
				<img src="<?php echo esc_url( $imatge_url ); ?>" alt="<?php echo esc_attr( $imatge_alt ); ?>">
			</div>
		<?php endif; ?>

		<div class="concert-archive-card__content">

			<?php if ( $data_formatejada ) : ?>
				<p class="concert-archive-card__date">
					<?php echo esc_html( $data_formatejada ); ?>

					<?php if ( $hora && $hora !== '00:00' ) : ?>
						<span><?php echo esc_html( $hora ); ?></span>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<h3 class="concert-archive-card__title">
				<?php the_title(); ?>
			</h3>

		</div>

		<span class="concert-archive-card__arrow" aria-hidden="true">
			→
		</span>

	</a>

</article>