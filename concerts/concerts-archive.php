<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode: [concerts_arxiu]
 *
 * Mostra un arxiu de concerts amb:
 * - Filtre automàtic per any
 * - Grid de cards
 * - Paginació
 */
function coral_concerts_arxiu_shortcode( $atts ) {

	wp_enqueue_style(
		'coral-concerts',
		get_stylesheet_directory_uri() . '/concerts/concerts.css',
		[],
		'1.0'
	);

	$atts = shortcode_atts(
		[
			'per_page' => 15,
		],
		$atts,
		'concerts_arxiu'
	);

	$per_page = absint( $atts['per_page'] );

	if ( ! $per_page ) {
		$per_page = 15;
	}

	$paged = 1;

	if ( get_query_var( 'paged' ) ) {
		$paged = absint( get_query_var( 'paged' ) );
	}

	if ( isset( $_GET['concert_page'] ) ) {
		$paged = absint( $_GET['concert_page'] );
	}

	$any = isset( $_GET['any'] ) ? sanitize_text_field( $_GET['any'] ) : 'tots';

	$meta_query = [
		'relation' => 'AND',
	];

	if ( $any !== 'tots' ) {
		$meta_query[] = [
			'key' => 'dades_dels_concerts_data_i_hora',
			'value' => [
				$any . '-01-01 00:00:00',
				$any . '-12-31 23:59:59',
			],
			'compare' => 'BETWEEN',
			'type' => 'DATETIME',
		];
	}

	$query_args = [
		'post_type' => 'concert',
		'posts_per_page' => $per_page,
		'post_status' => 'publish',
		'paged' => $paged,
		'meta_key' => 'dades_dels_concerts_data_i_hora',
		'orderby' => 'meta_value',
		'order' => 'DESC',
	];

	if ( count( $meta_query ) > 1 ) {
		$query_args['meta_query'] = $meta_query;
	}

	$query = new WP_Query( $query_args );

	$years = coral_get_concert_years();

	ob_start();
	?>

	<div class="concerts-archive">

		<form class="concerts-archive-filters" method="get">

			<div class="concerts-filter">
				<label for="concert-any">Filtrar per any</label>

				<select id="concert-any" name="any">
					<option value="tots" <?php selected( $any, 'tots' ); ?>>
						Tots els anys
					</option>

					<?php foreach ( $years as $year ) : ?>
						<option value="<?php echo esc_attr( $year ); ?>" <?php selected( $any, $year ); ?>>
							<?php echo esc_html( $year ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<button type="submit" class="concerts-filter-button">
				Filtrar
			</button>

			<a class="concerts-filter-reset" href="<?php echo esc_url( get_permalink() ); ?>">
				Netejar
			</a>

		</form>

		<?php if ( $query->have_posts() ) : ?>

			<div class="concerts-archive-grid">

				<?php
				while ( $query->have_posts() ) :
					$query->the_post();

					include get_stylesheet_directory() . '/concerts/templates/archive-card.php';

				endwhile;
				?>

			</div>

			<?php
			$total_pages = (int) $query->max_num_pages;

			if ( $total_pages > 1 ) :
				?>
				<nav class="concerts-pagination" aria-label="Paginació de concerts">
					<?php
					for ( $i = 1; $i <= $total_pages; $i++ ) {
						$url = add_query_arg(
							[
								'concert_page' => $i,
								'any' => $any,
							],
							get_permalink()
						);

						printf(
							'<a class="%s" href="%s">%s</a>',
							$i === $paged ? 'is-active' : '',
							esc_url( $url ),
							esc_html( $i )
						);
					}
					?>
				</nav>
			<?php endif; ?>

		<?php else : ?>

			<p class="concerts-archive-empty">
				No s’han trobat concerts per a aquest any.
			</p>

		<?php endif; ?>

	</div>

	<?php
	wp_reset_postdata();

	return ob_get_clean();
}
add_shortcode( 'concerts_arxiu', 'coral_concerts_arxiu_shortcode' );

/**
 * Retorna els anys disponibles segons les dates dels concerts.
 *
 * Així no cal tocar codi quan arribin 2027, 2028, etc.
 * Si existeix un concert amb data d'aquell any, apareix al filtre.
 */
function coral_get_concert_years() {

	$query = new WP_Query(
		[
			'post_type' => 'concert',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'fields' => 'ids',
			'meta_key' => 'dades_dels_concerts_data_i_hora',
			'orderby' => 'meta_value',
			'order' => 'DESC',
			'meta_query' => [
				[
					'key' => 'dades_dels_concerts_data_i_hora',
					'compare' => 'EXISTS',
				],
			],
		]
	);

	$years = [];

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $post_id ) {

			$dades = get_field( 'dades_dels_concerts', $post_id );

			if ( ! is_array( $dades ) ) {
				continue;
			}

			$data_raw = $dades['data_i_hora'] ?? '';

			if ( ! $data_raw ) {
				continue;
			}

			try {
				$date = new DateTime( $data_raw );
				$year = $date->format( 'Y' );

				if ( $year ) {
					$years[] = $year;
				}
			} catch (Exception $e) {
				continue;
			}
		}
	}

	wp_reset_postdata();

	$years = array_unique( $years );
	rsort( $years, SORT_NUMERIC );

	return $years;
}