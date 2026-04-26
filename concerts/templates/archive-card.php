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
$ubicacio = $dades['ubicacio'] ?? '';
$lloc_id = $dades['ubicacio_mapa'] ?? '';
$fitxa_pdf = $dades['fitxa_del_concert'] ?? '';

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

$nom_lloc = '';

if ( $lloc_id ) {
	$nom_lloc = get_the_title( $lloc_id );
}
?>

<article class="concert-archive-card">

	<a class="concert-archive-card__link" href="<?php the_permalink(); ?>">

		<?php if ( $imatge_url ) : ?>
			<div class="concert-archive-card__image">
				<img src="<?php echo esc_url( $imatge_url ); ?>" alt="<?php echo esc_attr( $imatge_alt ); ?>">
			</div>
		<?php endif; ?>

		<div class="concert-archive-card__content">

			<?php if ( $data_formatejada ) : ?>
				<p class="concert-archive-card__date">
					<?php echo esc_html( $data_formatejada ); ?>
					<?php if ( $hora ) : ?>
						<span>
							<?php echo esc_html( $hora ); ?>
						</span>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<h3 class="concert-archive-card__title">
				<?php the_title(); ?>
			</h3>

			<?php if ( $nom_lloc ) : ?>
				<p class="concert-archive-card__place">
					<?php echo esc_html( $nom_lloc ); ?>
				</p>
			<?php elseif ( $ubicacio ) : ?>
				<div class="concert-archive-card__place">
					<?php echo wp_kses_post( wp_trim_words( $ubicacio, 12, '...' ) ); ?>
				</div>
			<?php endif; ?>

			<span class="concert-archive-card__more">
				Veure concert
			</span>

		</div>

	</a>

</article>