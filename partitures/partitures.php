<?php
if ( ! defined( 'ABSPATH' ) )
	exit;

/* =====================================================
 * SHORTCODE [partitures]
 * Sidebar esquerra + grid dreta
 * ===================================================== */
function coral_partitures_shortcode() {

	if ( ! is_user_logged_in() ) {
		return '<p>Has d\'iniciar sessió per veure les partitures.</p>';
	}

	// CSS
	wp_enqueue_style(
		'coral-partitures',
		get_stylesheet_directory_uri() . '/partitures/partitures.css',
		[],
		'1.2'
	);

	// JS
	wp_enqueue_script(
		'coral-partitures',
		get_stylesheet_directory_uri() . '/partitures/partitures.js',
		[ 'jquery' ],
		'1.2',
		true
	);

	wp_localize_script(
		'coral-partitures',
		'coralPartitures',
		[
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'coral_partitures_nonce' ),
		]
	);

	ob_start(); ?>

	<section class="partitures-app">
		<div class="partitures-layout">

			<!-- SIDEBAR -->
			<aside class="partitures-sidebar">
				<form id="partitures-filtres" class="partitures-filtres">
					<input type="hidden" name="action" value="coral_get_partitures">
					<input type="hidden" name="nonce"
						value="<?php echo esc_attr( wp_create_nonce( 'coral_partitures_nonce' ) ); ?>">
					<input type="hidden" name="paged" value="1">
					<input type="hidden" name="orderby" value="">
					<input type="hidden" name="order" value="ASC">

					<!-- Cercador -->
					<label class="partitures-label" for="pf-search">Cercador general</label>
					<input id="pf-search" type="text" name="search" placeholder="Cerca per obra, autor, llibre…"
						autocomplete="off">

					<!-- Número + Llibre en línia -->
					<div class="partitures-row partitures-row--two">
						<div class="partitures-col">
							<label class="partitures-label" for="pf-numero">Número</label>
							<input id="pf-numero" type="text" name="numero" placeholder="Ex: 143 o 001" inputmode="numeric"
								autocomplete="off">
						</div>

						<div class="partitures-col">
							<label class="partitures-label" for="pf-llibre">Llibre / Llibret</label>
							<input id="pf-llibre" type="text" name="llibre_txt" placeholder="Ex: Llibret 45"
								autocomplete="off">
						</div>
					</div>

					<!-- Any exacte -->
					<label class="partitures-label" for="pf-any">Any</label>
					<input id="pf-any" type="number" name="any" placeholder="Ex: 1750" step="1" inputmode="numeric">

					<!-- Gènere -->
					<div class="partitures-col">
						<label class="partitures-label">Gènere</label>
						<select name="genere">
							<option value="">Tots els gèneres</option>
							<?php
							$terms = get_terms( [
								'taxonomy' => 'genere',
								'hide_empty' => false,
							] );
							if ( ! is_wp_error( $terms ) ) {
								foreach ( $terms as $term ) {
									echo '<option value="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</option>';
								}
							}
							?>
						</select>
					</div>

					<!-- Tradicional -->
					<div class="partitures-col">
						<label class="partitures-label">Tradicional</label>
						<select name="tradicional">
							<option value="">Totes (tradicional)</option>
							<?php
							$terms = get_terms( [
								'taxonomy' => 'tradicional',
								'hide_empty' => false,
							] );
							if ( ! is_wp_error( $terms ) ) {
								foreach ( $terms as $term ) {
									echo '<option value="' . esc_attr( $term->slug ) . '">' . esc_html( $term->name ) . '</option>';
								}
							}
							?>
						</select>
					</div>

				</form>
			</aside>

			<!-- CONTINGUT -->
			<main class="partitures-main">

				<div class="partitures-topbar">
					<div id="partitures-count" class="partitures-count">Carregant…</div>
					<div id="partitures-loader" class="partitures-loader" style="display:none;">Carregant…</div>
				</div>

				<div id="partitures-grid" class="partitures-grid">
					<p>Carregant partitures…</p>
				</div>

				<!-- MODAL PDF -->
				<div id="pdf-modal" class="pdf-modal">
					<div id="pdf-modal-backdrop" class="pdf-modal-backdrop"></div>
					<div class="pdf-modal-panel">
						<button id="pdf-modal-close" class="pdf-modal-close" type="button">Tancar</button>
						<iframe src="" title="Preview PDF"></iframe>
					</div>
				</div>

			</main>

		</div>
	</section>

	<?php
	return ob_get_clean();
}
add_shortcode( 'partitures', 'coral_partitures_shortcode' );


