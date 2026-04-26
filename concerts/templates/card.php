<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Dades ACF (group)
 */
$dades = get_field( 'dades_dels_concerts' );
if ( ! is_array( $dades ) )
	return;

$imatge = $dades['imatge'] ?? '';
$data_raw = $dades['data_i_hora'] ?? '';
$ubicacio = $dades['ubicacio'] ?? '';
$repertori = $dades['repertori'] ?? '';
$descripcio = $dades['descripcio'] ?? '';

/**
 * Format data / hora
 * ACF retorna: Y-m-d H:i:s
 */
$data = '';
$hora = '';
$mes_cat = '';

if ( $data_raw ) {
	try {
		$date = new DateTime( $data_raw );
		$data = $date->format( 'd/m/Y' );
		$hora = $date->format( 'H:i' );

		$fmt = new IntlDateFormatter(
			'ca_ES',
			IntlDateFormatter::NONE,
			IntlDateFormatter::NONE,
			null,
			null,
			'MMM'
		);
		$mes_cat = mb_strtoupper( $fmt->format( $date ), 'UTF-8' );

	} catch (Exception $e) {
	}
}
?>

<article class="concert-card">
	<a href="<?php the_permalink(); ?>" style="text-decoration: none;">
		<?php if ( $imatge ) : ?>
			<div class="concert-image">
				<img src="<?php echo esc_url( $imatge ); ?>" alt="<?php the_title_attribute(); ?>">

				<?php if ( $data_raw && isset( $date ) ) : ?>
					<div class="concert-date-badge">
						<span class="day"><?php echo esc_html( $date->format( 'd' ) ); ?></span>
						<span class="month"><?php echo esc_html( $mes_cat ); ?></span>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="concert-content">

			<h3 class="concert-title"><?php the_title(); ?>
			</h3>

			<?php if ( $hora ) : ?>
				<div class="concert-hour">
					<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path
							d="M12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21Z"
							stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path>
						<path d="M12 6V12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
							stroke-linejoin="round">
						</path>
						<path d="M16.24 16.24L12 12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"
							stroke-linejoin="round"></path>
					</svg>
					<?php echo esc_html( $hora ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $ubicacio ) : ?>
				<div class="concert-place">
					<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path
							d="M12 21C15.5 17.4 19 14.1764 19 10.2C19 6.22355 15.866 3 12 3C8.13401 3 5 6.22355 5 10.2C5 14.1764 8.5 17.4 12 21Z"
							stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
						<path
							d="M12 13C13.6569 13 15 11.6569 15 10C15 8.34315 13.6569 7 12 7C10.3431 7 9 8.34315 9 10C9 11.6569 10.3431 13 12 13Z"
							stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>
					</svg>
					<?php echo wp_kses_post( $ubicacio ); ?>
				</div>
			<?php endif; ?>

			<?php if ( $descripcio ) : ?>
				<div class="concert-desc concert-desc--card">
					<?php echo wp_kses_post( wp_trim_words( $descripcio, 22, '...' ) ); ?>
				</div>
			<?php endif; ?>

			<a class="concert-button" href="<?php the_permalink(); ?>">
				Veure repertori
			</a>

		</div>
	</a>

</article>