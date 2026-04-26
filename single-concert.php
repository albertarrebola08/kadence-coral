<?php
/**
 * Single Concert
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

wp_enqueue_style(
	'coral-concerts',
	get_stylesheet_directory_uri() . '/concerts/concerts.css',
	[],
	'1.0'
);

$dades = get_field( 'dades_dels_concerts' );

$imatge = '';
$data_raw = '';
$ubicacio = '';
$repertori = '';
$descripcio = '';

if ( is_array( $dades ) ) {
	$imatge     = $dades['imatge'] ?? '';
	$data_raw   = $dades['data_i_hora'] ?? '';
	$ubicacio   = $dades['ubicacio'] ?? '';
	$repertori  = $dades['repertori'] ?? '';
	$descripcio = $dades['descripcio'] ?? '';
}

$data = '';
$hora = '';
$data_llarga = '';

if ( $data_raw ) {
	try {
		$date = new DateTime( $data_raw );

		$data = $date->format( 'd/m/Y' );
		$hora = $date->format( 'H:i' );

		$fmt = new IntlDateFormatter(
			'ca_ES',
			IntlDateFormatter::FULL,
			IntlDateFormatter::NONE,
			null,
			null,
			"EEEE, d 'de' MMMM 'de' y"
		);

		$data_llarga = $fmt->format( $date );

	} catch ( Exception $e ) {
		$data_llarga = '';
	}
}
?>

<main id="primary" class="site-main concert-single">

	<?php while ( have_posts() ) : the_post(); ?>

		<section class="concert-single-hero">

			<?php if ( $imatge ) : ?>
				<div class="concert-single-hero__image">
					<img src="<?php echo esc_url( $imatge ); ?>" alt="<?php the_title_attribute(); ?>">
				</div>
			<?php endif; ?>

			<div class="concert-single-hero__content">
				<p class="concert-single-kicker">Concert</p>

				<h1 class="concert-single-title"><?php the_title(); ?></h1>

				<div class="concert-single-meta">

					<?php if ( $data_llarga ) : ?>
						<div class="concert-single-meta__item">
							<strong>Data</strong>
							<span><?php echo esc_html( ucfirst( $data_llarga ) ); ?></span>
						</div>
					<?php endif; ?>

					<?php if ( $hora ) : ?>
						<div class="concert-single-meta__item">
							<strong>Hora</strong>
							<span><?php echo esc_html( $hora ); ?></span>
						</div>
					<?php endif; ?>

					<?php if ( $ubicacio ) : ?>
						<div class="concert-single-meta__item">
							<strong>Ubicació</strong>
							<span><?php echo wp_kses_post( $ubicacio ); ?></span>
						</div>
					<?php endif; ?>

				</div>
			</div>

		</section>

		<section class="concert-single-body">

			<?php if ( $descripcio ) : ?>
				<div class="concert-single-section">
					<h2>Descripció</h2>
					<div class="concert-single-text">
						<?php echo wp_kses_post( $descripcio ); ?>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( $repertori ) : ?>
				<div class="concert-single-section">
					<h2>Repertori</h2>
					<div class="concert-single-repertori">
						<?php echo wp_kses_post( $repertori ); ?>
					</div>
				</div>
			<?php endif; ?>

			<div class="concert-single-back">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="concert-button">
					Tornar a l'inici
				</a>
			</div>

		</section>

	<?php endwhile; ?>

</main>

<?php
get_footer();