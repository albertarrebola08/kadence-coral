<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode: [coral_memoria_activitats]
 * Mostra la memòria d'activitats agrupada per anys.
 */
function coral_memoria_activitats_shortcode() {

	if ( ! function_exists( 'get_field' ) ) {
		return '';
	}

	$page_id = get_the_ID();

	$memories = get_field( 'memoria_activitats', $page_id );

	if ( empty( $memories ) || ! is_array( $memories ) ) {
		return '<p>Encara no hi ha cap memòria d’activitats publicada.</p>';
	}

	/*
	 * Ordenem automàticament de més recent a més antic.
	 * Exemple: 2025, 2024, 2023...
	 */
	usort( $memories, function ( $a, $b ) {
		$any_a = isset( $a['any'] ) ? intval( $a['any'] ) : 0;
		$any_b = isset( $b['any'] ) ? intval( $b['any'] ) : 0;

		return $any_b <=> $any_a;
	} );

	ob_start();
	?>

	<section class="coral-memoria-activitats">

		<?php foreach ( $memories as $memoria ) : ?>

			<?php
			$any = $memoria['any'] ?? '';
			$activitats = $memoria['activitats_fetes'] ?? '';

			if ( empty( $any ) && empty( $activitats ) ) {
				continue;
			}
			?>

			<article class="coral-memoria-any" id="memoria-<?php echo esc_attr( $any ); ?>">

				<?php if ( $any ) : ?>
					<header class="coral-memoria-any__header">
						<h2>
							<?php echo esc_html( $any ); ?>
						</h2>
					</header>
				<?php endif; ?>

				<?php if ( $activitats ) : ?>
					<div class="coral-memoria-any__content">
						<?php echo wp_kses_post( $activitats ); ?>
					</div>
				<?php endif; ?>

			</article>

		<?php endforeach; ?>

	</section>

	<?php
	return ob_get_clean();
}

add_shortcode( 'coral_memoria_activitats', 'coral_memoria_activitats_shortcode' );