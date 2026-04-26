<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode: [concerts_arxiu]
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
			'per_page' => 10,
		],
		$atts,
		'concerts_arxiu'
	);

	$per_page = absint( $atts['per_page'] );

	if ( ! $per_page ) {
		$per_page = 10;
	}

	$paged = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1;

	if ( isset( $_GET['concert_page'] ) ) {
		$paged = absint( $_GET['concert_page'] );
	}

	$estat = isset( $_GET['estat'] ) ? sanitize_text_field( $_GET['estat'] ) : 'tots';
	$zona = isset( $_GET['zona'] ) ? sanitize_text_field( $_GET['zona'] ) : 'totes';

	$avui = current_time( 'Y-m-d H:i:s' );

	$meta_query = [
		'relation' => 'AND',
	];

	if ( $estat === 'proxims' ) {
		$meta_query[] = [
			'key' => 'dades_dels_concerts_data_i_hora',
			'value' => $avui,
			'compare' => '>=',
			'type' => 'DATETIME',
		];
	}

	if ( $estat === 'passats' ) {
		$meta_query[] = [
			'key' => 'dades_dels_concerts_data_i_hora',
			'value' => $avui,
			'compare' => '<',
			'type' => 'DATETIME',
		];
	}

	/**
	 * Filtre per zona.
	 * Com que la zona està al CPT lloc_cantat, primer busquem els llocs d'aquella zona
	 * i després filtrem concerts que tinguin ubicacio_mapa dins aquests IDs.
	 */
	if ( $zona !== 'totes' ) {

		$llocs_ids = coral_get_llocs_ids_by_zona( $zona );

		if ( ! empty( $llocs_ids ) ) {
			$meta_query[] = [
				'key' => 'dades_dels_concerts_ubicacio_mapa',
				'value' => $llocs_ids,
				'compare' => 'IN',
			];
		} else {
			// Forcem que no retorni res si no hi ha llocs d'aquesta zona.
			$meta_query[] = [
				'key' => 'dades_dels_concerts_ubicacio_mapa',
				'value' => '-1',
				'compare' => '=',
			];
		}
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

	ob_start();
	?>

	<div class="concerts-archive">

		<form class="concerts-archive-filters" method="get">

			<div class="concerts-filter">
				<label for="concert-estat">Tipus</label>
				<select id="concert-estat" name="estat">
					<option value="tots" <?php selected( $estat, 'tots' ); ?>>Tots</option>
					<option value="proxims" <?php selected( $estat, 'proxims' ); ?>>Pròxims</option>
					<option value="passats" <?php selected( $estat, 'passats' ); ?>>Passats</option>
				</select>
			</div>

			<div class="concerts-filter">
				<label for="concert-zona">Zona</label>
				<select id="concert-zona" name="zona">
					<option value="totes" <?php selected( $zona, 'totes' ); ?>>Totes</option>
					<option value="catalunya" <?php selected( $zona, 'catalunya' ); ?>>Catalunya</option>
					<option value="resta_espanya" <?php selected( $zona, 'resta_espanya' ); ?>>Resta d'Espanya</option>
					<option value="resta_del_mon" <?php selected( $zona, 'resta_del_mon' ); ?>>Resta del món</option>
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
								'estat' => $estat,
								'zona' => $zona,
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
				No s’han trobat concerts amb aquests filtres.
			</p>

		<?php endif; ?>

	</div>

	<?php
	wp_reset_postdata();

	return ob_get_clean();
}
add_shortcode( 'concerts_arxiu', 'coral_concerts_arxiu_shortcode' );

/**
 * Retorna IDs de lloc_cantat filtrats per zona.
 */
function coral_get_llocs_ids_by_zona( $zona ) {

	$query = new WP_Query(
		[
			'post_type' => 'lloc_cantat',
			'posts_per_page' => -1,
			'post_status' => 'publish',
			'fields' => 'ids',
			'meta_query' => [
				[
					'key' => 'informacio_llocs_on_hem_cantat_zona',
					'value' => $zona,
					'compare' => '=',
				],
			],
		]
	);

	return $query->posts;
}