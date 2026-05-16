<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function coral_fitxes_concerts_get_file_url( $file ): string {
	if ( empty( $file ) ) {
		return '';
	}

	if ( is_array( $file ) && ! empty( $file['url'] ) ) {
		return (string) $file['url'];
	}

	if ( is_numeric( $file ) ) {
		$url = wp_get_attachment_url( (int) $file );
		return $url ? $url : '';
	}

	return is_string( $file ) ? $file : '';
}

function coral_fitxes_concerts_shortcode( $atts = [] ): string {
	if ( ! is_user_logged_in() ) {
		return '<p>Has d\'iniciar sessió per veure les fitxes dels concerts.</p>';
	}

	wp_enqueue_style(
		'coral-partitures',
		get_stylesheet_directory_uri() . '/partitures/partitures.css',
		[],
		'1.4'
	);

	wp_enqueue_script(
		'coral-partitures',
		get_stylesheet_directory_uri() . '/partitures/partitures.js',
		[ 'jquery' ],
		'1.4',
		true
	);

	$atts = shortcode_atts(
		[
			'per_page' => 12,
		],
		$atts,
		'fitxes_concerts'
	);

	$per_page = max( 1, absint( $atts['per_page'] ) );
	$paged    = max( 1, absint( $_GET['fitxes_page'] ?? 1 ) );

	$query = new WP_Query(
		[
			'post_type'      => 'concert',
			'posts_per_page' => $per_page,
			'paged'          => $paged,
			'post_status'    => 'publish',
			'meta_key'       => 'dades_dels_concerts_data_i_hora',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
			'meta_query'     => [
				[
					'key'     => 'dades_dels_concerts_fitxa_del_concert',
					'value'   => '',
					'compare' => '!=',
				],
			],
		]
	);

	ob_start();
	?>
	<section class="fitxes-concerts">
		<?php if ( $query->have_posts() ) : ?>
			<div class="fitxes-concerts-grid">
				<?php
				while ( $query->have_posts() ) :
					$query->the_post();

					$post_id = get_the_ID();
					$dades   = get_field( 'dades_dels_concerts', $post_id );

					if ( ! is_array( $dades ) ) {
						continue;
					}

					$pdf_url = coral_fitxes_concerts_get_file_url( $dades['fitxa_del_concert'] ?? '' );

					if ( ! $pdf_url ) {
						continue;
					}

					$title    = coral_get_concert_display_title( $post_id, $dades );
					$date_raw = $dades['data_i_hora'] ?? '';
					$date     = '';

					if ( $date_raw ) {
						try {
							$date = ( new DateTime( $date_raw ) )->format( 'd/m/Y' );
						} catch ( Exception $e ) {
							$date = '';
						}
					}
					?>
					<article class="fitxa-concert-card">
						<header class="fitxa-concert-card__header">
							<?php if ( $date ) : ?>
								<p class="fitxa-concert-card__date"><?php echo esc_html( $date ); ?></p>
							<?php endif; ?>

							<h3 class="fitxa-concert-card__title"><?php echo esc_html( $title ); ?></h3>
						</header>

						<div class="document-preview fitxa-concert-card__preview">
							<iframe src="<?php echo esc_url( $pdf_url ); ?>#toolbar=0&navpanes=0&zoom=150" loading="lazy"
								title="<?php echo esc_attr( 'Vista prèvia de la fitxa de ' . $title ); ?>"></iframe>
						</div>

						<div class="fitxa-concert-card__actions">
							<a class="btn-preview fitxa-concert-card__button" href="#" data-pdf="<?php echo esc_url( $pdf_url ); ?>">
								Previsualitzar
							</a>
							<a class="btn-pdf fitxa-concert-card__button" href="<?php echo esc_url( $pdf_url ); ?>" target="_blank"
								rel="noopener" download>
								Descarregar PDF
							</a>
						</div>
					</article>
				<?php endwhile; ?>
			</div>

			<?php if ( (int) $query->max_num_pages > 1 ) : ?>
				<nav class="fitxes-concerts-pagination" aria-label="Paginació de fitxes de concerts">
					<?php
					$total_pages = (int) $query->max_num_pages;
					$base_url    = remove_query_arg( 'fitxes_page' );

					if ( $paged > 1 ) {
						printf(
							'<a href="%1$s#tab-strongfitxesdeconcertsstrong">Anterior</a>',
							esc_url( add_query_arg( 'fitxes_page', $paged - 1, $base_url ) )
						);
					}

					for ( $i = 1; $i <= $total_pages; $i++ ) {
						if ( $i === 1 || $i === $total_pages || abs( $i - $paged ) <= 2 ) {
							printf(
								'<a class="%1$s" href="%2$s#tab-strongfitxesdeconcertsstrong">%3$d</a>',
								$i === $paged ? 'is-active' : '',
								esc_url( add_query_arg( 'fitxes_page', $i, $base_url ) ),
								(int) $i
							);
						} elseif ( abs( $i - $paged ) === 3 ) {
							echo '<span>...</span>';
						}
					}

					if ( $paged < $total_pages ) {
						printf(
							'<a href="%1$s#tab-strongfitxesdeconcertsstrong">Seguent</a>',
							esc_url( add_query_arg( 'fitxes_page', $paged + 1, $base_url ) )
						);
					}
					?>
				</nav>
			<?php endif; ?>
		<?php else : ?>
			<p class="fitxes-concerts-empty">Encara no hi ha fitxes de concerts publicades.</p>
		<?php endif; ?>

		<?php echo function_exists( 'coral_pdf_modal_html' ) ? coral_pdf_modal_html() : ''; ?>
	</section>
	<?php

	wp_reset_postdata();
	return ob_get_clean();
}

add_shortcode( 'fitxes_concerts', 'coral_fitxes_concerts_shortcode' );