/* =====================================================
 * AJAX: obtenir partitures
 * - Cerca global: títol OR camps ACF (autor, harmonització, adaptació, traducció, llibre, número, temps)
 * - Filtres: taxonomies (AND) + meta_query (AND)
 * - Any exacte dins rang: any_inici <= any <= any_fi
 * - Ordre: per defecte per número, o per title (A–Z/Z–A)
 * ===================================================== */
add_action( 'wp_ajax_coral_get_partitures', 'coral_get_partitures' );
add_action( 'wp_ajax_nopriv_coral_get_partitures', 'coral_get_partitures' );

function coral_get_partitures() {

	if ( ! is_user_logged_in() )
		wp_die();

	$nonce = $_POST['nonce'] ?? '';
	if ( ! wp_verify_nonce( $nonce, 'coral_partitures_nonce' ) ) {
		wp_die( 'Nonce incorrecte' );
	}

	$search = sanitize_text_field( $_POST['search'] ?? '' );
	$numero = sanitize_text_field( $_POST['numero'] ?? '' );
	$llibreT = sanitize_text_field( $_POST['llibre_txt'] ?? '' );

	$paged = max( 1, intval( $_POST['paged'] ?? 1 ) );
	$orderby = sanitize_text_field( $_POST['orderby'] ?? '' );
	$order = ( $_POST['order'] ?? 'ASC' ) === 'DESC' ? 'DESC' : 'ASC';

	$any = isset( $_POST['any'] ) ? intval( $_POST['any'] ) : 0;

	$per_page = 6;

	/* =========================
	   TAX QUERY (AND)
	========================= */
	$taxonomies = [ 'genere', 'tradicional' ];
	$tax_query = [ 'relation' => 'AND' ];

	foreach ( $taxonomies as $tax ) {
		$val = sanitize_text_field( $_POST[ $tax ] ?? '' );
		if ( $val ) {
			$tax_query[] = [
				'taxonomy' => $tax,
				'field' => 'slug',
				'terms' => $val,
			];
		}
	}
	$use_tax_query = ( count( $tax_query ) > 1 ) ? $tax_query : [];

	/* =========================
	   META QUERY (AND)
	========================= */
	$meta_and = [ 'relation' => 'AND' ];

	if ( $numero !== '' ) {
		$meta_and[] = [
			'key' => 'dades_de_la_partitura_numero',
			'value' => $numero,
			'compare' => 'LIKE',
		];
	}

	if ( $llibreT !== '' ) {
		$meta_and[] = [
			'key' => 'dades_de_la_partitura_llibre',
			'value' => $llibreT,
			'compare' => 'LIKE',
		];
	}

	// ✅ Any exacte dins rang
	if ( $any ) {
		$meta_and[] = [
			'key' => 'dades_de_la_partitura_any_inici',
			'value' => $any,
			'type' => 'NUMERIC',
			'compare' => '<=',
		];
		$meta_and[] = [
			'key' => 'dades_de_la_partitura_any_fi',
			'value' => $any,
			'type' => 'NUMERIC',
			'compare' => '>=',
		];
	}

	// ✅ IMPORTANT: calcular DESPRÉS d’afegir l’any
	$use_meta_and = ( count( $meta_and ) > 1 ) ? $meta_and : [];

	/* =========================
	   ARGS BASE
	========================= */
	$base_args = [
		'post_type' => 'partitura',
		'posts_per_page' => $per_page,
		'paged' => $paged,
		'tax_query' => $use_tax_query,
	];

	// Ordre per defecte: per número
	if ( $orderby !== 'title' ) {
		$base_args['meta_key'] = 'dades_de_la_partitura_numero';
		$base_args['orderby'] = 'meta_value_num';
		$base_args['order'] = 'ASC';
	}

	/* =========================
	   CAS AMB CERCA GLOBAL
	========================= */
	if ( $search ) {

		$ids = [];

		// 1) Cerca per títol (respectant meta AND + tax)
		$q_title = new WP_Query( array_merge( $base_args, [
			'posts_per_page' => -1,
			'fields' => 'ids',
			's' => $search,
			'meta_query' => $use_meta_and,
		] ) );
		$ids = array_merge( $ids, $q_title->posts );

		// 2) Cerca per metes (ACF) OR, però mantenint meta AND (filtres) + tax
		$meta_or = [
			'relation' => 'OR',
			[ 'key' => 'dades_de_la_partitura_autor', 'value' => $search, 'compare' => 'LIKE' ],
			[ 'key' => 'dades_de_la_partitura_harmonitzacio', 'value' => $search, 'compare' => 'LIKE' ],
			[ 'key' => 'dades_de_la_partitura_adaptacio', 'value' => $search, 'compare' => 'LIKE' ],
			[ 'key' => 'dades_de_la_partitura_traduccio_lletra', 'value' => $search, 'compare' => 'LIKE' ],
			[ 'key' => 'dades_de_la_partitura_llibre', 'value' => $search, 'compare' => 'LIKE' ],
			[ 'key' => 'dades_de_la_partitura_numero', 'value' => $search, 'compare' => 'LIKE' ],
			[ 'key' => 'dades_de_la_partitura_temps', 'value' => $search, 'compare' => 'LIKE' ],
		];

		$meta_query_search = [ 'relation' => 'AND' ];
		if ( $use_meta_and ) {
			foreach ( $use_meta_and as $mq ) {
				if ( is_array( $mq ) )
					$meta_query_search[] = $mq;
			}
		}
		$meta_query_search[] = $meta_or;

		$q_meta = new WP_Query( array_merge( $base_args, [
			'posts_per_page' => -1,
			'fields' => 'ids',
			'meta_query' => $meta_query_search,
		] ) );

		$ids = array_merge( $ids, $q_meta->posts );
		$ids = array_values( array_unique( $ids ) );

		if ( empty( $ids ) ) {
			echo '<p>No hi ha resultats.</p>';
			wp_die();
		}

		// Ordenació per títol si s’ha demanat
		if ( $orderby === 'title' ) {
			usort( $ids, function ( $a, $b ) use ( $order ) {
				$t1 = get_the_title( $a );
				$t2 = get_the_title( $b );
				return $order === 'ASC' ? strcasecmp( $t1, $t2 ) : strcasecmp( $t2, $t1 );
			} );
		}

		// Paginació manual sobre IDs
		$offset = ( $paged - 1 ) * $per_page;
		$paged_ids = array_slice( $ids, $offset, $per_page );
		$total_pages = (int) ceil( count( $ids ) / $per_page );

		$query = new WP_Query( [
			'post_type' => 'partitura',
			'posts_per_page' => $per_page,
			'post__in' => $paged_ids,
			'orderby' => 'post__in',
		] );

	} else {

		// Sense cerca: filtres tax + meta AND
		$args = $base_args;

		if ( $use_meta_and ) {
			$args['meta_query'] = $use_meta_and;
		}

		if ( $orderby === 'title' ) {
			$args['orderby'] = 'title';
			$args['order'] = $order;
			unset( $args['meta_key'] );
		}

		$query = new WP_Query( $args );
		$total_pages = (int) $query->max_num_pages;
	}

	/* =========================
	   RENDER
	========================= */
	if ( ! $query->have_posts() ) {
		echo '<p>No hi ha resultats.</p>';
		wp_die();
	}

	while ( $query->have_posts() ) {
		$query->the_post();
		include get_stylesheet_directory() . '/partitures/templates/card.php';
	}

	/* =========================
	   PAGINACIÓ
	========================= */
	if ( $total_pages > 1 ) {
		echo '<div class="partitures-pagination">';

		if ( $paged > 1 ) {
			echo '<button class="pagination-prev" type="button">← Anterior</button>';
		}
		if ( $paged < $total_pages ) {
			echo '<button class="pagination-next" type="button">Següent →</button>';
		}

		echo '</div>';
	}

	wp_reset_postdata();
	wp_die();
}
