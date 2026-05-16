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
		'1.4'
	);

	wp_enqueue_script(
		'coral-concerts-archive',
		get_stylesheet_directory_uri() . '/concerts/concerts-archive.js',
		[ 'jquery' ],
		'1.1',
		true
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

	$shortcode_post = get_post();
	$archive_url    = $shortcode_post instanceof WP_Post ? get_permalink( $shortcode_post->ID ) : '';

	if ( ! $archive_url ) {
		$archive_url = get_permalink( get_queried_object_id() );
	}

	$archive_url = remove_query_arg( [ 'concert_page', 'any' ], $archive_url );

	$paged = 1;

	if ( get_query_var( 'paged' ) ) {
		$paged = absint( get_query_var( 'paged' ) );
	}

	if ( isset( $_GET['concert_page'] ) ) {
		$paged = absint( $_GET['concert_page'] );
	}

	$any = isset( $_GET['any'] ) ? sanitize_text_field( $_GET['any'] ) : 'tots';
	$any = coral_concerts_normalize_year_filter( $any );

	$years = coral_get_concert_years();
	$initial_render = coral_concerts_arxiu_render_fragment( $any, $paged, $per_page, $archive_url );

	wp_localize_script(
		'coral-concerts-archive',
		'coralConcertsArchive',
		[
			'ajaxurl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'coral_concerts_archive_nonce' ),
			'base_url'   => $archive_url,
			'per_page'   => $per_page,
		]
	);

	ob_start();
	?>

	<div class="concerts-archive" id="concerts-archive" data-base-url="<?php echo esc_attr( $archive_url ); ?>">

		<form id="concerts-archive-filters" class="concerts-archive-filters" method="get" action="<?php echo esc_url( $archive_url ); ?>">
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

			<input type="hidden" name="action" value="coral_get_concerts_archive" />
			<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'coral_concerts_archive_nonce' ) ); ?>" />
			<input type="hidden" name="concert_page" value="<?php echo esc_attr( $paged ); ?>" />
		</form>

		<div id="concerts-archive-results" class="concerts-archive-results">
			<?php echo $initial_render['html']; ?>
		</div>
	</div>

	<?php
	return ob_get_clean();
}

add_shortcode( 'concerts_arxiu', 'coral_concerts_arxiu_shortcode' );

/**
 * Retorna un fragment HTML de la llista de concerts i paginació.
 */
function coral_concerts_arxiu_render_fragment( $any, $paged, $per_page, $archive_url ) {

	$any = coral_concerts_normalize_year_filter( $any );
	$paged = max( 1, absint( $paged ) );
	$per_page = max( 1, absint( $per_page ) );

	$meta_query = [
		'relation' => 'AND',
	];

	if ( $any !== 'tots' ) {
		$meta_query[] = [
			'key'     => 'dades_dels_concerts_data_i_hora',
			'value'   => [
				$any . '-01-01 00:00:00',
				$any . '-12-31 23:59:59',
			],
			'compare' => 'BETWEEN',
			'type'    => 'DATETIME',
		];
	}

	$query_args = [
		'post_type'      => 'concert',
		'posts_per_page' => $per_page,
		'post_status'    => 'publish',
		'paged'          => $paged,
		'meta_key'       => 'dades_dels_concerts_data_i_hora',
		'orderby'        => 'meta_value',
		'order'          => 'DESC',
	];

	if ( count( $meta_query ) > 1 ) {
		$query_args['meta_query'] = $meta_query;
	}

	$query = new WP_Query( $query_args );

	ob_start();

	if ( $query->have_posts() ) :

		?>

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
							'any'          => $any,
						],
						$archive_url
					);

					printf(
						'<a class="%1$s" href="%2$s" data-page="%3$d">%4$s</a>',
						$i === $paged ? 'is-active' : '',
						esc_url( $url ),
						$i,
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

	<?php endif;

	wp_reset_postdata();
	$fragment = ob_get_clean();

	return [
		'html'        => $fragment,
		'currentPage' => $paged,
		'totalPages'  => isset( $total_pages ) ? (int) $total_pages : 0,
	];
}

function coral_concerts_normalize_year_filter( $value ) {
	$allowed_any = 'tots';

	if ( $value === $allowed_any ) {
		return $allowed_any;
	}

	return preg_match( '/^\d{4}$/', $value ) ? $value : $allowed_any;
}

/**
 * AJAX: refés la llista de concerts quan canvia l'any o la pàgina.
 */
add_action( 'wp_ajax_coral_get_concerts_archive', 'coral_get_concerts_archive' );
add_action( 'wp_ajax_nopriv_coral_get_concerts_archive', 'coral_get_concerts_archive' );

function coral_get_concerts_archive() {

	$nonce = sanitize_text_field( $_POST['nonce'] ?? '' );
	if ( ! wp_verify_nonce( $nonce, 'coral_concerts_archive_nonce' ) ) {
		wp_send_json_error(
			[
				'message' => 'Token d\'autenticació invàlid',
			],
			403
		);
	}

	$any = coral_concerts_normalize_year_filter(
		sanitize_text_field( $_POST['any'] ?? 'tots' )
	);

	$paged = max( 1, absint( $_POST['concert_page'] ?? 1 ) );
	$per_page = max( 1, absint( $_POST['per_page'] ?? 15 ) );
	$archive_url = sanitize_text_field( $_POST['archive_url'] ?? '' );
	$archive_url = remove_query_arg( [ 'concert_page', 'any' ], esc_url_raw( $archive_url ) );

	if ( ! $archive_url ) {
		$archive_url = home_url( '/que-fem/concerts/' );
	}

	$fragment = coral_concerts_arxiu_render_fragment( $any, $paged, $per_page, $archive_url );

	wp_send_json_success(
		[
			'html'        => $fragment['html'],
			'any'         => $any,
			'currentPage' => $fragment['currentPage'],
			'totalPages'  => $fragment['totalPages'],
		]
	);
}

/**
 * Retorna els anys disponibles segons les dates dels concerts.
 *
 * Així no cal tocar codi quan arribin 2027, 2028, etc.
 * Si existeix un concert amb data d'aquell any, apareix al filtre.
 */
function coral_get_concert_years() {

	$query = new WP_Query(
		[
			'post_type'      => 'concert',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'meta_key'       => 'dades_dels_concerts_data_i_hora',
			'orderby'        => 'meta_value',
			'order'          => 'DESC',
			'meta_query'     => [
				[
					'key'     => 'dades_dels_concerts_data_i_hora',
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
			} catch ( Exception $e ) {
				continue;
			}
		}
	}

	wp_reset_postdata();

	$years = array_unique( $years );
	rsort( $years, SORT_NUMERIC );

	return $years;
}
